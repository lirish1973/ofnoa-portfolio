<?php
/**
 * [ofnoa_portfolio] shortcode.
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the shortcode.
 */
class Ofnoa_Portfolio_Shortcode {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_shortcode( 'ofnoa_portfolio', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render callback.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'mode'        => null,
				'layout'      => null,
				'theme'       => null,
				'columns'     => null,
				'accent'      => null,
				'accent2'     => null,
				'gap'         => null,
				'radius'      => null,
				'max_width'   => null,
				'card_style'  => null,
				'font'        => null,
				'sparkles'    => null,
				'sparkles_style' => null,
				'animations'  => null,
				'tilt'        => null,
				'filters'     => null,
				'heading'     => null,
				'subheading'  => null,
				'limit'       => null,
				'category'    => null,
				'ids'         => null,
				'orderby'     => null,
			),
			$atts,
			'ofnoa_portfolio'
		);

		// Drop nulls so saved settings act as the base.
		$atts = array_filter(
			$atts,
			static function ( $v ) {
				return null !== $v;
			}
		);

		return Ofnoa_Portfolio_Render::render( $atts );
	}
}
Ofnoa_Portfolio_Shortcode::init();
