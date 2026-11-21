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
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'didban_process_scan_batch', array(), 'didban' );
		}
	}
}