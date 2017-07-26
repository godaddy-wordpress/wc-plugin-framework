###
 WooCommerce Apple Pay Handler
 Version 4.7.0

 Copyright (c) 2016, SkyVerge, Inc.
 Licensed under the GNU General Public License v3.0
 http://www.gnu.org/licenses/gpl-3.0.html
###

jQuery( document ).ready ($) ->

	"use strict"

	# The WooCommerce Apple Pay handler base class.
	#
	# @since 4.7.0
	class window.SV_WC_Apple_Pay_Handler


		# Constructs the handler.
		#
		# @since 4.7.0
		constructor: (args) ->

			@params = sv_wc_apple_pay_params

			@payment_request = args.payment_request

			@buttons = '.sv-wc-apple-pay-button'

			if this.is_available()

				if @payment_request
					$( @buttons ).show()

				this.init()

				this.attach_update_events()


		# Determines if Apple Pay is available.
		#
		# @since 4.7.0
		# @return bool
		is_available: ->

			return false unless window.ApplePaySession

			ApplePaySession.canMakePaymentsWithActiveCard( @params.merchant_id ).then ( canMakePayments ) =>

				return canMakePayments


		# Initializes the handler.
		#
		# @since 4.7.0
		init: ->

			$( document.body ).on 'click', '.sv-wc-apple-pay-button', ( e ) =>

				e.preventDefault()

				this.block_ui()

				try

					@session = new ApplePaySession( 1, @payment_request )

					# set the payment card events
					@session.onvalidatemerchant        = ( event ) => this.on_validate_merchant( event )
					@session.onpaymentmethodselected   = ( event ) => this.on_payment_method_selected( event )
					@session.onshippingcontactselected = ( event ) => this.on_shipping_contact_selected( event )
					@session.onshippingmethodselected  = ( event ) => this.on_shipping_method_selected( event )
					@session.onpaymentauthorized       = ( event ) => this.on_payment_authorized( event )
					@session.oncancel                  = ( event ) => this.on_cancel_payment( event )

					@session.begin()

				catch error

					this.fail_payment( error )


		# The callback for after the merchant data is validated.
		#
		# @since 4.7.0
		on_validate_merchant: ( event ) =>

			this.validate_merchant( event.validationURL ).then ( merchant_session ) =>

				merchant_session = $.parseJSON( merchant_session )

				@session.completeMerchantValidation( merchant_session )

			, ( response ) =>

				@session.abort()

				this.fail_payment 'Merchant could no be validated. ' + response.message


		# Validates the merchant data.
		#
		# @since 4.7.0
		# @return object
		validate_merchant: ( url ) => new Promise ( resolve, reject ) =>

			data = {
				'action':      'sv_wc_apple_pay_validate_merchant',
				'nonce':       @params.validate_nonce,
				'merchant_id': @params.merchant_id,
				'url':         url
			}

			# retrieve a payment request object
			$.post @params.ajax_url, data, ( response ) =>

				if response.success
					resolve response.data
				else
					reject response.data


		# Fires after a payment method has been selected.
		#
		# @since 4.7.0
		on_payment_method_selected: ( event ) =>

			new Promise ( resolve, reject ) =>

				data = {
					'action': 'sv_wc_apple_pay_recalculate_totals',
					'nonce':  @params.recalculate_totals_nonce,
				}

				# retrieve a payment request object
				$.post @params.ajax_url, data, ( response ) =>

					if response.success

						data = response.data

						resolve @session.completePaymentMethodSelection( data.total, data.line_items )

					else

						console.error '[Apple Pay] Error selecting a shipping contact. ' + response.data.message

						reject @session.completePaymentMethodSelection( @payment_request.total, @payment_request.lineItems )


		# Fires after a shipping contact has been selected.
		#
		# @since 4.7.0
		on_shipping_contact_selected: ( event ) =>

			new Promise ( resolve, reject ) =>

				data = {
					'action':  'sv_wc_apple_pay_recalculate_totals',
					'nonce':   @params.recalculate_totals_nonce,
					'contact': event.shippingContact
				}

				# retrieve a payment request object
				$.post @params.ajax_url, data, ( response ) =>

					if response.success

						data = response.data

						resolve @session.completeShippingContactSelection( ApplePaySession.STATUS_SUCCESS, data.shipping_methods, data.total, data.line_items )

					else

						console.error '[Apple Pay] Error selecting a shipping contact. ' + response.data.message

						reject @session.completeShippingContactSelection( ApplePaySession.STATUS_FAILURE, [], @payment_request.total, @payment_request.lineItems )


		# Fires after a shipping method has been selected.
		#
		# @since 4.7.0
		on_shipping_method_selected: ( event ) =>

			new Promise ( resolve, reject ) =>

				data = {
					'action': 'sv_wc_apple_pay_recalculate_totals',
					'nonce':  @params.recalculate_totals_nonce,
					'method': event.shippingMethod.identifier
				}

				# retrieve a payment request object
				$.post @params.ajax_url, data, ( response ) =>

					if response.success

						data = response.data

						resolve @session.completeShippingMethodSelection( ApplePaySession.STATUS_SUCCESS, data.total, data.line_items )

					else

						console.error '[Apple Pay] Error selecting a shipping method. ' + response.data.message

						reject @session.completeShippingMethodSelection( ApplePaySession.STATUS_FAILURE, @payment_request.total, @payment_request.lineItems )


		# The callback for after the payment data is authorized.
		#
		# @since 4.7.0
		on_payment_authorized: ( event ) =>

			this.process_authorization( event.payment ).then ( response ) =>

				this.set_payment_status( true )

				this.complete_purchase( response )

			, ( response ) =>

				this.set_payment_status( false )

				this.fail_payment 'Payment could no be processed. ' + response.message


		# Processes the transaction data.
		#
		# @since 4.7.0
		process_authorization: ( payment ) => new Promise ( resolve, reject ) =>

			data = {
				action:  'sv_wc_apple_pay_process_payment',
				nonce:   @params.process_nonce,
				type:    @type,
				payment: JSON.stringify( payment )
			}

			$.post @params.ajax_url, data, ( response ) =>

				if response.success
					resolve response.data
				else
					reject response.data


		# The callback for when the payment card is cancelled/dismissed.
		#
		# @since 4.7.0
		on_cancel_payment: ( event ) =>

			this.unblock_ui()


		# Completes the purchase based on the gateway result.
		#
		# @since 4.7.0
		complete_purchase: ( response ) ->

			window.location = response.redirect


		# Fails the purchase based on the gateway result.
		#
		# @since 4.7.0
		fail_payment: ( error ) ->

			console.error '[Apple Pay] ' + error

			this.unblock_ui()

			this.render_errors( [ @params.generic_error ] )


		# Sets the Apple Pay payment status depending on the gateway result.
		#
		# @since 4.7.0
		set_payment_status: ( success ) ->

			if success
				status = ApplePaySession.STATUS_SUCCESS
			else
				status = ApplePaySession.STATUS_FAILURE

			@session.completePayment( status )


		# Attaches any update events required by the implementing class.
		#
		# @since 4.7.0
		attach_update_events: =>

			# Optional, for resetting the request data


		# Resets the payment request via AJAX.
		#
		# Extending handlers can call this on change events to refresh the data.
		#
		# @since 4.7.0
		reset_payment_request: ( data = {} ) =>

			this.block_ui()

			this.get_payment_request( data ).then ( response ) =>

				$( @buttons ).show()

				@payment_request = $.parseJSON( response )

				this.unblock_ui()

			, ( response ) =>

				console.error '[Apple Pay] Could not build payment request. ' + response.message

				$( @buttons ).hide()

				this.unblock_ui()


		# Gets the payment request via AJAX.
		#
		# @since 4.7.0
		get_payment_request: ( data ) => new Promise ( resolve, reject ) =>

			base_data = {
				'action': 'sv_wc_apple_pay_get_payment_request'
				'type'  : @type
			}

			$.extend data, base_data

			# retrieve a payment request object
			$.post @params.ajax_url, data, ( response ) =>

				if response.success
					resolve response.data
				else
					reject response.data


		# Renders any new errors and bring them into the viewport.
		#
		# @since 4.7.0
		render_errors: ( errors ) ->

			# hide and remove any previous errors
			$( '.woocommerce-error, .woocommerce-message' ).remove()

			# add errors
			@ui_element.prepend '<ul class="woocommerce-error"><li>' + errors.join( '</li><li>' ) + '</li></ul>'

			# unblock UI
			@ui_element.removeClass( 'processing' ).unblock()

			# scroll to top
			$( 'html, body' ).animate( { scrollTop: @ui_element.offset().top - 100 }, 1000 )


		# Blocks the payment form UI.
		#
		# @since 4.7.0
		block_ui: -> @ui_element.block( message: null, overlayCSS: background: '#fff', opacity: 0.6 )


		# Unblocks the payment form UI.
		#
		# @since 4.7.0
		unblock_ui: -> @ui_element.unblock()


	# The WooCommerce Apple Pay cart handler class.
	#
	# @since 4.7.0
	class window.SV_WC_Apple_Pay_Cart_Handler extends SV_WC_Apple_Pay_Handler


		# Constructs the handler.
		#
		# @since 4.7.0
		constructor: ( args ) ->

			@type = 'cart'

			@ui_element = $( 'form.woocommerce-cart-form' ).parents( 'div.woocommerce' )

			super( args )

		attach_update_events: =>

			# re-init if the cart totals are updated
			$( document.body ).on 'updated_cart_totals', =>

				this.reset_payment_request()


	# The WooCommerce Apple Pay checkout handler class.
	#
	# @since 4.7.0
	class window.SV_WC_Apple_Pay_Checkout_Handler extends SV_WC_Apple_Pay_Handler


		# Constructs the handler.
		#
		# @since 4.7.0
		constructor: ( args ) ->

			@type = 'checkout'

			@ui_element = $( 'form.woocommerce-checkout' )

			super( args )

			@buttons = '.sv-wc-apply-pay-checkout'


		attach_update_events: =>

			# re-init if the cart totals are updated
			$( document.body ).on 'updated_checkout', =>

				this.reset_payment_request()


	# The WooCommerce Apple Pay product handler class.
	#
	# @since 4.7.0
	class window.SV_WC_Apple_Pay_Product_Handler extends SV_WC_Apple_Pay_Handler


		# Constructs the handler.
		#
		# @since 4.7.0
		constructor: ( args ) ->

			@type = 'product'

			@ui_element = $( 'form.cart' )

			super( args )
