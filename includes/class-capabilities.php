<?php
/**
 * Capability checks and access control.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages Didban permissions.
 */
final class Didban_Capabilities {

	/**
	 * Default capability required to access Didban.
	 */
	public const MANAGE_CAPABILITY = 'manage_woocommerce';

	/**
	 * Get the required capability.
	 */
	public static function get_manage_capability(): string {
		return (string) apply_filters( 'didban_manage_capability', self::MANAGE_CAPABILITY );
	}

	/**
	 * Check whether the current user can access Didban.
	 */
	public static function current_user_can_manage(): bool {
		return current_user_can( self::get_manage_capability() );
	}

	/**
	 * Abort execution if current user lacks required capability.
	 */
	public static function verify_access(): void {
		if ( ! self::current_user_can_manage() ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'didban' ),
				esc_html__( 'Access Denied', 'didban' ),
				array( 'response' => 403 )
			);
		}
	}
}