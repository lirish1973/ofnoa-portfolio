<?php
/**
 * Uninstall cleanup. Removes plugin options only — the user's projects
 * (posts + meta) are intentionally preserved.
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ofnoa_portfolio_settings' );
delete_option( 'ofnoa_portfolio_seeded' );
delete_transient( 'ofnoa_portfolio_gh_update' );
