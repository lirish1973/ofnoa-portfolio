/* Ofnoa Portfolio — admin: media picker + color picker. */
( function ( $ ) {
	'use strict';

	$( function () {
		// Colour pickers.
		if ( $.fn.wpColorPicker ) {
			$( '.ofnoa-color' ).wpColorPicker();
		}

		// Media picker for screenshot fields.
		var frame = null;
		var activeTarget = null;

		$( document ).on( 'click', '.ofnoa-media-pick', function ( e ) {
			e.preventDefault();
			var $btn = $( this );
			activeTarget = $btn.data( 'target' );

			if ( frame ) {
				frame.off( 'select' );
			}
			frame = wp.media( {
				title: 'בחר צילום מסך',
				button: { text: 'שימוש בתמונה' },
				library: { type: 'image' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				var url = att.url;
				if ( att.sizes && att.sizes.large ) { url = att.sizes.large.url; }
				var $field = $( 'input[name="' + activeTarget + '"]' );
				$field.val( url );
				var $preview = $field.closest( '.ofnoa-media-field' ).find( '.ofnoa-media-preview' );
				$preview.addClass( 'has-img' ).html( '<img src="' + url + '" alt="" />' );
			} );

			frame.open();
		} );

		$( document ).on( 'click', '.ofnoa-media-clear', function ( e ) {
			e.preventDefault();
			var target = $( this ).data( 'target' );
			var $field = $( 'input[name="' + target + '"]' );
			$field.val( '' );
			$field.closest( '.ofnoa-media-field' ).find( '.ofnoa-media-preview' ).removeClass( 'has-img' ).empty();
		} );
	} );
} )( jQuery );
