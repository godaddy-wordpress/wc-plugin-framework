###
 WooCommerce Apple Pay Handler
 Version 4.6.0-dev

 Copyright (c) 2016, SkyVerge, Inc.
 Licensed under the GNU General Public License v3.0
 http://www.gnu.org/licenses/gpl-3.0.html
###

jQuery( document ).ready ($) ->

	"use strict"

	# The WooCommerce Apple Pay handler base class.
	#
	# @since 4.6.0-dev
	class window.SV_WC_Apple_Pay_Handler


		# Constructs the handler.
		#
		# @since 3.9.2-1
		constructor: (args) ->

			@params = sv_wc_apple_pay_params

			@request_action = args.request_action
			@request_nonce  = args.request_nonce

			if this.is_available()

				this.init()


		# Determines if Apple Pay is available.
		#
		# @since 3.9.2-1
		# @return bool
		is_available: ->

			return false unless window.ApplePaySession

			ApplePaySession.canMakePaymentsWithActiveCard( @params.merchant_id ).then ( canMakePayments ) =>

				return canMakePayments


		init: ->

			this.block_ui()

			@buttons = $( '.sv-wc-apple-pay-button' )

			this.get_payment_request().then ( response ) =>

				@payment_request = $.parseJSON( response )

				if @payment_request

					@buttons.show().prop( 'disabled', false )

					this.unblock_ui()

			, ( response ) =>

				console.log '[Apple Pay Error] ' + response

				this.unblock_ui()

			$( document.body ).on 'click', '.sv-wc-apple-pay-button:not([disabled])', ( e ) =>

				e.preventDefault()

				this.block_ui()

				try

					@session = new ApplePaySession( 1, @payment_request )

					@session.onvalidatemerchant = ( event ) => this.on_validate_merchant( event )

					@session.onpaymentauthorized = ( event ) => this.on_process_authorization( event )

					@session.oncancel = ( event ) => this.on_cancel_payment( event )

					@session.begin()

				catch error

					@session.abort()

					this.fail_payment( error )


		# Gets the payment request via AJAX.
		#
		# @since 4.6.0-dev
		get_payment_request: => new Promise ( resolve, reject ) =>

			data = {
				'action':     @request_action
				'nonce':      @request_nonce
				'product_id': @product_id
			}

			# retrieve a payment request object
			$.post @params.ajax_url, data, ( response ) =>

				if response.result is 'success'
					resolve response.request
				else
					reject response.message


		on_validate_merchant: ( event ) =>

			this.validate_merchant( event.validationURL ).then ( merchant_session ) =>

				merchant_session = $.parseJSON( merchant_session )

				@session.completeMerchantValidation( merchant_session )

			, ( error ) =>

				@session.abort()

				this.fail_payment 'Merchant could no be validated. ' + error


		# Validates the merchant data.
		#
		# @since 3.9.2-1
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

				if response.result is 'success'
					resolve response.merchant_session
				else
					reject response.message


		on_process_authorization: ( event ) =>

			this.process_authorization( event.payment ).then ( response ) =>

				this.set_payment_status( response.result )

				this.complete_purchase( response )

			, ( error ) =>

				this.set_payment_status( false )

				this.fail_payment 'Payment could no be processed. ' + error


		# Processes the transaction data after the payment is authorized.
		#
		# @since 3.9.2-1
		process_authorization: ( payment ) => new Promise ( resolve, reject ) =>

			data = {
				action:  'sv_wc_apple_pay_process_payment',
				nonce:   @params.process_nonce,
				payment: JSON.stringify( payment )
			}

			$.post @params.ajax_url, data, ( response ) =>

				if response.result is 'success'
					resolve response
				else
					reject response.message


		on_cancel_payment: ( event ) =>

			this.unblock_ui()


		complete_purchase: ( response ) ->

			window.location = response.redirect


		fail_payment: ( error ) ->

			console.log '[Apple Pay Error] ' + error

			this.unblock_ui()

			this.render_errors( ['An error occurred'] ) # localize


		# Sets the Apple Pay payment status depending on the processing result.
		#
		# @since 3.9.2-1
		set_payment_status: ( result ) ->

			if result is 'success'
				status = ApplePaySession.STATUS_SUCCESS
			else
				status = ApplePaySession.STATUS_FAILURE

			@session.completePayment( status )


		# Public: Render any new errors and bring them into the viewport
		#
		# Returns nothing.
		render_errors: ( errors ) ->

			# hide and remove any previous errors
			$( '.woocommerce-error, .woocommerce-message' ).remove()

			# add errors
			@payment_form.prepend '<ul class="woocommerce-error"><li>' + errors.join( '</li><li>' ) + '</li></ul>'

			# unblock UI
			@payment_form.removeClass( 'processing' ).unblock()

			# scroll to top
			$( 'html, body' ).animate( { scrollTop: @payment_form.offset().top - 100 }, 1000 )


		# Blocks the payment form UI
		#
		# @since 4.6.0-dev
		block_ui: -> @payment_form.addClass( 'processing' ).block( message: null, overlayCSS: background: '#fff',opacity: 0.6 )


		# Unblocks the payment form UI
		#
		# @since 4.6.0-dev
		unblock_ui: -> @payment_form.removeClass( 'processing' ).unblock()


	# The WooCommerce Apple Pay cart handler class.
	#
	# @since 4.6.0-dev
	class window.SV_WC_Apple_Pay_Cart_Handler extends SV_WC_Apple_Pay_Handler


		# Constructs the handler.
		#
		# @since 3.9.2-1
		constructor: (args) ->

			@payment_form = $( '.cart_totals' )

			super(args)

			# re-init if the cart totals are updated
			$( document.body ).on( 'updated_cart_totals', => this.init() )


	# The WooCommerce Apple Pay checkout handler class.
	#
	# @since 4.6.0-dev
	class window.SV_WC_Apple_Pay_Checkout_Handler extends SV_WC_Apple_Pay_Handler


		# Constructs the handler.
		#
		# @since 3.9.2-1
		constructor: (args) ->

			@payment_form = $( 'form.woocommerce-checkout' )

			super(args)

			# re-init if the cart totals are updated
			$( document.body ).on( 'update_checkout', => this.init() )


	# The WooCommerce Apple Pay product handler class.
	#
	# @since 4.6.0-dev
	class window.SV_WC_Apple_Pay_Product_Handler extends SV_WC_Apple_Pay_Handler


		# Constructs the handler.
		#
		# @since 3.9.2-1
		constructor: (args) ->

			@payment_form = $( 'form.cart' )

			@product_id = args.product_id

			super(args)

			# re-init if the varation form is updated
			$( document.body ).on( 'show_variation', ( event, variation, purchasable ) => this.init_variations( variation.variation_id, purchasable ) )


		init_variations: ( variation_id, purchasable ) =>

			if variation_id and purchasable
				@product_id = variation_id
			else
				@product_id = 0

			this.init()


		init: =>

			return if @product_id is 0

			super()
