<?php
/**
 * Issues management and filtering controller.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles server-side filtering, search, sorting, and pagination of detected issues.
 */
final class Didban_Issues_Page {

	/**
	 * Render the issues management view.
	 */
	public function render(): void {
		Didban_Capabilities::verify_access();

		$repository     = new Didban_Issue_Repository();
		$scanner        = new Didban_Scanner( Didban::instance()->scan_manager(), $repository );
		$last_completed = $repository->get_last_completed_scan();
		$has_scan       = null !== $last_completed;

		$scan_status   = $scanner->get_status();
		$scan_progress = $scanner->get_progress();
		$is_scanning   = in_array( $scan_status, array( Didban_Scanner::STATUS_PENDING, Didban_Scanner::STATUS_RUNNING ), true );
		$status_label  = Didban_Dashboard::get_status_label( $scan_status );

		$filters = $this->parse_filters();

		$result = $has_scan
			? $repository->query(
				array(
					'issue_type'  => $filters['issue_type'],
					'category_id' => $filters['category_id'],
					'search'      => $filters['search'],
					'orderby'     => $filters['orderby'],
					'order'       => $filters['order'],
					'page'        => $filters['paged'],
					'per_page'    => $filters['per_page'],
				)
			)
			: array(
				'items'    => array(),
				'total'    => 0,
				'pages'    => 0,
				'page'     => 1,
				'per_page' => $filters['per_page'],
			);

		$categories   = $this->get_product_categories();
		$issue_labels = Didban_Dashboard::get_issue_type_labels();
		$current_page = 'issues';

		require DIDBAN_PATH . 'admin/views/partials/header.php';
		require DIDBAN_PATH . 'admin/views/issues.php';
		require DIDBAN_PATH . 'admin/views/partials/footer.php';
	}

	/**
	 * Parse and sanitize GET filter parameters.
	 *
	 * @return array<string, mixed>
	 */
	private function parse_filters(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only GET filtering.
		$search      = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['search'] ) ) : '';
		$issue_type  = isset( $_GET['issue_type'] ) ? sanitize_key( (string) $_GET['issue_type'] ) : '';
		$category_id = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0;
		$orderby     = isset( $_GET['orderby'] ) ? sanitize_key( (string) $_GET['orderby'] ) : 'id';
		$order       = isset( $_GET['order'] ) ? strtoupper( sanitize_key( (string) $_GET['order'] ) ) : 'DESC';
		$paged       = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page    = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 20;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$allowed_types = array_keys( Didban_Dashboard::get_issue_type_labels() );
		if ( '' !== $issue_type && ! in_array( $issue_type, $allowed_types, true ) ) {
			$issue_type = '';
		}

		$allowed_orderby = array( 'id', 'product_id', 'variation_id', 'product_name', 'sku', 'issue_type', 'detected_at' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'id';
		}

		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		if ( ! in_array( $per_page, array( 20, 50, 100 ), true ) ) {
			$per_page = 20;
		}

		return array(
			'search'      => trim( $search ),
			'issue_type'  => $issue_type,
			'category_id' => $category_id,
			'orderby'     => $orderby,
			'order'       => $order,
			'paged'       => $paged,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Fetch WooCommerce product categories for filter dropdown.
	 *
	 * @return array<int, WP_Term>
	 */
	private function get_product_categories(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		return is_array( $terms ) ? $terms : array();
	}
}