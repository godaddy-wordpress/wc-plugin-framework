###
 WooCommerce SkyVerge Payment Gateway My Payment Methods CoffeeScript
 Version 5.1.0

 Copyright (c) 2014-2020, SkyVerge, Inc.
 Licensed under the GNU General Public License v3.0
 http://www.gnu.org/licenses/gpl-3.0.html
###
jQuery( document ).ready ($) ->
	"use strict"

	# The My Payment Methods handler.
	#
	# @since 5.1.0
	class window.SV_WC_Payment_Methods_Handler


		# Constructs the class.
		#
		# @since 5.1.0
		#
		# @param [Object] args, with the properties:
		#     id:         [String] plugin ID
		#     slug:       [String] plugin slug or dasherized ID
		#     i18n:       [Object] localized text strings
		#     ajax_url:   [String] URL for AJAX requests
		#     ajax_nonce: [String] nonce for AJAX requests
		constructor: ( args ) ->

			@id         = args.id
			@slug       = args.slug
			@i18n       = args.i18n
			@ajax_url   = args.ajax_url
			@ajax_nonce = args.ajax_nonce

			# init tipTip
			$( ".woocommerce-PaymentMethod--actions .button.tip" ).tipTip()

			# hide the "Save" button
			$( ".woocommerce-PaymentMethod--actions .button.save" ).hide()

			# handle the edit action
			$( ".woocommerce-MyAccount-paymentMethods" ).on( 'click', ".woocommerce-PaymentMethod--actions .button.edit", ( event ) => this.edit_method( event ) )

			# handle the save action
			$( ".woocommerce-MyAccount-paymentMethods" ).on( 'click', ".woocommerce-PaymentMethod--actions .button.save", ( event ) => this.save_method( event ) )

			# handle the cancel action
			$( ".woocommerce-MyAccount-paymentMethods" ).on( 'click', ".woocommerce-PaymentMethod--actions .cancel-edit", ( event ) => this.cancel_edit( event ) )

			# handle the delete action
			$( ".woocommerce-MyAccount-paymentMethods" ).on( 'click', ".woocommerce-PaymentMethod--actions .button.delete", ( event ) =>

				if $( event.currentTarget ).hasClass( 'disabled' ) or not confirm( @i18n.delete_ays )
					event.preventDefault()

			)

			# don't follow the Add Payment Method button URL if it's disabled
			$( '.button[href*="add-payment-method"]' ).click ( event ) ->
				event.preventDefault() if $( this ).hasClass( 'disabled' )


		# Edits a payment method.
		#
		# @since 5.1.0
		#
		# @param [Object] event jQuery event object
		edit_method: ( event ) =>

			event.preventDefault()

			button = $( event.currentTarget )
			row    = button.parents( 'tr' )

			row.find( 'div.view' ).hide()
			row.find( 'div.edit' ).show()
			row.addClass( 'editing' )

			# change the Edit button to "Cancel"
			button.text( @i18n.cancel_button ).removeClass( 'edit' ).addClass( 'cancel-edit' ).removeClass( 'button' )

			button.siblings( '.save' ).show()
			button.siblings( '.delete' ).hide()

			this.enable_editing_ui()


		# Saves a payment method.
		#
		# @since 5.1.0
		#
		# @param [Object] event jQuery event object
		save_method: ( event ) =>

			event.preventDefault()

			button = $( event.currentTarget )
			row    = button.parents( 'tr' )

			this.block_ui()

			# remove any previous errors
			row.next( '.error' ).remove()

			data =
				action:   "wc_#{@id}_save_payment_method"
				nonce:    @ajax_nonce
				token_id: row.find( 'input[name=token-id]' ).val()
				data:     row.find( 'input[name]' ).serialize()

			$.post( @ajax_url, data )

				.done ( response ) =>

					return this.display_error( row, response.data ) unless response.success

					if response.data.title?
						row.find('.woocommerce-PaymentMethod--title').html( response.data.title )

					if response.data.nonce?
						@ajax_nonce = response.data.nonce

					# change the "Cancel" button back to "Edit"
					button.siblings( '.cancel-edit' ).removeClass( 'cancel-edit' ).addClass( 'edit' ).text( @i18n.edit_button ).addClass( 'button' )

					# hide the "Save" button
					button.hide()
					button.siblings( '.delete' ).show()

					this.disable_editing_ui()

				.fail ( jqXHR, textStatus, error ) =>

					this.display_error( row, error )

				.always =>

					this.unblock_ui()


		# Cancels/stop editing a payment method.
		#
		# @since 5.1.0
		#
		# @param [Object] event jQuery event object
		cancel_edit: ( event ) =>

			event.preventDefault()

			button = $( event.currentTarget )
			row    = button.parents( 'tr' )

			row.find( 'div.view' ).show()
			row.find( 'div.edit' ).hide()
			row.removeClass( 'editing' )

			# change the "Cancel" button back to "Edit"
			button.removeClass( 'cancel-edit' ).addClass( 'edit' ).text( @i18n.edit_button ).addClass( 'button' )

			button.siblings( '.save' ).hide()
			button.siblings( '.delete' ).show()

			this.disable_editing_ui()


		# Sets the page UI to the "editing" state.
		#
		# This brings proper focus to the method being edited and prevents
		# other available buttons/actions until the editing is finished or cancelled.
		#
		# @since 5.1.1
		enable_editing_ui: ->

			# set the methods table as 'editing'
			$( ".woocommerce-MyAccount-paymentMethods" ).addClass( 'editing' )

			# disable the Add Payment Method button
			$( '.button[href*="add-payment-method"]' ).addClass( 'disabled' )


		# Sets the page UI back to the default state.
		#
		# @since 5.1.1
		disable_editing_ui: ->

			# removes the methods table's "editing" status
			$( ".woocommerce-MyAccount-paymentMethods" ).removeClass( 'editing' )

			# re-enable the Add Payment Method button
			$( '.button[href*="add-payment-method"]' ).removeClass( 'disabled' )


		# Blocks the payment methods table UI.
		#
		# @since 5.1.0
		block_ui: -> $( ".woocommerce-MyAccount-paymentMethods" ).parent( 'div' ).block( message: null, overlayCSS: background: '#fff', opacity: 0.6 )


		# Unblocks the payment methods table UI.
		#
		# @since 5.1.0
		unblock_ui: -> $( ".woocommerce-MyAccount-paymentMethods" ).parent( 'div' ).unblock()


		# Displays an error message to the user.
		#
		# @since 5.1.0
		#
		# @param [Object] row payment method table row
		# @param [String] error raw error message
		# @param [String] message user error message
		display_error: ( row, error, message = '' ) ->

			console.error( error )

			message = @i18n.save_error unless message

			columns = $( ".woocommerce-MyAccount-paymentMethods thead tr th" ).size()

			$( '<tr class="error"><td colspan="' + columns + '">' + message + '</td></tr>' ).insertAfter( row ).find( 'td' ).delay( 8000 ).slideUp( 200 )
