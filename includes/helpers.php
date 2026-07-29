<?php
/**
 * Shared helpers: sanitizers, option access, SVG icons, choice maps.
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The single source of truth for default design settings.
 *
 * @return array
 */
function ofnoa_portfolio_defaults() {
	return array(
		'heading'        => 'תיק העבודות שלנו',
		'subheading'     => 'אתרים חיים שבנינו — כל אחד סיפור של עיצוב, טכנולוגיה ותוצאות.',
		'theme'          => 'dark',      // dark | light | auto
		'accent'         => '#40E0FF',
		'accent2'        => '#B9A7FF',
		'layout'         => 'bento',     // bento | grid | masonry | carousel
		'mode'           => 'device',    // device | hand | cinematic | tilt
		'columns'        => '3',         // 2 | 3 | 4
		'gap'            => '24',
		'radius'         => '20',
		'sparkles'       => '1',
		'sparkles_style' => 'sparkles',  // sparkles | fireworks | constellation | aurora
		'animations'     => '1',
		'tilt'           => '1',
		'show_filters'   => '1',
		'font'           => 'heebo',     // heebo | assistant | rubik | system | space
		'max_width'      => '1280',
		'section_bg'     => '',          // optional custom background override
		'card_style'     => 'glass',     // glass | solid | outline
	);
}

/**
 * Get merged settings (defaults + saved).
 *
 * @return array
 */
function ofnoa_portfolio_get_settings() {
	$saved = get_option( 'ofnoa_portfolio_settings', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, ofnoa_portfolio_defaults() );
}

/**
 * Sanitize a hex colour, falling back to a default.
 *
 * @param string $value    Raw value.
 * @param string $fallback Fallback colour.
 * @return string
 */
function ofnoa_portfolio_sanitize_color( $value, $fallback = '#40E0FF' ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	$clean = sanitize_hex_color( $value );
	if ( ! $clean ) {
		// Reject anything that could inject extra CSS declarations.
		if ( $value && preg_match( '/[;{}<>]|url\s*\(|expression|@import/i', $value ) ) {
			return $fallback;
		}
		// Allow strict rgb()/rgba()/hsl()/hsla() and named-token gradients used in mesh backgrounds.
		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/]+\)$/i', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^(linear-gradient|radial-gradient|conic-gradient)\(\s*[a-z0-9.,%#()\s\/-]+\)$/i', $value ) ) {
			return $value;
		}
		return $fallback;
	}
	return $clean;
}

/**
 * Restrict a value to a set of allowed choices.
 *
 * @param string $value   Raw value.
 * @param array  $allowed Allowed values.
 * @param string $default Default value.
 * @return string
 */
function ofnoa_portfolio_enum( $value, $allowed, $default ) {
	$value = is_string( $value ) ? sanitize_key( $value ) : '';
	return in_array( $value, $allowed, true ) ? $value : $default;
}

/**
 * Sanitize a full settings array.
 *
 * @param array $input Raw input.
 * @return array
 */
function ofnoa_portfolio_sanitize_settings( $input ) {
	$d   = ofnoa_portfolio_defaults();
	$out = array();
	$in  = is_array( $input ) ? $input : array();

	$out['heading']        = isset( $in['heading'] ) ? sanitize_text_field( $in['heading'] ) : $d['heading'];
	$out['subheading']     = isset( $in['subheading'] ) ? sanitize_text_field( $in['subheading'] ) : $d['subheading'];
	$out['theme']          = ofnoa_portfolio_enum( $in['theme'] ?? '', array( 'dark', 'light', 'auto' ), $d['theme'] );
	$out['accent']         = ofnoa_portfolio_sanitize_color( $in['accent'] ?? '', $d['accent'] );
	$out['accent2']        = ofnoa_portfolio_sanitize_color( $in['accent2'] ?? '', $d['accent2'] );
	$out['layout']         = ofnoa_portfolio_enum( $in['layout'] ?? '', array( 'bento', 'grid', 'masonry', 'carousel' ), $d['layout'] );
	$out['mode']           = ofnoa_portfolio_enum( $in['mode'] ?? '', array( 'device', 'hand', 'cinematic', 'tilt' ), $d['mode'] );
	$out['columns']        = ofnoa_portfolio_enum( $in['columns'] ?? '', array( '2', '3', '4' ), $d['columns'] );
	$out['gap']            = (string) max( 0, min( 80, intval( $in['gap'] ?? $d['gap'] ) ) );
	$out['radius']         = (string) max( 0, min( 48, intval( $in['radius'] ?? $d['radius'] ) ) );
	$out['max_width']      = (string) max( 600, min( 1920, intval( $in['max_width'] ?? $d['max_width'] ) ) );
	$out['sparkles']       = empty( $in['sparkles'] ) ? '0' : '1';
	$out['sparkles_style'] = ofnoa_portfolio_enum( $in['sparkles_style'] ?? '', array( 'sparkles', 'fireworks', 'constellation', 'aurora' ), $d['sparkles_style'] );
	$out['animations']     = empty( $in['animations'] ) ? '0' : '1';
	$out['tilt']           = empty( $in['tilt'] ) ? '0' : '1';
	$out['show_filters']   = empty( $in['show_filters'] ) ? '0' : '1';
	$out['font']           = ofnoa_portfolio_enum( $in['font'] ?? '', array( 'heebo', 'assistant', 'rubik', 'system', 'space' ), $d['font'] );
	$out['card_style']     = ofnoa_portfolio_enum( $in['card_style'] ?? '', array( 'glass', 'solid', 'outline' ), $d['card_style'] );
	$out['section_bg']     = isset( $in['section_bg'] ) ? ofnoa_portfolio_sanitize_color( $in['section_bg'], '' ) : '';

	return $out;
}

/**
 * Map a font key to its stack + Google Fonts family (empty family = no external load).
 *
 * @param string $key Font key.
 * @return array{stack:string,google:string}
 */
function ofnoa_portfolio_font_map( $key ) {
	switch ( $key ) {
		case 'assistant':
			return array( 'stack' => "'Assistant', system-ui, sans-serif", 'google' => 'Assistant:wght@300;400;600;700;800' );
		case 'rubik':
			return array( 'stack' => "'Rubik', system-ui, sans-serif", 'google' => 'Rubik:wght@300;400;500;700;900' );
		case 'space':
			return array( 'stack' => "'Space Grotesk', 'Heebo', system-ui, sans-serif", 'google' => 'Space+Grotesk:wght@400;500;700&family=Heebo:wght@300;400;700;900' );
		case 'system':
			return array( 'stack' => "system-ui, -apple-system, 'Segoe UI', Arial, sans-serif", 'google' => '' );
		case 'heebo':
		default:
			return array( 'stack' => "'Heebo', system-ui, sans-serif", 'google' => 'Heebo:wght@300;400;500;700;800;900' );
	}
}

/**
 * Inline SVG icon set. Returns raw SVG markup (already safe, hard-coded).
 *
 * @param string $name Icon name.
 * @return string
 */
function ofnoa_portfolio_icon( $name ) {
	$icons = array(
		'external' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'arrow'    => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'close'    => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
		'bolt'     => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
		'globe'    => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18" stroke="currentColor" stroke-width="1.6"/></svg>',
	);
	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}
