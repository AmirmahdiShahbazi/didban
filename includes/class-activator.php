<?php
/**
 * Plugin activation handler.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation tasks.
 */
final class Didban_Activator {

	/**
	 * Minimum supported PHP version.
	 */
	private const MIN_PHP_VERSION = '8.1';

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
			deactivate_plugins( DIDBAN_BASENAME );
			wp_die(
				esc_html(
					sprintf(
						/* translators: %s: Minimum required PHP version. */
						__( 'Didban requires PHP %s or newer.', 'didban' ),
						self::MIN_PHP_VERSION
					)
				),
				esc_html__( 'Plugin Activation Error', 'didban' ),
				array( 'back_link' => true )
			);
		}

		require_once DIDBAN_PATH . 'includes/class-database.php';
		Didban_Database::install();

		update_option( 'didban_version', DIDBAN_VERSION );
	}
}