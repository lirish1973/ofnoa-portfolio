<?php
/**
 * Drag-and-drop ordering for portfolio projects in the admin list table.
 *
 * Persists the visual order to each project's menu_order, which the front-end
 * query already sorts by (orderby = menu_order ASC).
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Makes the projects list sortable by dragging rows.
 */
class Ofnoa_Portfolio_Reorder {

	const NONCE = 'ofnoa_reorder';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_ofnoa_reorder', array( __CLASS__, 'ajax' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'default_order' ) );
	}

	/**
	 * Only load on the projects list screen.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function assets( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || Ofnoa_Portfolio_CPT::POST_TYPE !== $screen->post_type ) {
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		wp_enqueue_script(
			'ofnoa-portfolio-reorder',
			OFNOA_PORTFOLIO_URL . 'assets/js/ofnoa-reorder.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			OFNOA_PORTFOLIO_VERSION,
			true
		);
		wp_localize_script(
			'ofnoa-portfolio-reorder',
			'OfnoaReorder',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( self::NONCE ),
			)
		);
	}

	/**
	 * Default the admin list (and empty-orderby front queries) to menu_order.
	 *
	 * @param WP_Query $q Query.
	 */
	public static function default_order( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( $q->get( 'post_type' ) !== Ofnoa_Portfolio_CPT::POST_TYPE ) {
			return;
		}
		if ( '' === $q->get( 'orderby' ) ) {
			$q->set( 'orderby', 'menu_order title' );
			$q->set( 'order', 'ASC' );
		}
	}

	/**
	 * Persist a new order (array of post IDs, top-to-bottom).
	 */
	public static function ajax() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		check_ajax_referer( self::NONCE, 'nonce' );

		$order = isset( $_POST['order'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['order'] ) ) : array();
		if ( ! $order ) {
			wp_send_json_error( 'empty' );
		}

		// Preserve the absolute baseline so ordering is stable across pages.
		$offset = isset( $_POST['offset'] ) ? absint( wp_unslash( $_POST['offset'] ) ) : 0;
		$i      = $offset;
		foreach ( $order as $id ) {
			if ( ! $id || Ofnoa_Portfolio_CPT::POST_TYPE !== get_post_type( $id ) ) {
				continue;
			}
			wp_update_post(
				array(
					'ID'         => $id,
					'menu_order' => $i,
				)
			);
			$i++;
		}
		wp_send_json_success( array( 'count' => count( $order ) ) );
	}
}
Ofnoa_Portfolio_Reorder::init();
