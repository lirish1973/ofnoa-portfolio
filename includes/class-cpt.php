<?php
/**
 * Custom Post Type + taxonomy for portfolio projects.
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the ofnoa_project post type and its "stack" taxonomy.
 */
class Ofnoa_Portfolio_CPT {

	const POST_TYPE = 'ofnoa_project';
	const TAXONOMY  = 'ofnoa_stack';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register CPT + taxonomy.
	 */
	public static function register() {
		$labels = array(
			'name'               => 'תיק עבודות',
			'singular_name'      => 'פרויקט',
			'menu_name'          => 'תיק עבודות',
			'add_new'            => 'הוסף פרויקט',
			'add_new_item'       => 'הוסף פרויקט חדש',
			'edit_item'          => 'עריכת פרויקט',
			'new_item'           => 'פרויקט חדש',
			'view_item'          => 'צפייה בפרויקט',
			'search_items'       => 'חיפוש פרויקטים',
			'not_found'          => 'לא נמצאו פרויקטים',
			'not_found_in_trash' => 'אין פרויקטים בפח',
			'all_items'          => 'כל הפרויקטים',
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => true,
				'has_archive'         => false,
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-portfolio',
				'menu_position'       => 26,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
				'rewrite'             => array( 'slug' => 'project' ),
				'exclude_from_search' => false,
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => 'קטגוריות / טכנולוגיה',
					'singular_name' => 'קטגוריה',
					'add_new_item'  => 'הוסף קטגוריה',
					'all_items'     => 'כל הקטגוריות',
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'project-stack' ),
			)
		);
	}
}
Ofnoa_Portfolio_CPT::init();
