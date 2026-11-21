<?php
/**
 * AJAX endpoints for scan execution and progress polling.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles authenticated AJAX requests from the Didban panel.
 */
final class Didban_Ajax {

	/**
	 * Nonce action name.
	 */
	public const NONCE_ACTION = 'didban_scan_nonce';

	/**
	 * Register WordPress AJAX hooks.
	 */
	public function init(): void {
		add_action( 'wp_ajax_didban_start_scan', array( $this, 'handle_start_scan' ) );
		add_action( 'wp_ajax_didban_scan_status', array( $this, 'handle_scan_status' ) );
	}

	/**
	 * Start a new scan via AJAX.
	 */
    public function handle_start_scan(): void {
		$this->verify_request();

		$settings = Didban_Settings::get_settings();
		$statuses = array( 'publish' );

		if ( ! empty( $settings['include_draft'] ) ) {
			$statuses[] = 'draft';
		}
		if ( ! empty( $settings['include_private'] ) ) {
			$statuses[] = 'private';
		}

		$scanner = new Didban_Scanner( Didban::instance()->scan_manager() );
		$result  = $scanner->start_scan(
			(int) $settings['batch_size'],
			array(
				'background'     => true,
				'statuses'       => $statuses,
				'enabled_checks' => (array) $settings['enabled_checks'],
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
				),
				400
			);
		}

		wp_send_json_success( $this->format_progress_payload( $scanner ) );
	}

	/**
	 * Poll scan progress (and advance one batch if needed when WP-Cron/AS is idle).
	 */
	public function handle_scan_status(): void {
		$this->verify_request();

		$scanner = new Didban_Scanner( Didban::instance()->scan_manager() );
		$status  = $scanner->get_status();

		if ( in_array( $status, array( Didban_Scanner::STATUS_PENDING, Didban_Scanner::STATUS_RUNNING ), true ) ) {
			// Safe due to internal transient batch lock; ensures progress even if WP-Cron is delayed locally.
			$scanner->process_batch();
		}

		wp_send_json_success( $this->format_progress_payload( $scanner ) );
	}

	/**
	 * Verify nonce, capability, and WooCommerce availability.
	 */
	private function verify_request(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! Didban_Capabilities::current_user_can_manage() ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'didban' ) ),
				403
			);
		}

		if ( ! Didban_WooCommerce::is_active() ) {
			wp_send_json_error(
				array( 'message' => __( 'WooCommerce is not active.', 'didban' ) ),
				400
			);
		}
	}

	/**
	 * Build normalized JSON payload for frontend progress UI.
	 *
	 * @param Didban_Scanner $scanner Scanner instance.
	 * @return array<string, mixed>
	 */
	private function format_progress_payload( Didban_Scanner $scanner ): array {
		$state = $scanner->get_state();

		return array(
			'scan_id'              => $state['scan_id'],
			'status'               => $state['status'],
			'progress'             => $scanner->get_progress(),
			'processed_products'   => (int) $state['processed_products'],
			'total_products'       => (int) $state['total_products'],
			'processed_variations' => (int) $state['processed_variations'],
			'issues_detected'      => (int) $state['issues_detected'],
			'error_message'        => (string) $state['error_message'],
		);
	}
}