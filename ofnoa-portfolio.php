<?php
/**
 * Plugin Name:       Ofnoa Portfolio — תיק עבודות מרשים
 * Plugin URI:        https://github.com/lirish1973/ofnoa-portfolio
 * Description:        תצוגת תיק עבודות טכנולוגית ומרשימה למעצבי אתרים — מסגרות מכשירים, יד מחזיקה נייד, פרלקסה קולנועית, כרטיסי 3D ואפקט נצנוצים/זיקוקים. עצמאי לחלוטין (CPT + שורטקוד + בלוק Gutenberg), רספונסיבי, RTL, ומגוון עצום של אפשרויות עיצוב.
 * Version:           1.0.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Liraz (Ofnoa)
 * Author URI:        https://github.com/lirish1973
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ofnoa-portfolio
 * Domain Path:       /languages
 *
 * @package OfnoaPortfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'OFNOA_PORTFOLIO_VERSION', '1.0.1' );
define( 'OFNOA_PORTFOLIO_FILE', __FILE__ );
define( 'OFNOA_PORTFOLIO_DIR', plugin_dir_path( __FILE__ ) );
define( 'OFNOA_PORTFOLIO_URL', plugin_dir_url( __FILE__ ) );
define( 'OFNOA_PORTFOLIO_BASENAME', plugin_basename( __FILE__ ) );

// GitHub auto-update coordinates (matches the ofnoa-cards / ofnoa-forms mechanism).
define( 'OFNOA_PORTFOLIO_GH_USER', 'lirish1973' );
define( 'OFNOA_PORTFOLIO_GH_REPO', 'ofnoa-portfolio' );

require_once OFNOA_PORTFOLIO_DIR . 'includes/helpers.php';
require_once OFNOA_PORTFOLIO_DIR . 'includes/class-cpt.php';
require_once OFNOA_PORTFOLIO_DIR . 'includes/class-metaboxes.php';
require_once OFNOA_PORTFOLIO_DIR . 'includes/class-settings.php';
require_once OFNOA_PORTFOLIO_DIR . 'includes/class-render.php';
require_once OFNOA_PORTFOLIO_DIR . 'includes/class-shortcode.php';
require_once OFNOA_PORTFOLIO_DIR . 'includes/class-block.php';
require_once OFNOA_PORTFOLIO_DIR . 'includes/class-seeder.php';
require_once OFNOA_PORTFOLIO_DIR . 'includes/class-duplicate.php';
require_once OFNOA_PORTFOLIO_DIR . 'includes/class-github-updater.php';
require_once OFNOA_PORTFOLIO_DIR . 'includes/class-ofnoa-portfolio.php';

/**
 * Boot the plugin.
 */
function ofnoa_portfolio() {
	return Ofnoa_Portfolio::instance();
}
ofnoa_portfolio();

/**
 * Activation: register CPT (so rewrite rules exist), seed demo content, flush.
 */
function ofnoa_portfolio_activate() {
	Ofnoa_Portfolio_CPT::register();
	Ofnoa_Portfolio_Settings::install_defaults();
	Ofnoa_Portfolio_Seeder::maybe_seed();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ofnoa_portfolio_activate' );

/**
 * Deactivation: flush rewrite rules.
 */
function ofnoa_portfolio_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ofnoa_portfolio_deactivate' );
