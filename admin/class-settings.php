<?php
/**
 * Settings controller and sanitization handler.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages Didban configuration options and renders the Persian settings page.
 */
final class Didban_Settings {

	/**
	 * WordPress option key for plugin settings.
	 */
	public const OPTION_KEY = 'didban_settings';

	/**
	 * Nonce actions.
	 */
	private const SAVE_NONCE_ACTION    = 'didban_save_settings';
	private const CLEANUP_NONCE_ACTION = 'didban_manual_cleanup';

	/**
	 * Safe limits for batch size and retention.
	 */
	private const MIN_BATCH_SIZE     = 10;
	private const MAX_BATCH_SIZE     = 200;
	private const MIN_RETENTION_DAYS = 1;
	private const MAX_RETENTION_DAYS = 365;

	/**
	 * Get normalized plugin settings with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$defaults = array(
			'include_draft'   => false,
			'include_private' => false,
			'batch_size'      => 50,
			'retention_days'  => 30,
			'enabled_checks'  => array(
				Didban_Price_Check::ISSUE_TYPE         => true,
				Didban_SKU_Check::ISSUE_TYPE           => true,
				Didban_Image_Check::ISSUE_TYPE         => true,
				Didban_Empty_Product_Check::ISSUE_TYPE => true,
				Didban_Duplicate_Check::ISSUE_TYPE     => true,
			),
		);

		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$merged                   = array_merge( $defaults, $stored );
		$merged['enabled_checks'] = array_merge(
			$defaults['enabled_checks'],
			is_array( $stored['enabled_checks'] ?? null ) ? $stored['enabled_checks'] : array()
		);

		return $merged;
	}

	/**
	 * Handle POST actions and render the settings view.
	 */
	public function render(): void {
		Didban_Capabilities::verify_access();

		$notice = null;

		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$notice = $this->handle_post_request();
		}

		$settings     = self::get_settings();
		$scanner      = new Didban_Scanner( Didban::instance()->scan_manager() );
		$scan_status  = $scanner->get_status();
		$status_label = Didban_Dashboard::get_status_label( $scan_status );
		$current_page = 'settings';

		require DIDBAN_PATH . 'admin/views/partials/header.php';
		require DIDBAN_PATH . 'admin/views/settings.php';
		require DIDBAN_PATH . 'admin/views/partials/footer.php';
	}

	/**
	 * Process settings save or manual cleanup POST submissions.
	 *
	 * @return array{type: string, message: string}|null
	 */
	private function handle_post_request(): ?array {
		// 1. Save Settings.
		if ( isset( $_POST['didban_settings_nonce'] ) ) {
			check_admin_referer( self::SAVE_NONCE_ACTION, 'didban_settings_nonce' );

			$sanitized = $this->sanitize_post_settings( $_POST );
			update_option( self::OPTION_KEY, $sanitized, false );

			// Apply retention policy immediately on save.
			$repository = new Didban_Issue_Repository();
			$repository->delete_older_than( (int) $sanitized['retention_days'] );

			return array(
				'type'    => 'success',
				'message' => 'تنظیمات دیدبان با موفقیت ذخیره شد.',
			);
		}

		// 2. Manual Cleanup.
		if ( isset( $_POST['didban_cleanup_nonce'] ) ) {
			check_admin_referer( self::CLEANUP_NONCE_ACTION, 'didban_cleanup_nonce' );

			$repository = new Didban_Issue_Repository();
			$scanner    = new Didban_Scanner( Didban::instance()->scan_manager(), $repository );

			$scanner->reset_scan();
			$repository->clear_all();

			return array(
				'type'    => 'success',
				'message' => 'تمام نتایج و سوابق بررسی‌های قبلی با موفقیت پاک‌سازی شد.',
			);
		}

		return null;
	}

	/**
	 * Validate and sanitize submitted settings.
	 *
	 * @param array<string, mixed> $input Raw POST array.
	 * @return array<string, mixed>
	 */
	private function sanitize_post_settings( array $input ): array {
		$batch_size = isset( $input['batch_size'] ) ? absint( $input['batch_size'] ) : 50;
		$batch_size = max( self::MIN_BATCH_SIZE, min( self::MAX_BATCH_SIZE, $batch_size ) );

		$retention_days = isset( $input['retention_days'] ) ? absint( $input['retention_days'] ) : 30;
		$retention_days = max( self::MIN_RETENTION_DAYS, min( self::MAX_RETENTION_DAYS, $retention_days ) );

		$check_keys = array(
			Didban_Price_Check::ISSUE_TYPE,
			Didban_SKU_Check::ISSUE_TYPE,
			Didban_Image_Check::ISSUE_TYPE,
			Didban_Empty_Product_Check::ISSUE_TYPE,
			Didban_Duplicate_Check::ISSUE_TYPE,
		);

		$raw_checks     = is_array( $input['enabled_checks'] ?? null ) ? $input['enabled_checks'] : array();
		$enabled_checks = array();

		foreach ( $check_keys as $key ) {
			$enabled_checks[ $key ] = ! empty( $raw_checks[ $key ] );
		}

		return array(
			'include_draft'   => ! empty( $input['include_draft'] ),
			'include_private' => ! empty( $input['include_private'] ),
			'batch_size'      => $batch_size,
			'retention_days'  => $retention_days,
			'enabled_checks'  => $enabled_checks,
		);
	}
}