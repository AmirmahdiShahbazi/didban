<?php
/**
 * Dashboard controller.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prepares real scan statistics and renders the Persian Didban dashboard.
 */
final class Didban_Dashboard {

	/**
	 * Render the dashboard view.
	 */
	public function render(): void {
		Didban_Capabilities::verify_access();

		$scanner    = new Didban_Scanner( Didban::instance()->scan_manager() );
		$repository = new Didban_Issue_Repository();

		$scan_state     = $scanner->get_state();
		$scan_status    = $scanner->get_status();
		$scan_progress  = $scanner->get_progress();
		$last_completed = $repository->get_last_completed_scan();
		$issue_counts   = $repository->get_counts_by_type();

		$total_products   = $last_completed ? (int) $last_completed['total_products'] : 0;
		$total_variations = $last_completed ? (int) $last_completed['total_variations'] : 0;
		$total_issues     = (int) ( $issue_counts['total'] ?? 0 );

		$stats = array(
			'total_products'         => $total_products,
			'total_variations'       => $total_variations,
			'missing_price'          => (int) ( $issue_counts[ Didban_Price_Check::ISSUE_TYPE ] ?? 0 ),
			'missing_sku'            => (int) ( $issue_counts[ Didban_SKU_Check::ISSUE_TYPE ] ?? 0 ),
			'missing_image'          => (int) ( $issue_counts[ Didban_Image_Check::ISSUE_TYPE ] ?? 0 ),
			'without_active_vars'    => (int) ( $issue_counts[ Didban_Empty_Product_Check::ISSUE_TYPE ] ?? 0 ),
			'duplicate_combinations' => (int) ( $issue_counts[ Didban_Duplicate_Check::ISSUE_TYPE ] ?? 0 ),
			'total_issues'           => $total_issues,
			'last_scan_date'         => $last_completed ? $this->format_persian_datetime( (string) $last_completed['completed_at'] ) : null,
			'last_scan_status'       => $scan_status,
			'scan_error'             => (string) ( $scan_state['error_message'] ?? '' ),
		);

		$has_scan      = null !== $last_completed;
		$is_scanning   = in_array( $scan_status, array( Didban_Scanner::STATUS_PENDING, Didban_Scanner::STATUS_RUNNING ), true );
		$health_score  = $this->calculate_health_score( $total_variations, $total_issues, $has_scan );
		$latest_issues = $has_scan
			? $repository->query(
				array(
					'per_page' => 10,
					'orderby'  => 'id',
					'order'    => 'DESC',
				)
			)['items']
			: array();

		$issue_labels = self::get_issue_type_labels();
		$status_label = self::get_status_label( $scan_status );

		require DIDBAN_PATH . 'admin/views/partials/header.php';
		require DIDBAN_PATH . 'admin/views/dashboard.php';
		require DIDBAN_PATH . 'admin/views/partials/footer.php';
	}

	/**
	 * Map internal issue_type keys to Persian labels and badge styles.
	 *
	 * @return array<string, array{label: string, badge: string}>
	 */
	public static function get_issue_type_labels(): array {
		return array(
			Didban_Price_Check::ISSUE_TYPE         => array(
				'label' => 'قیمت ثبت نشده',
				'badge' => 'badge-rose',
			),
			Didban_SKU_Check::ISSUE_TYPE           => array(
				'label' => 'کد SKU ثبت نشده',
				'badge' => 'badge-amber',
			),
			Didban_Image_Check::ISSUE_TYPE         => array(
				'label' => 'تصویر اختصاصی ندارد',
				'badge' => 'badge-violet',
			),
			Didban_Empty_Product_Check::ISSUE_TYPE => array(
				'label' => 'بدون تنوع فعال',
				'badge' => 'badge-crimson',
			),
			Didban_Duplicate_Check::ISSUE_TYPE     => array(
				'label' => 'ترکیب ویژگی تکراری',
				'badge' => 'badge-indigo',
			),
		);
	}

	/**
	 * Map internal scan status to Persian label.
	 *
	 * @param string $status Internal scan status.
	 */
	public static function get_status_label( string $status ): string {
		return match ( $status ) {
			Didban_Scanner::STATUS_PENDING   => 'در صف انتظار',
			Didban_Scanner::STATUS_RUNNING   => 'در حال بررسی...',
			Didban_Scanner::STATUS_COMPLETED => 'تکمیل شده',
			Didban_Scanner::STATUS_FAILED    => 'خطا در بررسی',
			default                          => 'آماده به کار',
		};
	}

	/**
	 * Format UTC MySQL datetime into WordPress local date/time.
	 *
	 * @param string $utc_datetime MySQL datetime in UTC.
	 */
	private function format_persian_datetime( string $utc_datetime ): string {
		$timestamp = strtotime( $utc_datetime . ' UTC' );
		if ( false === $timestamp ) {
			return '—';
		}

		return wp_date( 'Y/m/d - H:i', $timestamp );
	}

	/**
	 * Compute a simple variation health percentage for the hero ring.
	 *
	 * @param int  $total_variations Total variations scanned.
	 * @param int  $total_issues     Total detected issues.
	 * @param bool $has_scan         Whether a completed scan exists.
	 */
	private function calculate_health_score( int $total_variations, int $total_issues, bool $has_scan ): ?int {
		if ( ! $has_scan ) {
			return null;
		}

		if ( 0 === $total_variations ) {
			return 0 === $total_issues ? 100 : 0;
		}

		$ratio = min( 1.0, $total_issues / max( 1, $total_variations ) );

		return (int) max( 0, round( ( 1 - $ratio ) * 100 ) );
	}
}