###
 WooCommerce SkyVerge Payment Gateway My Payment Methods CoffeeScript
 Version 5.1.0

 Copyright (c) 2014-2019, SkyVerge, Inc.
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

			# hide the core "No methods" message
			$( ".wc-#{@slug}-my-payment-methods" ).prev( ".woocommerce-Message.woocommerce-Message--info" ).hide() unless args.has_core_tokens

			# init tipTip
			$( ".wc-#{@slug}-payment-method-actions .button.tip" ).tipTip()

			# handle the edit action
			$( ".wc-#{@slug}-my-payment-methods" ).on( 'click', ".wc-#{@slug}-payment-method-actions .edit-payment-method", ( event ) => this.edit_method( event ) )

			# handle the save action
			$( ".wc-#{@slug}-my-payment-methods" ).on( 'click', ".wc-#{@slug}-payment-method-actions .save-payment-method", ( event ) => this.save_method( event ) )

			# handle the cancel action
			$( ".wc-#{@slug}-my-payment-methods" ).on( 'click', ".wc-#{@slug}-payment-method-actions .cancel-edit-payment-method", ( event ) => this.cancel_edit( event ) )

			# handle the delete action
			$( ".wc-#{@slug}-my-payment-methods" ).on( 'click', ".wc-#{@slug}-payment-method-actions .delete-payment-method", ( event ) =>

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

			row.find( '.view' ).hide()
			row.find( '.edit' ).show()
			row.addClass( 'editing' )

			# change the Edit button to "Cancel"
			button.text( @i18n.cancel_button ).removeClass( 'edit-payment-method' ).addClass( 'cancel-edit-payment-method' ).removeClass( 'button' )

			button.siblings( '.save-payment-method' ).show()
			button.siblings( '.delete-payment-method' ).hide()

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
				token_id: row.data( 'token-id' )
				data:     row.find( 'input[name]' ).serialize()

			$.post( @ajax_url, data )

				.done ( response ) =>

					return this.display_error( row, response.data ) unless response.success

					# remove other methods' "Default" badges if this was set as default
					if response.data.is_default
						row.siblings().find( ".wc-#{@slug}-payment-method-default .view" ).empty().siblings( '.edit' ).find( 'input' ).prop( 'checked', false )

					if response.data.html?
						row.replaceWith( response.data.html )

					if response.data.nonce?
						@ajax_nonce = response.data.nonce

					this.disable_editing_ui()

				.fail ( jqXHR, textStatus, error ) =>

					this.display_error( row, error )

				.always =>

					this.unblock_ui()


		# Cancels editing a payment method.
		#
		# @since 5.1.0
		#
		# @param [Object] event jQuery event object
		cancel_edit: ( event ) =>

			event.preventDefault()

			button = $( event.currentTarget )
			row    = button.parents( 'tr' )

			row.find( '.view' ).show()
			row.find( '.edit' ).hide()
			row.removeClass( 'editing' )

			# change the "Cancel" button back to "Edit"
			button.removeClass( 'cancel-edit-payment-method' ).addClass( 'edit-payment-method' ).text( @i18n.edit_button ).addClass( 'button' )

			button.siblings( '.save-payment-method' ).hide()
			button.siblings( '.delete-payment-method' ).show()

			this.disable_editing_ui()


		# Sets the page UI to the "editing" state.
		#
		# This brings proper focus to the method being edited and prevents
		# other available buttons/actions until the editing is finished or cancelled.
		#
		# @since 5.1.1
		enable_editing_ui: ->

			# set the methods table as 'editing'
			$( ".wc-#{@slug}-my-payment-methods" ).addClass( 'editing' )

			# disable the Add Payment Method button
			$( '.button[href*="add-payment-method"]' ).addClass( 'disabled' )


		# Sets the page UI back to the default state.
		#
		# @since 5.1.1
		disable_editing_ui: ->

			# removes the methods table's "editing" status
			$( ".wc-#{@slug}-my-payment-methods" ).removeClass( 'editing' )

			# re-enable the Add Payment Method button
			$( '.button[href*="add-payment-method"]' ).removeClass( 'disabled' )


		# Blocks the payment methods table UI.
		#
		# @since 5.1.0
		block_ui: -> $( ".wc-#{@slug}-my-payment-methods" ).parent( 'div' ).block( message: null, overlayCSS: background: '#fff', opacity: 0.6 )


		# Unblocks the payment methods table UI.
		#
		# @since 5.1.0
		unblock_ui: -> $( ".wc-#{@slug}-my-payment-methods" ).parent( 'div' ).unblock()


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

			columns = $( ".wc-#{@slug}-my-payment-methods thead tr th" ).size()

			$( '<tr class="error"><td colspan="' + columns + '">' + message + '</td></tr>' ).insertAfter( row ).find( 'td' ).delay( 8000 ).slideUp( 200 )
