<?php
/**
 * Core WooCommerce variable product scanner engine.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inspects variable products and their variations in memory-safe batches.
 */
final class Didban_Scanner {

	/**
	 * Scan lifecycle statuses.
	 */
	public const STATUS_IDLE      = 'idle';
	public const STATUS_PENDING   = 'pending';
	public const STATUS_RUNNING   = 'running';
	public const STATUS_COMPLETED = 'completed';
	public const STATUS_FAILED    = 'failed';

	/**
	 * WordPress option key for scan state metadata.
	 */
	public const STATE_OPTION = 'didban_scan_state';

	/**
	 * Transient key for preventing simultaneous batch execution.
	 */
	private const BATCH_LOCK_TRANSIENT = 'didban_scan_batch_lock';

	/**
	 * Maximum allowed batch size to protect memory.
	 */
	private const MAX_BATCH_SIZE = 200;

	/**
	 * Default batch size.
	 */
	private const DEFAULT_BATCH_SIZE = 50;

	/**
	 * Stale scan timeout in seconds (15 minutes).
	 */
	private const STALE_TIMEOUT_SECONDS = 900;

	/**
	 * Scan manager for background scheduling.
	 *
	 * @var Didban_Scan_Manager|null
	 */
	private ?Didban_Scan_Manager $scan_manager;

	/**
	 * Issue repository for persisting scan results.
	 *
	 * @var Didban_Issue_Repository
	 */
	private Didban_Issue_Repository $repository;

	/**
	 * Constructor.
	 *
	 * @param Didban_Scan_Manager|null     $scan_manager Optional background scan manager.
	 * @param Didban_Issue_Repository|null $repository   Optional issue repository.
	 */
	public function __construct(
		?Didban_Scan_Manager $scan_manager = null,
		?Didban_Issue_Repository $repository = null
	) {
		$this->scan_manager = $scan_manager;
		$this->repository   = $repository ?? new Didban_Issue_Repository();
	}

	/**
	 * Start a new catalog scan.
	 *
	 * @param int                  $batch_size Number of variable products per batch.
	 * @param array<string, mixed> $args       Optional scan configuration.
	 * @return array<string, mixed>|WP_Error Initialized scan state or WP_Error on failure.
	 */
	public function start_scan( int $batch_size = self::DEFAULT_BATCH_SIZE, array $args = array() ): array|WP_Error {
		if ( ! Didban_WooCommerce::is_active() ) {
			return new WP_Error(
				'didban_woocommerce_inactive',
				__( 'WooCommerce must be active to run a scan.', 'didban' )
			);
		}

		if ( $this->is_scan_in_progress() ) {
			return new WP_Error(
				'didban_scan_in_progress',
				__( 'A scan is already pending or running.', 'didban' )
			);
		}

		$batch_size       = $this->sanitize_batch_size( $batch_size );
		$product_statuses = $this->sanitize_statuses( $args['statuses'] ?? array( 'publish' ) );
		$use_background   = (bool) ( $args['background'] ?? true );
		$enabled_checks   = $this->sanitize_enabled_checks( $args['enabled_checks'] ?? null );

		try {
			$total_products = $this->count_variable_products( $product_statuses );
			$now            = current_time( 'mysql', true );
			$scan_id        = wp_generate_uuid4();

			$state = array(
				'scan_id'              => $scan_id,
				'status'               => 0 === $total_products ? self::STATUS_COMPLETED : self::STATUS_PENDING,
				'total_products'       => $total_products,
				'processed_products'   => 0,
				'total_variations'     => 0,
				'processed_variations' => 0,
				'issues_detected'      => 0,
				'current_page'         => 1,
				'batch_size'           => $batch_size,
				'product_statuses'     => $product_statuses,
				'enabled_checks'       => $enabled_checks,
				'background'           => $use_background,
				'started_at'           => $now,
				'updated_at'           => $now,
				'completed_at'         => 0 === $total_products ? $now : null,
				'error_message'        => '',
			);

			$this->save_state( $state );

			if ( 0 === $total_products ) {
				$this->finalize_completed_scan( $state );
			} elseif ( $use_background && null !== $this->scan_manager ) {
				$this->scan_manager->schedule_next_batch( $scan_id, $batch_size );
			}

			return $state;
		} catch ( Throwable $e ) {
			return $this->fail_scan( $e->getMessage() );
		}
	}

	/**
	 * Process a single batch of variable products and their variations.
	 *
	 * @param int $batch_size Optional override for batch size.
	 * @return array<string, mixed>|WP_Error Structured batch result or WP_Error.
	 */
	public function process_batch( int $batch_size = 0 ): array|WP_Error {
		if ( ! Didban_WooCommerce::is_active() ) {
			return new WP_Error(
				'didban_woocommerce_inactive',
				__( 'WooCommerce must be active to process a scan batch.', 'didban' )
			);
		}

		if ( ! $this->acquire_batch_lock() ) {
			return new WP_Error(
				'didban_batch_locked',
				__( 'Another scan batch is currently being processed.', 'didban' )
			);
		}

		try {
			$state = $this->get_state();

			if ( ! in_array( $state['status'], array( self::STATUS_PENDING, self::STATUS_RUNNING ), true ) ) {
				$this->release_batch_lock();

				return new WP_Error(
					'didban_scan_not_active',
					__( 'No active scan is waiting to be processed.', 'didban' )
				);
			}

			$effective_batch_size = $batch_size > 0
				? $this->sanitize_batch_size( $batch_size )
				: $this->sanitize_batch_size( (int) $state['batch_size'] );

			$state['status']     = self::STATUS_RUNNING;
			$state['updated_at'] = current_time( 'mysql', true );
			$this->save_state( $state );

			$page        = max( 1, (int) $state['current_page'] );
			$product_ids = $this->fetch_variable_product_ids(
				$page,
				$effective_batch_size,
				(array) $state['product_statuses']
			);

			$inspected_products  = array();
			$batch_issues        = array();
			$batch_variation_cnt = 0;

			foreach ( $product_ids as $product_id ) {
				$result = $this->inspect_and_detect( (int) $product_id, $state );

				if ( null !== $result ) {
					$inspected_products[] = $result['product'];
					$batch_variation_cnt += count( $result['product']['variations'] );

					if ( ! empty( $result['issues'] ) ) {
						$batch_issues = array_merge( $batch_issues, $result['issues'] );
					}
				}
			}

			$inserted_count = $this->repository->insert_many( (string) $state['scan_id'], $batch_issues );

			$processed_in_batch              = count( $product_ids );
			$state['processed_products']    += $processed_in_batch;
			$state['processed_variations']  += $batch_variation_cnt;
			$state['total_variations']       = $state['processed_variations'];
			$state['issues_detected']       += $inserted_count;
			$state['current_page']           = $page + 1;
			$state['updated_at']             = current_time( 'mysql', true );

			$is_finished = ( 0 === $processed_in_batch )
				|| ( $state['processed_products'] >= $state['total_products'] )
				|| ( $processed_in_batch < $effective_batch_size );

			if ( $is_finished ) {
				$state['status']       = self::STATUS_COMPLETED;
				$state['completed_at'] = current_time( 'mysql', true );
			}

			$this->save_state( $state );

			if ( $is_finished ) {
				$this->finalize_completed_scan( $state );
			}

			$this->release_batch_lock();

			if ( ! $is_finished && ! empty( $state['background'] ) && null !== $this->scan_manager ) {
				$this->scan_manager->schedule_next_batch( (string) $state['scan_id'], $effective_batch_size );
			}

			return array(
				'scan_id'              => $state['scan_id'],
				'status'               => $state['status'],
				'batch_page'           => $page,
				'processed_in_batch'   => $processed_in_batch,
				'processed_products'   => $state['processed_products'],
				'total_products'       => $state['total_products'],
				'processed_variations' => $state['processed_variations'],
				'issues_in_batch'      => $inserted_count,
				'progress'             => $this->calculate_progress( $state ),
				'products'             => $inspected_products,
			);
		} catch ( Throwable $e ) {
			$this->release_batch_lock();
			return $this->fail_scan( $e->getMessage() );
		}
	}

	/**
	 * Get scan progress percentage (0 to 100).
	 */
	public function get_progress(): float {
		return $this->calculate_progress( $this->get_state() );
	}

	/**
	 * Get current scan lifecycle status.
	 */
	public function get_status(): string {
		$state = $this->get_state();

		return (string) $state['status'];
	}

	/**
	 * Get full normalized scan state.
	 *
	 * @return array<string, mixed>
	 */
	public function get_state(): array {
		$defaults = array(
			'scan_id'              => '',
			'status'               => self::STATUS_IDLE,
			'total_products'       => 0,
			'processed_products'   => 0,
			'total_variations'     => 0,
			'processed_variations' => 0,
			'issues_detected'      => 0,
			'current_page'         => 1,
			'batch_size'           => self::DEFAULT_BATCH_SIZE,
			'product_statuses'     => array( 'publish' ),
			'enabled_checks'       => $this->get_default_checks(),
			'background'           => true,
			'started_at'           => null,
			'updated_at'           => null,
			'completed_at'         => null,
			'error_message'        => '',
		);

		$stored = get_option( self::STATE_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( $defaults, $stored );
	}

	/**
	 * Determine whether a scan is currently active and not stale.
	 */
	public function is_scan_in_progress(): bool {
		$state = $this->get_state();

		if ( ! in_array( $state['status'], array( self::STATUS_PENDING, self::STATUS_RUNNING ), true ) ) {
			return false;
		}

		if ( ! empty( $state['updated_at'] ) ) {
			$updated_ts = strtotime( (string) $state['updated_at'] . ' UTC' );
			if ( false !== $updated_ts && ( time() - $updated_ts ) > self::STALE_TIMEOUT_SECONDS ) {
				$this->fail_scan( __( 'Scan timed out due to inactivity.', 'didban' ) );
				return false;
			}
		}

		return true;
	}

	/**
	 * Reset scan state back to idle and clear locks.
	 */
	public function reset_scan(): void {
		delete_option( self::STATE_OPTION );
		$this->release_batch_lock();

		if ( null !== $this->scan_manager ) {
			$this->scan_manager->unschedule_all();
		}
	}

	/**
	 * Inspect a variable product, run enabled checks, and return product data + issues.
	 *
	 * @param int                  $product_id Variable product ID.
	 * @param array<string, mixed> $scan_state Current scan state.
	 * @return array<string, mixed>|null
	 */
	private function inspect_and_detect( int $product_id, array $scan_state ): ?array {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof WC_Product_Variable ) {
			return null;
		}

		$enabled_checks       = (array) ( $scan_state['enabled_checks'] ?? $this->get_default_checks() );
		$include_non_publish  = count( (array) ( $scan_state['product_statuses'] ?? array( 'publish' ) ) ) > 1
			|| ! in_array( 'publish', (array) ( $scan_state['product_statuses'] ?? array() ), true );

		$price_check         = new Didban_Price_Check();
		$sku_check           = new Didban_SKU_Check();
		$image_check         = new Didban_Image_Check();
		$empty_product_check = new Didban_Empty_Product_Check();
		$duplicate_check     = new Didban_Duplicate_Check();

		$variation_ids = $product->get_children();
		$variations    = array();
		$issues        = array();
		$product_name  = $product->get_name();

		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( (int) $variation_id );

			if ( ! $variation instanceof WC_Product_Variation ) {
				continue;
			}

			$variation_data = array(
				'variation_id'  => $variation->get_id(),
				'parent_id'     => $product->get_id(),
				'status'        => $variation->get_status(),
				'sku'           => $variation->get_sku( 'edit' ),
				'regular_price' => $variation->get_regular_price( 'edit' ),
				'sale_price'    => $variation->get_sale_price( 'edit' ),
				'price'         => $variation->get_price( 'edit' ),
				'image_id'      => (int) $variation->get_image_id( 'edit' ),
				'attributes'    => $variation->get_attributes(),
				'manage_stock'  => $variation->get_manage_stock(),
				'stock_status'  => $variation->get_stock_status(),
			);

			$attr_summary = $this->format_attributes_summary( $variation_data['attributes'] );

			if ( ! empty( $enabled_checks[ Didban_Price_Check::ISSUE_TYPE ] ) ) {
				$issue = $price_check->check( $variation );
				if ( null !== $issue ) {
					$issues[] = $this->enrich_issue( $issue, $product_name, $variation_data['sku'], $attr_summary );
				}
			}

			if ( ! empty( $enabled_checks[ Didban_SKU_Check::ISSUE_TYPE ] ) ) {
				$issue = $sku_check->check( $variation );
				if ( null !== $issue ) {
					$issues[] = $this->enrich_issue( $issue, $product_name, '', $attr_summary );
				}
			}

			if ( ! empty( $enabled_checks[ Didban_Image_Check::ISSUE_TYPE ] ) ) {
				$issue = $image_check->check( $variation, $product );
				if ( null !== $issue ) {
					$issues[] = $this->enrich_issue( $issue, $product_name, $variation_data['sku'], $attr_summary );
				}
			}

			$variations[] = $variation_data;
			unset( $variation );
		}

		$product_data = array(
			'product_id'      => $product->get_id(),
			'name'            => $product_name,
			'status'          => $product->get_status(),
			'sku'             => $product->get_sku( 'edit' ),
			'image_id'        => (int) $product->get_image_id( 'edit' ),
			'variation_count' => count( $variations ),
			'variations'      => $variations,
		);

		if ( ! empty( $enabled_checks[ Didban_Empty_Product_Check::ISSUE_TYPE ] ) ) {
			$issue = $empty_product_check->check( $product_data, $include_non_publish );
			if ( null !== $issue ) {
				$issues[] = $this->enrich_issue( $issue, $product_name, $product_data['sku'], '' );
			}
		}

		if ( ! empty( $enabled_checks[ Didban_Duplicate_Check::ISSUE_TYPE ] ) ) {
			$dup_issues = $duplicate_check->check( $product->get_id(), $variations );
			foreach ( $dup_issues as $dup_issue ) {
				$var_id       = (int) $dup_issue['variation_id'];
				$matched_var  = $this->find_variation_in_list( $variations, $var_id );
				$var_sku      = $matched_var ? (string) $matched_var['sku'] : '';
				$var_attr_str = $matched_var ? $this->format_attributes_summary( (array) $matched_var['attributes'] ) : '';
				$issues[]     = $this->enrich_issue( $dup_issue, $product_name, $var_sku, $var_attr_str );
			}
		}

		unset( $product );

		return array(
			'product' => $product_data,
			'issues'  => $issues,
		);
	}

	/**
	 * Attach display metadata to a standardized issue array before storage.
	 *
	 * @param array<string, mixed> $issue        Standardized issue.
	 * @param string               $product_name Parent product name.
	 * @param string               $sku          Variation/product SKU.
	 * @param string               $attr_summary Formatted attribute summary.
	 * @return array<string, mixed>
	 */
	private function enrich_issue( array $issue, string $product_name, string $sku, string $attr_summary ): array {
		$issue['product_name']       = $product_name;
		$issue['sku']                = $sku;
		$issue['attributes_summary'] = $attr_summary;

		return $issue;
	}

	/**
	 * Format variation attributes into a readable string.
	 *
	 * @param array<string, mixed> $attributes Variation attributes map.
	 */
	private function format_attributes_summary( array $attributes ): string {
		if ( empty( $attributes ) ) {
			return '';
		}

		$parts = array();
		foreach ( $attributes as $key => $value ) {
			$label = wc_attribute_label( (string) $key );
			$val   = '' !== (string) $value ? urldecode( (string) $value ) : 'هر مقدار';
			$parts[] = $label . ': ' . $val;
		}

		return implode( ' | ', $parts );
	}

	/**
	 * Find a normalized variation array by variation ID.
	 *
	 * @param array<int, array<string, mixed>> $variations   List of variations.
	 * @param int                              $variation_id Target ID.
	 * @return array<string, mixed>|null
	 */
	private function find_variation_in_list( array $variations, int $variation_id ): ?array {
		foreach ( $variations as $variation ) {
			if ( (int) $variation['variation_id'] === $variation_id ) {
				return $variation;
			}
		}

		return null;
	}

	/**
	 * Promote a completed scan and remove outdated results from earlier scans.
	 *
	 * @param array<string, mixed> $state Completed scan state.
	 */
	private function finalize_completed_scan( array $state ): void {
		$scan_id = (string) ( $state['scan_id'] ?? '' );
		if ( '' === $scan_id ) {
			return;
		}

		$this->repository->save_last_completed_scan( $state );
		$this->repository->delete_except_scan( $scan_id );
	}

	/**
	 * Count variable products matching the configured statuses using WooCommerce CRUD.
	 *
	 * @param array<int, string> $statuses Post statuses to include.
	 */
	private function count_variable_products( array $statuses ): int {
		$results = wc_get_products(
			array(
				'type'     => 'variable',
				'status'   => $statuses,
				'limit'    => 1,
				'paginate' => true,
				'return'   => 'ids',
			)
		);

		return is_object( $results ) && isset( $results->total ) ? (int) $results->total : 0;
	}

	/**
	 * Fetch a single page of variable product IDs using WooCommerce CRUD.
	 *
	 * @param int                $page     Page number (1-indexed).
	 * @param int                $limit    Number of IDs per batch.
	 * @param array<int, string> $statuses Post statuses to include.
	 * @return array<int, int>
	 */
	private function fetch_variable_product_ids( int $page, int $limit, array $statuses ): array {
		$ids = wc_get_products(
			array(
				'type'     => 'variable',
				'status'   => $statuses,
				'limit'    => $limit,
				'page'     => $page,
				'orderby'  => 'ID',
				'order'    => 'ASC',
				'paginate' => false,
				'return'   => 'ids',
			)
		);

		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}

	/**
	 * Transition scan state to failed, clean up partial scan rows, and unschedule jobs.
	 *
	 * @param string $message Error message.
	 */
	private function fail_scan( string $message ): WP_Error {
		$state                  = $this->get_state();
		$failed_scan_id         = (string) ( $state['scan_id'] ?? '' );
		$state['status']        = self::STATUS_FAILED;
		$state['error_message'] = sanitize_text_field( $message );
		$state['updated_at']    = current_time( 'mysql', true );
		$this->save_state( $state );

		if ( '' !== $failed_scan_id ) {
			$this->repository->delete_by_scan( $failed_scan_id );
		}

		if ( null !== $this->scan_manager ) {
			$this->scan_manager->unschedule_all();
		}

		return new WP_Error( 'didban_scan_failed', $state['error_message'] );
	}

	/**
	 * Calculate progress percentage from scan state.
	 *
	 * @param array<string, mixed> $state Scan state.
	 */
	private function calculate_progress( array $state ): float {
		if ( self::STATUS_COMPLETED === $state['status'] ) {
			return 100.0;
		}

		$total = (int) ( $state['total_products'] ?? 0 );
		if ( $total <= 0 ) {
			return 0.0;
		}

		$processed = (int) ( $state['processed_products'] ?? 0 );
		$percent   = ( $processed / $total ) * 100;

		return round( min( 100.0, max( 0.0, $percent ) ), 1 );
	}

	/**
	 * Persist scan state to WordPress Options API.
	 *
	 * @param array<string, mixed> $state Scan state.
	 */
	private function save_state( array $state ): void {
		update_option( self::STATE_OPTION, $state, false );
	}

	/**
	 * Clamp batch size to safe limits.
	 *
	 * @param int $batch_size Requested batch size.
	 */
	private function sanitize_batch_size( int $batch_size ): int {
		if ( $batch_size < 1 ) {
			return self::DEFAULT_BATCH_SIZE;
		}

		return min( self::MAX_BATCH_SIZE, $batch_size );
	}

	/**
	 * Sanitize product statuses array.
	 *
	 * @param mixed $statuses Raw statuses.
	 * @return array<int, string>
	 */
	private function sanitize_statuses( mixed $statuses ): array {
		if ( ! is_array( $statuses ) || empty( $statuses ) ) {
			return array( 'publish' );
		}

		$allowed = array( 'publish', 'draft', 'private', 'pending' );
		$clean   = array();

		foreach ( $statuses as $status ) {
			$status = sanitize_key( (string) $status );
			if ( in_array( $status, $allowed, true ) ) {
				$clean[] = $status;
			}
		}

		return ! empty( $clean ) ? array_values( array_unique( $clean ) ) : array( 'publish' );
	}

	/**
	 * Return default enabled checks map.
	 *
	 * @return array<string, bool>
	 */
	private function get_default_checks(): array {
		return array(
			Didban_Price_Check::ISSUE_TYPE         => true,
			Didban_SKU_Check::ISSUE_TYPE           => true,
			Didban_Image_Check::ISSUE_TYPE         => true,
			Didban_Empty_Product_Check::ISSUE_TYPE => true,
			Didban_Duplicate_Check::ISSUE_TYPE     => true,
		);
	}

	/**
	 * Sanitize enabled checks map.
	 *
	 * @param mixed $checks Raw checks array or null.
	 * @return array<string, bool>
	 */
	private function sanitize_enabled_checks( mixed $checks ): array {
		$defaults = $this->get_default_checks();
		if ( ! is_array( $checks ) ) {
			return $defaults;
		}

		$clean = array();
		foreach ( $defaults as $key => $default ) {
			$clean[ $key ] = isset( $checks[ $key ] ) ? (bool) $checks[ $key ] : $default;
		}

		return $clean;
	}

	/**
	 * Acquire a short-lived transient lock for batch execution.
	 */
	private function acquire_batch_lock(): bool {
		if ( false !== get_transient( self::BATCH_LOCK_TRANSIENT ) ) {
			return false;
		}

		set_transient( self::BATCH_LOCK_TRANSIENT, time(), 120 );
		return true;
	}

	/**
	 * Release the batch execution lock.
	 */
	private function release_batch_lock(): void {
		delete_transient( self::BATCH_LOCK_TRANSIENT );
	}
}