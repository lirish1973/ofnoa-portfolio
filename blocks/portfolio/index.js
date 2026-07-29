/**
 * Ofnoa Portfolio — Gutenberg editor UI (no build step; uses wp globals).
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var C = wp.components;
	var ServerSideRender = wp.serverSideRender;
	var __ = ( wp.i18n && wp.i18n.__ ) ? wp.i18n.__ : function ( s ) { return s; };

	function opt( label, value ) { return { label: label, value: value }; }

	registerBlockType( 'ofnoa/portfolio', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = function ( key ) {
				return function ( value ) {
					var o = {};
					o[ key ] = value;
					props.setAttributes( o );
				};
			};

			var controls = el(
				InspectorControls,
				{},
				el(
					C.PanelBody,
					{ title: __( 'תצוגה ופריסה', 'ofnoa-portfolio' ), initialOpen: true },
					el( C.SelectControl, {
						label: __( 'מצב תצוגה', 'ofnoa-portfolio' ),
						value: a.mode,
						options: [ opt( 'ברירת מחדל של האתר', '' ), opt( 'מסגרות מכשירים', 'device' ), opt( 'יד מחזיקה נייד', 'hand' ), opt( 'Cinematic פרלקסה', 'cinematic' ), opt( 'כרטיסי 3D tilt', 'tilt' ) ],
						onChange: set( 'mode' ),
					} ),
					el( C.SelectControl, {
						label: __( 'פריסה', 'ofnoa-portfolio' ),
						value: a.layout,
						options: [ opt( 'ברירת מחדל', '' ), opt( 'Bento', 'bento' ), opt( 'Grid', 'grid' ), opt( 'Masonry', 'masonry' ), opt( 'Carousel', 'carousel' ) ],
						onChange: set( 'layout' ),
					} ),
					el( C.SelectControl, {
						label: __( 'עמודות', 'ofnoa-portfolio' ),
						value: a.columns,
						options: [ opt( 'ברירת מחדל', '' ), opt( '2', '2' ), opt( '3', '3' ), opt( '4', '4' ) ],
						onChange: set( 'columns' ),
					} ),
					el( C.SelectControl, {
						label: __( 'תֵמה', 'ofnoa-portfolio' ),
						value: a.theme,
						options: [ opt( 'ברירת מחדל', '' ), opt( 'כהה', 'dark' ), opt( 'בהיר', 'light' ), opt( 'אוטומטי', 'auto' ) ],
						onChange: set( 'theme' ),
					} )
				),
				el(
					C.PanelBody,
					{ title: __( 'צבעים ואפקטים', 'ofnoa-portfolio' ), initialOpen: false },
					el( C.TextControl, { label: __( 'צבע הדגשה (#hex)', 'ofnoa-portfolio' ), value: a.accent, onChange: set( 'accent' ) } ),
					el( C.TextControl, { label: __( 'צבע הדגשה משני (#hex)', 'ofnoa-portfolio' ), value: a.accent2, onChange: set( 'accent2' ) } ),
					el( C.ToggleControl, { label: __( 'אנימציות כניסה', 'ofnoa-portfolio' ), checked: a.animations, onChange: set( 'animations' ) } ),
					el( C.ToggleControl, { label: __( 'הטיית 3D לפי עכבר', 'ofnoa-portfolio' ), checked: a.tilt, onChange: set( 'tilt' ) } ),
					el( C.ToggleControl, { label: __( 'שכבת נצנוצים/זיקוקים', 'ofnoa-portfolio' ), checked: a.sparkles, onChange: set( 'sparkles' ) } ),
					el( C.SelectControl, {
						label: __( 'סגנון נצנוצים', 'ofnoa-portfolio' ),
						value: a.sparkles_style,
						options: [ opt( 'ברירת מחדל', '' ), opt( 'נצנוצים', 'sparkles' ), opt( 'זיקוקים', 'fireworks' ), opt( 'קונסטלציה', 'constellation' ), opt( 'אורורה', 'aurora' ) ],
						onChange: set( 'sparkles_style' ),
					} ),
					el( C.ToggleControl, { label: __( 'סרגל סינון', 'ofnoa-portfolio' ), checked: a.filters, onChange: set( 'filters' ) } )
				),
				el(
					C.PanelBody,
					{ title: __( 'תוכן וכותרות', 'ofnoa-portfolio' ), initialOpen: false },
					el( C.TextControl, { label: __( 'כותרת', 'ofnoa-portfolio' ), value: a.heading, onChange: set( 'heading' ) } ),
					el( C.TextControl, { label: __( 'כותרת משנה', 'ofnoa-portfolio' ), value: a.subheading, onChange: set( 'subheading' ) } ),
					el( C.TextControl, { label: __( 'מספר פריטים (ריק = הכל)', 'ofnoa-portfolio' ), type: 'number', value: a.limit, onChange: set( 'limit' ) } ),
					el( C.TextControl, { label: __( 'סלאג קטגוריה (אופציונלי)', 'ofnoa-portfolio' ), value: a.category, onChange: set( 'category' ) } )
				)
			);

			var preview = el( ServerSideRender, {
				block: 'ofnoa/portfolio',
				attributes: a,
			} );

			return el( Fragment, {}, controls, el( 'div', { className: 'ofnoa-block-preview' }, preview ) );
		},
		save: function () {
			return null; // Dynamic.
		},
	} );
} )( window.wp );
