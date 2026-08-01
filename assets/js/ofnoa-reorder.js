/* Ofnoa Portfolio — drag-to-reorder projects in the admin list. */
( function ( $ ) {
	'use strict';

	$( function () {
		var list = $( 'table.wp-list-table tbody#the-list' );
		if ( ! list.length || typeof OfnoaReorder === 'undefined' ) {
			return;
		}

		// Compute the baseline offset from the current page so cross-page order stays stable.
		var perPage = list.find( 'tr' ).length;
		var paged = parseInt( ( window.location.search.match( /[?&]paged=(\d+)/ ) || [] )[ 1 ] || '1', 10 );
		var offset = ( paged - 1 ) * perPage;

		list.addClass( 'ofnoa-sortable' );

		list.sortable( {
			items: 'tr',
			axis: 'y',
			cursor: 'move',
			opacity: 0.75,
			placeholder: 'ofnoa-sort-placeholder',
			helper: function ( e, tr ) {
				// Lock cell widths so the dragged row keeps its layout.
				var originals = tr.children();
				var helper = tr.clone();
				helper.children().each( function ( i ) {
					$( this ).width( originals.eq( i ).width() );
				} );
				return helper;
			},
			start: function ( e, ui ) {
				ui.placeholder.height( ui.item.height() );
			},
			update: function () {
				var ids = list.find( 'tr' ).map( function () {
					var m = ( this.id || '' ).match( /post-(\d+)/ );
					return m ? m[ 1 ] : null;
				} ).get();

				list.css( 'opacity', 0.5 );
				$.post( OfnoaReorder.ajax, {
					action: 'ofnoa_reorder',
					nonce: OfnoaReorder.nonce,
					order: ids,
					offset: offset
				} ).always( function () {
					list.css( 'opacity', 1 );
				} );
			}
		} );
	} );
} )( jQuery );
