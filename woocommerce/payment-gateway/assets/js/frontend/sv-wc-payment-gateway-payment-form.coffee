###
 WooCommerce SkyVerge Payment Gateway Framework Payment Form CoffeeScript
 Version 4.3.0-beta

 Copyright (c) 2014-2020, SkyVerge, Inc.
 Licensed under the GNU General Public License v3.0
 http://www.gnu.org/licenses/gpl-3.0.html
###
jQuery( document ).ready ($) ->
	"use strict"


	class window.SV_WC_Payment_Form_Handler_v5_10_0


		# Public: Instantiate Payment Form Handler
		#
		# args - object with properties:
		#   id - gateway ID
		#   id_dasherized - gateway ID dasherized
		#   plugin_id - plugin ID
		#   type - gateway type, either `credit-card` or `echeck`
		#   csc_required - true if the gateway requires the CSC field to be displayed
		#
		# Returns SV_WC_Payment_Form_Handler_5_6_1 instance
		constructor: (args) ->

			@id                      = args.id
			@id_dasherized           = args.id_dasherized
			@plugin_id               = args.plugin_id
			@type                    = args.type
			@csc_required            = args.csc_required
			@csc_required_for_tokens = args.csc_required_for_tokens
			@enabled_card_types      = args.enabled_card_types

			# which payment form?
			if $( 'form.checkout' ).length
				@form = $( 'form.checkout' )
				this.handle_checkout_page()

			else if $( 'form#order_review' ).length
				@form = $( 'form#order_review' )
				this.handle_pay_page()

			else if $( 'form#add_payment_method' ).length
				@form = $( 'form#add_payment_method' )
				this.handle_add_payment_method_page()

			else
				console.log( 'No payment form found!' )
				return

			# localized error messages
			@params = window[ "sv_wc_payment_gateway_payment_form_params" ]

			# handle sample check image hint
			@form.on( 'click', '.js-sv-wc-payment-gateway-echeck-form-check-hint, .js-sv-wc-payment-gateway-echeck-form-sample-check', => this.handle_sample_check_hint() ) if @type is 'echeck'

			$( document ).trigger( 'sv_wc_payment_form_handler_init', { id: @id, instance: @ } )


		# Public: Handle required actions on the checkout page
		#
		# Returns nothing.
		handle_checkout_page: ->

			# format/validate credit card inputs using jQuery.payment
			$( document.body ).on( 'updated_checkout', => this.format_credit_card_inputs() ) if @type is 'credit-card'

			# updated payment fields jQuery object on each checkout update (prevents stale data)
			$( document.body ).on( 'updated_checkout', => this.set_payment_fields() )

			# handle saved payment methods
			# note on the checkout page, this is bound to `updated_checkout` so it
			# fires even when other parts of the checkout are changed
			$( document.body ).on( 'updated_checkout', => this.handle_saved_payment_methods() )

			# validate payment data before order is submitted
			@form.on( "checkout_place_order_#{ @id }", => this.validate_payment_data() )


		# Public: Handle required actions on the Order > Pay page
		#
		# Returns nothing.
		handle_pay_page: ->

			this.set_payment_fields()

			# format/validate credit card inputs using jQuery.payment
			if @type is 'credit-card'
				this.format_credit_card_inputs()

			# handle saved payment methods
			this.handle_saved_payment_methods()

			# validate payment data before order is submitted
			@form.submit =>

				# but only when one of our payment gateways is selected
				return this.validate_payment_data() if $( '#order_review input[name=payment_method]:checked' ).val() is @id


		# Public: Handle required actions on the Add Payment Method page
		#
		# Returns nothing.
		handle_add_payment_method_page: ->

			this.set_payment_fields()

			# format/validate credit card inputs using jQuery.payment
			if @type is 'credit-card'
				this.format_credit_card_inputs()

			# validate payment data before order is submitted
			@form.submit =>

				# but only when one of our payment gateways is selected
				return this.validate_payment_data() if $( '#add_payment_method input[name=payment_method]:checked' ).val() is @id


		# Public: Set payment fields class variable, this is done
		# during the updated_checkout event as otherwise the reference to
		# the checkout fields becomes stale (somehow ¯\_(ツ)_/¯)
		#
		# This ensures payment fields are not marked as "invalid" before the customer has interacted with them.
		#
		# Returns nothing.
		set_payment_fields: ->

			@payment_fields = $( ".payment_method_#{ @id }" )

			$required_fields = @payment_fields.find( '.validate-required .input-text' )

			$required_fields.each( ( i, input ) =>

				# if any of the required fields have a value, bail this loop and proceed with WooCommerce validation
				if $( input ).val()
					return false

				# otherwise remove all validation result classes from the inputs, since the form is freshly loaded
				$( input ).trigger( 'input' )
			)


		# Public: Validate Payment data when order is placed
		#
		# Returns boolean, true if payment data is valid, false otherwise
		validate_payment_data: ->

			# bail when already processing
			return false if @form.is( '.processing' )

			@saved_payment_method_selected = @payment_fields.find( '.js-sv-wc-payment-gateway-payment-token:checked' ).val()

			# perform internal validations (all fields present & valid, etc)
			valid = if @type is 'credit-card' then this.validate_card_data() else this.validate_account_data()

			# let gateways perform their own validation prior to form submission
			handler = $( document.body ).triggerHandler( 'sv_wc_payment_form_valid_payment_data', { payment_form: this, passed_validation: valid } ) isnt false

			return valid && handler


		# Public: format card data using jQuery.Payment
		#
		# Returns nothing.
		format_credit_card_inputs: ->
			$card_number = $('.js-sv-wc-payment-gateway-credit-card-form-account-number').payment('formatCardNumber');
			$expiry      = $('.js-sv-wc-payment-gateway-credit-card-form-expiry').payment('formatCardExpiry');
			$csc         = $('.js-sv-wc-payment-gateway-credit-card-form-csc').payment('formatCardCVC');

			# trigger a 'change' event for non empty fields only
			$card_number.trigger( 'change') if $card_number.val() && $card_number.val().length > 0
			$expiry.trigger( 'change') if $expiry.val() && $expiry.val().length > 0
			$csc.trigger( 'change') if $csc.val() && $csc.val().length > 0

			# perform inline validation on credit card inputs
			$( '.js-sv-wc-payment-gateway-credit-card-form-input' ).on( 'change paste keyup', => this.do_inline_credit_card_validation() )


		# Public: perform inline validation on credit card fields
		#
		# Returns nothing.
		do_inline_credit_card_validation: ->

			$card_number = $( '.js-sv-wc-payment-gateway-credit-card-form-account-number' )
			$expiry      = $( '.js-sv-wc-payment-gateway-credit-card-form-expiry' )
			$csc         = $( '.js-sv-wc-payment-gateway-credit-card-form-csc' )

			$card_type = $.payment.cardType( $card_number.val() )

			if $card_type not in @enabled_card_types
				$card_number.addClass( 'invalid-card-type' )
			else
				$card_number.removeClass( 'invalid-card-type' )

			if $.payment.validateCardExpiry( $expiry.payment( 'cardExpiryVal' ) )
				$expiry.addClass( 'identified' )
			else
				$expiry.removeClass( 'identified' )

			if $.payment.validateCardCVC( $csc.val() )
				$csc.addClass( 'identified' )
			else
				$csc.removeClass( 'identified' )


		# Public: Perform validation on the credit card info entered
		#
		# Return boolean, true if credit card info is valid, false otherwise
		validate_card_data: ->

			errors = []

			csc = @payment_fields.find( '.js-sv-wc-payment-gateway-credit-card-form-csc' ).val()

			# always validate the CSC if present
			if csc?

				if csc
					errors.push( @params.cvv_digits_invalid ) if /\D/.test( csc )
					errors.push( @params.cvv_length_invalid ) if csc.length < 3 || csc.length > 4
				else if @csc_required
					if not @saved_payment_method_selected or @csc_required_for_tokens
						errors.push( @params.cvv_missing )

			# Only validate the other CC fields if necessary
			if not @saved_payment_method_selected

				account_number = @payment_fields.find( '.js-sv-wc-payment-gateway-credit-card-form-account-number' ).val()
				expiry         = $.payment.cardExpiryVal( @payment_fields.find( '.js-sv-wc-payment-gateway-credit-card-form-expiry' ).val() )

				# replace any dashes or spaces in the card number
				account_number = account_number.replace( /-|\s/g, '' )

				# validate card number
				if not account_number
					errors.push( @params.card_number_missing )
				else
					errors.push( @params.card_number_length_invalid ) if account_number.length < 12 || account_number.length > 19
					errors.push( @params.card_number_digits_invalid ) if /\D/.test( account_number )
					errors.push( @params.card_number_invalid ) unless $.payment.validateCardNumber( account_number ) # performs luhn check

				# validate expiration date
				errors.push( @params.card_exp_date_invalid ) unless $.payment.validateCardExpiry( expiry ) # validates future date

			if errors.length > 0
				this.render_errors( errors )
				return false
			else
				# get rid of any space/dash characters
				@payment_fields.find( '.js-sv-wc-payment-gateway-credit-card-form-account-number' ).val( account_number )
				return true


		# Public: Perform validation on the eCheck info entered
		#
		# Return boolean, true if eCheck info is valid, false otherwise
		validate_account_data: ->

			return true if @saved_payment_method_selected

			errors = []

			routing_number = @payment_fields.find('.js-sv-wc-payment-gateway-echeck-form-routing-number').val()
			account_number = @payment_fields.find('.js-sv-wc-payment-gateway-echeck-form-account-number').val()

			# validate routing number
			if not routing_number
				errors.push( @params.routing_number_missing )
			else
				errors.push( @params.routing_number_length_invalid ) if 9 != routing_number.length
				errors.push( @params.routing_number_digits_invalid ) if /\D/.test( routing_number )

			# validate account number
			if not account_number
				errors.push( @params.account_number_missing )
			else
				errors.push( @params.account_number_length_invalid ) if account_number.length < 3 || account_number.length > 17
				errors.push( @params.account_number_invalid ) if /\D/.test( account_number )

			if errors.length > 0
				this.render_errors( errors )
				return false
			else
				# get rid of any space/dash characters
				@payment_fields.find( '.js-sv-wc-payment-gateway-echeck-form-account-number' ).val( account_number )
				return true


		# Public: Render any new errors and bring them into the viewport
		#
		# Returns nothing.
		render_errors: (errors) ->

			# hide and remove any previous errors
			$( '.woocommerce-error, .woocommerce-message' ).remove()

			# add errors
			@form.prepend '<ul class="woocommerce-error"><li>' + errors.join( '</li><li>' ) + '</li></ul>'

			# unblock UI
			@form.removeClass( 'processing' ).unblock()
			@form.find( '.input-text, select' ).blur()

			# scroll to top
			$( 'html, body' ).animate( { scrollTop: @form.offset().top - 100 }, 1000 )


		# Public: Handle associated actions for saved payment methods
		#
		# Returns nothing.
		handle_saved_payment_methods: ->

			# make available inside change events
			id_dasherized = @id_dasherized

			csc_required             = @csc_required
			csc_required_for_tokens  = @csc_required_for_tokens

			$new_payment_method_selection = $( "div.js-wc-#{ id_dasherized }-new-payment-method-form" )
			$csc_field = $new_payment_method_selection.find( '.js-sv-wc-payment-gateway-credit-card-form-csc' ).closest( '.form-row' )

			# show/hide the saved payment methods when a saved payment method is de-selected/selected
			$( "input.js-wc-#{ @id_dasherized }-payment-token" ).change ->

				tokenized_payment_method_selected = $( "input.js-wc-#{ id_dasherized }-payment-token:checked" ).val()

				if tokenized_payment_method_selected

					# using an existing tokenized payment method, hide the 'new method' fields
					$new_payment_method_selection.slideUp( 200 )

					# move the CSC field out of the 'new method' fields so it can be used with the tokenized transaction
					if csc_required_for_tokens
						$csc_field.removeClass( 'form-row-last' ).addClass( 'form-row-first' )
						$new_payment_method_selection.after( $csc_field )

				else
					# use new payment method, display the 'new method' fields
					$new_payment_method_selection.slideDown( 200 )

					# move the CSC field back into its regular spot
					if csc_required_for_tokens
						$csc_field.removeClass( 'form-row-first' ).addClass( 'form-row-last' )
						$new_payment_method_selection.find( '.js-sv-wc-payment-gateway-credit-card-form-expiry' ).closest( '.form-row' ).after( $csc_field )
			.change()

			# display the 'save payment method' option for guest checkouts if the 'create account' option is checked
			#  but only hide the input if there is a 'create account' checkbox (some themes just display the password)
			$( 'input#createaccount' ).change ->
				$parent_row = $( "input.js-wc-#{ id_dasherized }-tokenize-payment-method" ).closest( 'p.form-row' )

				if $( this ).is( ':checked' )
					$parent_row.slideDown()
					$parent_row.next().show()
				else
					$parent_row.hide()
					$parent_row.next().hide()

			$( 'input#createaccount' ).change() unless $( 'input#createaccount' ).is( ':checked' )


		# Public: Handle showing/hiding the sample check image
		#
		# Returns nothing.
		handle_sample_check_hint: ->

			$sample_check = @payment_fields.find( '.js-sv-wc-payment-gateway-echeck-form-sample-check' )

			if $sample_check.is( ":visible" ) then $sample_check.slideUp() else $sample_check.slideDown()



		# Blocks the payment form UI
		#
		# @since 3.0.0
		block_ui: -> @form.block( message: null, overlayCSS: background: '#fff',opacity: 0.6 )


		# Unblocks the payment form UI
		#
		# @since 3.0.0
		unblock_ui: -> @form.unblock()


	# dispatch loaded event
	$( document.body ).trigger( "sv_wc_payment_form_handler_v5_10_0_loaded" )
