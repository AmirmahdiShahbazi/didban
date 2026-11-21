<?php
/**
 * WooCommerce dependency checks.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checks WooCommerce availability and renders native WP notices.
 */
final class Didban_WooCommerce {

	/**
	 * Determine whether WooCommerce is installed and active.
	 */
	public static function is_active(): bool {
		return class_exists( 'WooCommerce' ) && function_exists( 'WC' );
	}

	/**
	 * Render WordPress-native admin notice when WooCommerce is missing.
	 */
	public static function render_missing_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Didban requires WooCommerce to be installed and active.', 'didban' )
		);
	}
}