<?php
/**
 * Main plugin orchestrator (singleton).
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires cross-cutting concerns; individual features self-register in their files.
 */
final class Ofnoa_Portfolio {

	/**
	 * Singleton instance.
	 *
	 * @var Ofnoa_Portfolio|null
	 */
	private static $instance = null;

	/**
	 * Accessor.
	 *
	 * @return Ofnoa_Portfolio
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: register hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_filter( 'plugin_action_links_' . OFNOA_PORTFOLIO_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'ofnoa-portfolio', false, dirname( OFNOA_PORTFOLIO_BASENAME ) . '/languages' );
	}

	/**
	 * Add a quick link to the settings screen from the plugins list.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url  = admin_url( 'edit.php?post_type=' . Ofnoa_Portfolio_CPT::POST_TYPE . '&page=ofnoa-portfolio-settings' );
		$link = '<a href="' . esc_url( $url ) . '">עיצוב ותצוגה</a>';
		array_unshift( $links, $link );
		return $links;
	}
}
