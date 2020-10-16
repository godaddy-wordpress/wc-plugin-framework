###
 WooCommerce SkyVerge Payment Gateway Framework Order Admin CoffeeScript
 Version 5.0.0

 Copyright (c) 2017-2020, SkyVerge, Inc.
 Licensed under the GNU General Public License v3.0
 http://www.gnu.org/licenses/gpl-3.0.html
###

jQuery( document ).ready ($) ->
	"use strict"

	sv_wc_payment_gateway_admin_order = window.sv_wc_payment_gateway_admin_order ? {}
	woocommerce_admin                 = window.woocommerce_admin ? {}
	woocommerce_admin_meta_boxes      = window.woocommerce_admin_meta_boxes ? {}
	accounting                        = window.accounting ? {}

	window.sv_wc_payment_gateway_admin_order_add_capture_events = () ->

		# prevent the events to be attached again
		if ( $( '.sv-wc-payment-gateway-partial-capture.sv-wc-payment-gateway-partial-capture-with-events' ).length )
			return

		$( '.sv-wc-payment-gateway-partial-capture' ).addClass( 'sv-wc-payment-gateway-partial-capture-with-events' ).appendTo( '#woocommerce-order-items .inside' )

		$( '#woocommerce-order-items' ).on 'click', '.sv-wc-payment-gateway-capture:not(.disabled)', ( e ) ->

			e.preventDefault()

			if ( $( @ ).hasClass( 'partial-capture' ) )

				$( 'div.sv-wc-payment-gateway-partial-capture' ).slideDown();
				$( 'div.wc-order-data-row-toggle' ).not( 'div.sv-wc-payment-gateway-partial-capture' ).slideUp();
				$( 'div.wc-order-totals-items' ).slideUp();

			else

				submitCapture()


		$( '.sv-wc-payment-gateway-partial-capture' ).on 'change keyup', '#capture_amount', ( e ) ->

			total = accounting.unformat( $( @ ).val(), woocommerce_admin.mon_decimal_point );

			if ( total )
				$( 'button.capture-action' ).removeAttr( 'disabled' )
			else
				$( 'button.capture-action' ).attr( 'disabled', 'disabled' )

			$( 'button .capture-amount .amount' ).text( accounting.formatMoney( total, {
				symbol:    woocommerce_admin_meta_boxes.currency_format_symbol,
				decimal:   woocommerce_admin_meta_boxes.currency_format_decimal_sep,
				thousand:  woocommerce_admin_meta_boxes.currency_format_thousand_sep,
				precision: woocommerce_admin_meta_boxes.currency_format_num_decimals,
				format:    woocommerce_admin_meta_boxes.currency_format
			} ) )


		$( '.sv-wc-payment-gateway-partial-capture' ).on 'click', '.capture-action', ( e ) ->

			e.preventDefault()

			amount  = $( '.sv-wc-payment-gateway-partial-capture #capture_amount' ).val()
			comment = $( '.sv-wc-payment-gateway-partial-capture #capture_comment' ).val()

			submitCapture( amount, comment )

	submitCapture = ( amount = '', comment = '' ) ->

		if ( confirm( sv_wc_payment_gateway_admin_order.capture_ays ) )

			$( '#woocommerce-order-items' ).block( {
				message: null
				overlayCSS: {
					background: '#fff'
					opacity: 0.6
				}
			} )

			data =
				action:     sv_wc_payment_gateway_admin_order.capture_action
				nonce:      sv_wc_payment_gateway_admin_order.capture_nonce
				gateway_id: sv_wc_payment_gateway_admin_order.gateway_id
				order_id:   sv_wc_payment_gateway_admin_order.order_id
				amount:     amount
				comment:    comment

			$.ajax(
				url:  sv_wc_payment_gateway_admin_order.ajax_url
				data: data
			).done( ( response ) ->

				alert( response.data.message ) if response.data? and response.data.message?

				location.reload() if response.success

			).fail( ->

				# connection error
				alert( sv_wc_payment_gateway_admin_order.capture_error )

			).always( ->

				# never leave the UI blocked
				$( '#woocommerce-order-items' ).unblock()
			)

	window.sv_wc_payment_gateway_admin_order_add_capture_events()
