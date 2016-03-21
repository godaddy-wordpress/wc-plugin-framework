###
 WooCommerce SkyVerge Payment Gateway Framework Token Editor CoffeeScript
 Version 4.3.0-beta

 Copyright (c) 2016, SkyVerge, Inc.
 Licensed under the GNU General Public License v3.0
 http://www.gnu.org/licenses/gpl-3.0.html
###
jQuery( document ).ready ($) ->
	"use strict"

	wc_payment_gateway_token_editor = window.wc_payment_gateway_token_editor ? {}


	$( '.sv_wc_payment_gateway_token_editor' ).each () ->

		tokens = $( this ).find( 'tr.token' )

		if ( tokens.length == 0 )
			$( this ).find( 'tr.no-tokens' ).show()
		else
			$( this ).find( 'tr.no-tokens' ).hide()


	# Remove a token
	$( '.sv_wc_payment_gateway_token_editor' ).on 'click', '.button[data-action="remove"]', ( e ) ->

		e.preventDefault()

		editor = $( this ).closest( 'table' )

		editor.block( message: null, overlayCSS: background: '#fff',opacity: 0.6 )

		if ( confirm( wc_payment_gateway_token_editor.actions.remove_token.ays ) )

			row = $( this ).closest( 'tr' )

			# If this is an unsaved token, just remove the row
			if ( row.hasClass( 'new-token' ) )

				row.remove()

			# Otherwise, fire up AJAX!
			else

				data =
					action:   'wc_payment_gateway_' + editor.data( 'gateway-id' ) + '_admin_remove_payment_token'
					user_id:  $( this ).data( 'user-id' )
					token_id: $( this ).data( 'token-id' )
					security: wc_payment_gateway_token_editor.actions.remove_token.nonce

				$.post wc_payment_gateway_token_editor.ajax_url, data, ( response ) ->

					if response.success is true then $( row ).remove()

			# No more tokens? Display a message
			if ( editor.find( 'tr.token' ).length == 0 )
				editor.find( 'tr.no-tokens' ).show()

		editor.unblock()

	# Add a new (blank) token
	$( 'table.sv_wc_payment_gateway_token_editor' ).on 'click', '.button[data-action="add-new"]', ( e ) ->

		e.preventDefault()

		editor = $( this ).closest( 'table' )

		editor.block( message: null, overlayCSS: background: '#fff',opacity: 0.6 )

		body  = editor.find( 'tbody.tokens' )
		count = body.find( 'tr.token' ).length

		data =
			action:   'wc_payment_gateway_' + editor.data( 'gateway-id' ) + '_admin_get_blank_payment_token'
			index:    count + 1
			security: wc_payment_gateway_token_editor.actions.add_token.nonce

		$.post wc_payment_gateway_token_editor.ajax_url, data, ( response ) ->

			if response.success is true then body.append( response.data )

			editor.find( 'tr.no-tokens' ).hide()

			editor.unblock()


	# Refresh the tokens
	$( 'table.sv_wc_payment_gateway_token_editor' ).on 'click', '.button[data-action="refresh"]', ( e ) ->

		e.preventDefault()

		editor = $( this ).closest( 'table' )

		editor.block( message: null, overlayCSS: background: '#fff',opacity: 0.6 )

		body  = editor.find( 'tbody.tokens' )
		count = body.find( 'tr.token' ).length

		data =
			action:   'wc_payment_gateway_' + editor.data( 'gateway-id' ) + '_admin_refresh_payment_tokens'
			user_id:  $( this ).data( 'user-id' )
			security: wc_payment_gateway_token_editor.actions.refresh.nonce

		$.post wc_payment_gateway_token_editor.ajax_url, data, ( response ) ->

			if ( response.success is true )

				if ( response.data )
					editor.find( 'tr.no-tokens' ).hide()
					body.html( response.data )
				else
					body.empty()
					editor.find( 'tr.no-tokens' ).show()

			editor.unblock()

	# Save the tokens
	$( 'table.sv_wc_payment_gateway_token_editor' ).on 'click', '.sv-wc-payment-gateway-token-editor-action-button[data-action="save"]', ( e ) ->

		editor      = $( this ).closest( 'table' )
		actions_row = editor.find( 'tfoot th' )

		editor.block( message: null, overlayCSS: background: '#fff',opacity: 0.6 )

		actions_row.find( '.error, .success' ).remove();

		# Validate the input data

		inputs  = editor.find( 'tbody.tokens tr.token input[type="text"]' )
		focused = false

		inputs.each ( index ) ->

			$( this ).removeClass( 'error' )

			value    = $( this ).val()
			required = $( this ).prop( 'required' )
			pattern  = $( this ).attr( 'pattern' )

			return unless required or value

			if ( ! value.match( pattern ) or ( required and ! value ) )

				e.preventDefault()

				$( this ).addClass( 'error' )

				if ( ! focused )
					actions_row.prepend( '<span class="error">' + wc_payment_gateway_token_editor.actions.save.error + '</span>' )
					$( this ).focus()
					focused = true

				editor.unblock()
