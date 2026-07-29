<?php
/**
 * Lightweight GitHub auto-updater — same pattern as ofnoa-cards / ofnoa-forms.
 * Polls a raw `plugin-updates.json` in the repo root and feeds the WP update API.
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks GitHub for newer releases and integrates with the WP updater.
 */
class Ofnoa_Portfolio_GitHub_Updater {

	/**
	 * Transient cache key.
	 */
	const CACHE_KEY = 'ofnoa_portfolio_gh_update';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'purge' ), 10, 0 );
	}

	/**
	 * Remote manifest URL (branch: main).
	 *
	 * @return string
	 */
	private static function manifest_url() {
		return sprintf(
			'https://raw.githubusercontent.com/%s/%s/main/plugin-updates.json',
			rawurlencode( OFNOA_PORTFOLIO_GH_USER ),
			rawurlencode( OFNOA_PORTFOLIO_GH_REPO )
		);
	}

	/**
	 * Fetch + cache the remote manifest (12h).
	 *
	 * @return array|false
	 */
	private static function remote() {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : false;
		}

		$response = wp_remote_get(
			self::manifest_url(),
			array(
				'timeout' => 12,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( self::CACHE_KEY, array(), 3 * HOUR_IN_SECONDS );
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['version'] ) ) {
			set_transient( self::CACHE_KEY, array(), 3 * HOUR_IN_SECONDS );
			return false;
		}

		set_transient( self::CACHE_KEY, $data, 12 * HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Inject an update into the plugins transient.
	 *
	 * @param mixed $transient Update transient.
	 * @return mixed
	 */
	public static function check( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$data = self::remote();
		if ( ! $data ) {
			return $transient;
		}

		if ( version_compare( $data['version'], OFNOA_PORTFOLIO_VERSION, '>' ) ) {
			$item = array(
				'slug'        => dirname( OFNOA_PORTFOLIO_BASENAME ),
				'plugin'      => OFNOA_PORTFOLIO_BASENAME,
				'new_version' => sanitize_text_field( $data['version'] ),
				'url'         => 'https://github.com/' . OFNOA_PORTFOLIO_GH_USER . '/' . OFNOA_PORTFOLIO_GH_REPO,
				'package'     => isset( $data['download_url'] ) ? esc_url_raw( $data['download_url'] ) : '',
				'tested'      => isset( $data['tested'] ) ? sanitize_text_field( $data['tested'] ) : '',
				'requires_php'=> isset( $data['requires_php'] ) ? sanitize_text_field( $data['requires_php'] ) : '',
			);
			$transient->response[ OFNOA_PORTFOLIO_BASENAME ] = (object) $item;
		}

		return $transient;
	}

	/**
	 * Provide the "View details" modal content.
	 *
	 * @param false|object|array $result Default result.
	 * @param string             $action API action.
	 * @param object             $args   Query args.
	 * @return false|object
	 */
	public static function info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( empty( $args->slug ) || dirname( OFNOA_PORTFOLIO_BASENAME ) !== $args->slug ) {
			return $result;
		}

		$data = self::remote();
		if ( ! $data ) {
			return $result;
		}

		$info               = new stdClass();
		$info->name         = 'Ofnoa Portfolio — תיק עבודות';
		$info->slug         = dirname( OFNOA_PORTFOLIO_BASENAME );
		$info->version      = $data['version'];
		$info->author       = '<a href="https://github.com/' . esc_attr( OFNOA_PORTFOLIO_GH_USER ) . '">Ofnoa</a>';
		$info->homepage     = 'https://github.com/' . OFNOA_PORTFOLIO_GH_USER . '/' . OFNOA_PORTFOLIO_GH_REPO;
		$info->requires     = isset( $data['requires'] ) ? $data['requires'] : '5.8';
		$info->tested       = isset( $data['tested'] ) ? $data['tested'] : '';
		$info->requires_php = isset( $data['requires_php'] ) ? $data['requires_php'] : '7.4';
		$info->last_updated = isset( $data['last_updated'] ) ? $data['last_updated'] : '';
		$info->download_link = isset( $data['download_url'] ) ? esc_url_raw( $data['download_url'] ) : '';
		if ( isset( $data['sections'] ) && is_array( $data['sections'] ) ) {
			$info->sections = array_map( 'wp_kses_post', $data['sections'] );
		} else {
			$info->sections = array( 'changelog' => 'ראה GitHub לפרטי הגרסה.' );
		}

		return $info;
	}

	/**
	 * Clear the cache after any update runs.
	 */
	public static function purge() {
		delete_transient( self::CACHE_KEY );
	}
}
Ofnoa_Portfolio_GitHub_Updater::init();
