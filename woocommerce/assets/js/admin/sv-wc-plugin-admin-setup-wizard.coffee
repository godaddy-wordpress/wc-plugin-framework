"use strict"

###*
# WooCommerce Plugin Framework Setup Wizard scripts.
#
# @since 5.3.0-dev
###
jQuery( document ).ready ( $ ) ->


	# when a checkbox is toggled, update the wrapper's classes
	$( '.sv-wc-plugin-admin-setup-control' ).on( 'change', '.enable input', ->
		if ( $( this ).is( ':checked' ) )
			$( this ).closest( '.toggle' ).removeClass( 'disabled' )
		else
			$( this ).closest( '.toggle' ).addClass( 'disabled' )
		)

	# when a toggle is clicked, update the input
	$( '.sv-wc-plugin-admin-setup-control' ).on( 'click', '.enable', ( e ) ->

		if ( $( e.target ).is( 'input' ) )
			e.stopPropagation()
			return

		$checkbox = $( this ).find( 'input[type="checkbox"]' )

		$checkbox.prop( 'checked', ! $checkbox.is( ':checked' ) ).change()
	)
