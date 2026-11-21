<?php
/**
 * Plugin Name:       Didban
 * Plugin URI:        https://example.com/didban
 * Description:       WooCommerce variable product and variation health inspector.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Didban
 * Text Domain:       didban
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.0
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

define( 'DIDBAN_VERSION', '1.0.0' );
define( 'DIDBAN_FILE', __FILE__ );
define( 'DIDBAN_PATH', plugin_dir_path( __FILE__ ) );
define( 'DIDBAN_URL', plugin_dir_url( __FILE__ ) );
define( 'DIDBAN_BASENAME', plugin_basename( __FILE__ ) );

require_once DIDBAN_PATH . 'includes/class-activator.php';
require_once DIDBAN_PATH . 'includes/class-deactivator.php';
require_once DIDBAN_PATH . 'includes/class-didban.php';

register_activation_hook( DIDBAN_FILE, array( 'Didban_Activator', 'activate' ) );
register_deactivation_hook( DIDBAN_FILE, array( 'Didban_Deactivator', 'deactivate' ) );

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
 */
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				DIDBAN_FILE,
				true
			);
		}
	}
);

/**
 * Bootstrap the plugin after all plugins are loaded.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		Didban::instance()->init();
	}
);