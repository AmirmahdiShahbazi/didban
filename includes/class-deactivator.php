<?php
/**
 * Plugin deactivation handler.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation tasks.
 */
final class Didban_Deactivator {

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		// Reserved for scheduled action cleanup in later phases.
	}
}