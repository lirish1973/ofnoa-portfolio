<?php
/**
 * "Duplicate project" row action for the portfolio list table.
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a one-click duplicate action to each project in the admin list.
 */
class Ofnoa_Portfolio_Duplicate {

	const ACTION = 'ofnoa_duplicate_project';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'post_row_actions', array( __CLASS__, 'row_action' ), 10, 2 );
		add_action( 'admin_action_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
	}

	/**
	 * Add the "Duplicate" link to the row actions.
	 *
	 * @param array   $actions Existing actions.
	 * @param WP_Post $post    Current post.
	 * @return array
	 */
	public static function row_action( $actions, $post ) {
		if ( Ofnoa_Portfolio_CPT::POST_TYPE !== $post->post_type ) {
			return $actions;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}
		$url = wp_nonce_url(
			admin_url( 'admin.php?action=' . self::ACTION . '&post=' . $post->ID ),
			self::ACTION . '_' . $post->ID
		);
		$actions['ofnoa_duplicate'] = '<a href="' . esc_url( $url ) . '">שכפל פרויקט</a>';
		return $actions;
	}

	/**
	 * Perform the duplication and redirect back to the list.
	 */
	public static function handle() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! $post_id || ! wp_verify_nonce( $nonce, self::ACTION . '_' . $post_id ) ) {
			wp_die( 'קישור שכפול לא תקין.' );
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'אין לך הרשאה לשכפל.' );
		}

		$original = get_post( $post_id );
		if ( ! $original || Ofnoa_Portfolio_CPT::POST_TYPE !== $original->post_type ) {
			wp_die( 'הפרויקט לא נמצא.' );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => Ofnoa_Portfolio_CPT::POST_TYPE,
				'post_status'  => 'draft',
				'post_title'   => $original->post_title . ' (עותק)',
				'post_content' => $original->post_content,
				'post_excerpt' => $original->post_excerpt,
				'menu_order'   => $original->menu_order,
			),
			true
		);

		if ( is_wp_error( $new_id ) || ! $new_id ) {
			wp_die( 'שכפול הפרויקט נכשל.' );
		}

		// Copy all meta.
		$meta = get_post_meta( $post_id );
		foreach ( $meta as $key => $values ) {
			if ( '_edit_lock' === $key || '_edit_last' === $key ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, maybe_unserialize( wp_slash( $value ) ) );
			}
		}

		// Copy taxonomy terms.
		$terms = wp_get_object_terms( $post_id, Ofnoa_Portfolio_CPT::TAXONOMY, array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $terms ) && $terms ) {
			wp_set_object_terms( $new_id, $terms, Ofnoa_Portfolio_CPT::TAXONOMY, false );
		}

		// Copy featured image.
		$thumb = get_post_thumbnail_id( $post_id );
		if ( $thumb ) {
			set_post_thumbnail( $new_id, $thumb );
		}

		$redirect = add_query_arg(
			array(
				'post_type'      => Ofnoa_Portfolio_CPT::POST_TYPE,
				'ofnoa_dup_done' => 1,
			),
			admin_url( 'edit.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Success notice after a duplication.
	 */
	public static function notice() {
		if ( empty( $_GET['ofnoa_dup_done'] ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || Ofnoa_Portfolio_CPT::POST_TYPE !== $screen->post_type ) {
			return;
		}
		echo '<div class="notice notice-success is-dismissible"><p>הפרויקט שוכפל בהצלחה כטיוטה — ערוך ופרסם כשמוכן.</p></div>';
	}
}
Ofnoa_Portfolio_Duplicate::init();
