<?php
/**
 * Issue persistence and query repository.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles CRUD, filtering, pagination, and cleanup for detected issues.
 */
final class Didban_Issue_Repository {

	/**
	 * Option key for the latest completed scan summary.
	 */
	public const LAST_COMPLETED_OPTION = 'didban_last_completed_scan';

	/**
	 * Insert a batch of standardized issues for a scan.
	 *
	 * @param string                           $scan_id Scan UUID.
	 * @param array<int, array<string, mixed>> $issues  List of issue arrays.
	 * @return int Number of inserted rows.
	 */
	public function insert_many( string $scan_id, array $issues ): int {
		global $wpdb;

		if ( '' === $scan_id || empty( $issues ) ) {
			return 0;
		}

		$table    = Didban_Database::get_issues_table();
		$inserted = 0;

		foreach ( $issues as $issue ) {
			$result = $wpdb->insert(
				$table,
				array(
					'scan_id'            => $scan_id,
					'product_id'         => (int) ( $issue['product_id'] ?? 0 ),
					'variation_id'       => (int) ( $issue['variation_id'] ?? 0 ),
					'product_name'       => mb_substr( sanitize_text_field( (string) ( $issue['product_name'] ?? '' ) ), 0, 255 ),
					'sku'                => mb_substr( sanitize_text_field( (string) ( $issue['sku'] ?? '' ) ), 0, 100 ),
					'attributes_summary' => sanitize_text_field( (string) ( $issue['attributes_summary'] ?? '' ) ),
					'issue_type'         => sanitize_key( (string) ( $issue['issue_type'] ?? '' ) ),
					'message'            => sanitize_text_field( (string) ( $issue['message'] ?? '' ) ),
					'detected_at'        => sanitize_text_field( (string) ( $issue['detected_at'] ?? current_time( 'mysql', true ) ) ),
				),
				array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false !== $result ) {
				++$inserted;
			}
		}

		return $inserted;
	}

	/**
	 * Get issue counts grouped by issue_type for a scan.
	 *
	 * @param string|null $scan_id Scan UUID (defaults to latest completed scan).
	 * @return array<string, int>
	 */
	public function get_counts_by_type( ?string $scan_id = null ): array {
		global $wpdb;

		$scan_id = $scan_id ?? $this->get_active_scan_id();

		$counts = array(
			Didban_Price_Check::ISSUE_TYPE         => 0,
			Didban_SKU_Check::ISSUE_TYPE           => 0,
			Didban_Image_Check::ISSUE_TYPE         => 0,
			Didban_Empty_Product_Check::ISSUE_TYPE => 0,
			Didban_Duplicate_Check::ISSUE_TYPE     => 0,
			'total'                                => 0,
		);

		if ( '' === $scan_id ) {
			return $counts;
		}

		$table = Didban_Database::get_issues_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT issue_type, COUNT(*) AS cnt FROM {$table} WHERE scan_id = %s GROUP BY issue_type",
				$scan_id
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return $counts;
		}

		$total = 0;
		foreach ( $rows as $row ) {
			$type = (string) $row['issue_type'];
			$cnt  = (int) $row['cnt'];

			$counts[ $type ] = $cnt;
			$total          += $cnt;
		}

		$counts['total'] = $total;

		return $counts;
	}

	/**
	 * Query issues with filtering, search, sorting, and server-side pagination.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<string, mixed>
	 */
	public function query( array $args = array() ): array {
		global $wpdb;

		$scan_id  = (string) ( $args['scan_id'] ?? $this->get_active_scan_id() );
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = (int) ( $args['per_page'] ?? 20 );

		$empty_result = array(
			'items'    => array(),
			'total'    => 0,
			'pages'    => 0,
			'page'     => $page,
			'per_page' => $per_page,
		);

		if ( '' === $scan_id ) {
			return $empty_result;
		}

		$table  = Didban_Database::get_issues_table();
		$where  = array( 'scan_id = %s' );
		$params = array( $scan_id );

		if ( ! empty( $args['issue_type'] ) ) {
			$where[]  = 'issue_type = %s';
			$params[] = sanitize_key( (string) $args['issue_type'] );
		}

		if ( ! empty( $args['product_id'] ) ) {
			$where[]  = 'product_id = %d';
			$params[] = (int) $args['product_id'];
		}

		if ( ! empty( $args['variation_id'] ) ) {
			$where[]  = 'variation_id = %d';
			$params[] = (int) $args['variation_id'];
		}

		if ( ! empty( $args['category_id'] ) ) {
			$cat_product_ids = $this->get_product_ids_by_category( (int) $args['category_id'] );
			if ( empty( $cat_product_ids ) ) {
				return $empty_result;
			}
			$placeholders = implode( ',', array_fill( 0, count( $cat_product_ids ), '%d' ) );
			$where[]      = "product_id IN ({$placeholders})";
			$params       = array_merge( $params, $cat_product_ids );
		}

		if ( ! empty( $args['search'] ) ) {
			$search_raw = trim( (string) $args['search'] );
			$like       = '%' . $wpdb->esc_like( $search_raw ) . '%';

			if ( ctype_digit( $search_raw ) ) {
				$numeric_id = (int) $search_raw;
				$where[]    = '(product_id = %d OR variation_id = %d OR product_name LIKE %s OR sku LIKE %s)';
				$params[]   = $numeric_id;
				$params[]   = $numeric_id;
				$params[]   = $like;
				$params[]   = $like;
			} else {
				$where[]  = '(product_name LIKE %s OR sku LIKE %s OR attributes_summary LIKE %s)';
				$params[] = $like;
				$params[] = $like;
				$params[] = $like;
			}
		}

		$where_sql = implode( ' AND ', $where );

		// Count matching rows.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $params ) );

		if ( 0 === $total ) {
			return $empty_result;
		}

		$allowed_orderby = array(
			'id'           => 'id',
			'product_id'   => 'product_id',
			'variation_id' => 'variation_id',
			'product_name' => 'product_name',
			'sku'          => 'sku',
			'issue_type'   => 'issue_type',
			'detected_at'  => 'detected_at',
		);

		$orderby_key = (string) ( $args['orderby'] ?? 'id' );
		$orderby     = $allowed_orderby[ $orderby_key ] ?? 'id';
		$order       = 'ASC' === strtoupper( (string) ( $args['order'] ?? 'DESC' ) ) ? 'ASC' : 'DESC';

		if ( $per_page > 0 ) {
			$offset       = ( $page - 1 ) * $per_page;
			$limit_sql    = 'LIMIT %d OFFSET %d';
			$query_params = array_merge( $params, array( $per_page, $offset ) );
			$pages        = (int) ceil( $total / $per_page );
		} else {
			$limit_sql    = '';
			$query_params = $params;
			$pages        = 1;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} {$limit_sql}",
				$query_params
			),
			ARRAY_A
		);

		return array(
			'items'    => is_array( $items ) ? $items : array(),
			'total'    => $total,
			'pages'    => $pages,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Delete all issues belonging to a specific scan ID.
	 *
	 * @param string $scan_id Scan UUID.
	 */
	public function delete_by_scan( string $scan_id ): int {
		global $wpdb;

		if ( '' === $scan_id ) {
			return 0;
		}

		$table = Didban_Database::get_issues_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE scan_id = %s", $scan_id ) );

		return false !== $deleted ? (int) $deleted : 0;
	}

	/**
	 * Replace outdated scan results by deleting all rows except the given scan ID.
	 *
	 * @param string $scan_id Newly completed scan UUID.
	 */
	public function delete_except_scan( string $scan_id ): int {
		global $wpdb;

		if ( '' === $scan_id ) {
			return 0;
		}

		$table = Didban_Database::get_issues_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE scan_id != %s", $scan_id ) );

		return false !== $deleted ? (int) $deleted : 0;
	}

	/**
	 * Safely delete issues older than the given number of days.
	 *
	 * @param int $days Retention period in days.
	 */
	public function delete_older_than( int $days ): int {
		global $wpdb;

		if ( $days < 1 ) {
			return 0;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		$table  = Didban_Database::get_issues_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE detected_at < %s", $cutoff ) );

		return false !== $deleted ? (int) $deleted : 0;
	}

	/**
	 * Delete all stored issues and clear last completed scan metadata.
	 */
	public function clear_all(): void {
		global $wpdb;

		$table = Didban_Database::get_issues_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "TRUNCATE TABLE {$table}" );
		delete_option( self::LAST_COMPLETED_OPTION );
	}

	/**
	 * Record a completed scan as the active scan for dashboard & reports.
	 *
	 * @param array<string, mixed> $scan_state Completed scan state.
	 */
	public function save_last_completed_scan( array $scan_state ): void {
		update_option(
			self::LAST_COMPLETED_OPTION,
			array(
				'scan_id'              => (string) ( $scan_state['scan_id'] ?? '' ),
				'status'               => Didban_Scanner::STATUS_COMPLETED,
				'total_products'       => (int) ( $scan_state['total_products'] ?? 0 ),
				'total_variations'     => (int) ( $scan_state['processed_variations'] ?? 0 ),
				'started_at'           => $scan_state['started_at'] ?? null,
				'completed_at'         => $scan_state['completed_at'] ?? current_time( 'mysql', true ),
			),
			false
		);
	}

	/**
	 * Retrieve the latest completed scan metadata.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_last_completed_scan(): ?array {
		$data = get_option( self::LAST_COMPLETED_OPTION, null );

		return is_array( $data ) && ! empty( $data['scan_id'] ) ? $data : null;
	}

	/**
	 * Get the active completed scan ID.
	 */
	public function get_active_scan_id(): string {
		$last = $this->get_last_completed_scan();

		return $last ? (string) $last['scan_id'] : '';
	}

	/**
	 * Resolve variable product IDs belonging to a WooCommerce category.
	 *
	 * @param int $category_id Term ID in product_cat.
	 * @return array<int, int>
	 */
	private function get_product_ids_by_category( int $category_id ): array {
		$term = get_term( $category_id, 'product_cat' );
		if ( ! $term instanceof WP_Term ) {
			return array();
		}

		$ids = wc_get_products(
			array(
				'type'     => 'variable',
				'category' => array( $term->slug ),
				'limit'    => -1,
				'return'   => 'ids',
			)
		);

		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}
}