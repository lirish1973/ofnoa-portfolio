<?php
/**
 * Gutenberg block registration (dynamic, server-rendered).
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "ofnoa/portfolio" dynamic block.
 */
class Ofnoa_Portfolio_Block {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the block from its metadata folder.
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		register_block_type(
			OFNOA_PORTFOLIO_DIR . 'blocks/portfolio',
			array(
				'render_callback' => array( __CLASS__, 'render' ),
			)
		);
	}

	/**
	 * Server render.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		$map  = array(
			'mode', 'layout', 'theme', 'columns', 'accent', 'accent2', 'gap', 'radius',
			'max_width', 'card_style', 'font', 'sparkles', 'sparkles_style', 'animations',
			'tilt', 'filters', 'heading', 'subheading', 'limit', 'category', 'orderby',
		);
		$args = array();
		foreach ( $map as $key ) {
			if ( isset( $attributes[ $key ] ) && '' !== $attributes[ $key ] ) {
				// Booleans arrive as real booleans from the editor.
				if ( is_bool( $attributes[ $key ] ) ) {
					$args[ $key ] = $attributes[ $key ] ? '1' : '0';
				} else {
					$args[ $key ] = $attributes[ $key ];
				}
			}
		}
		return Ofnoa_Portfolio_Render::render( $args );
	}
}
Ofnoa_Portfolio_Block::init();
