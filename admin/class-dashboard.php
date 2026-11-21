<?php
/**
 * Dashboard controller.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prepares data and renders the Persian Didban dashboard.
 */
final class Didban_Dashboard {

	/**
	 * Render the dashboard view.
	 */
	public function render(): void {
		Didban_Capabilities::verify_access();

		$stats = array(
			'total_products'          => 0,
			'total_variations'        => 0,
			'missing_price'           => 0,
			'missing_sku'             => 0,
			'missing_image'           => 0,
			'without_active_vars'     => 0,
			'duplicate_combinations'  => 0,
			'last_scan_date'          => null,
			'last_scan_status'        => 'idle',
		);

		$has_scan = false;

		require DIDBAN_PATH . 'admin/views/partials/header.php';
		require DIDBAN_PATH . 'admin/views/dashboard.php';
		require DIDBAN_PATH . 'admin/views/partials/footer.php';
	}
}