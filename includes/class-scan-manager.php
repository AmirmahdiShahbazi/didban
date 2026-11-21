<?php
/**
 * Background scan orchestration via WooCommerce Action Scheduler.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Schedules and executes background scan batches.
 */
final class Didban_Scan_Manager {

	/**
	 * Action Scheduler hook name for processing a batch.
	 */
	public const BATCH_HOOK = 'didban_process_scan_batch';

	/**
	 * Action Scheduler group name.
	 */
	public const ACTION_GROUP = 'didban';

	/**
	 * Register Action Scheduler callback.
	 */
	public function init(): void {
		add_action( self::BATCH_HOOK, array( $this, 'handle_scheduled_batch' ), 10, 2 );
	}

	/**
	 * Schedule the next scan batch if Action Scheduler is available.
	 *
	 * @param string $scan_id    Active scan UUID.
	 * @param int    $batch_size Batch size for the scheduled run.
	 */
	public function schedule_next_batch( string $scan_id, int $batch_size = 50 ): bool {
		if ( ! function_exists( 'as_schedule_single_action' ) || ! function_exists( 'as_has_scheduled_action' ) ) {
			return false;
		}

		$args = array(
			'scan_id'    => $scan_id,
			'batch_size' => $batch_size,
		);

		if ( as_has_scheduled_action( self::BATCH_HOOK, $args, self::ACTION_GROUP ) ) {
			return true;
		}

		as_schedule_single_action( time(), self::BATCH_HOOK, $args, self::ACTION_GROUP );

		return true;
	}

	/**
	 * Unschedule all pending Didban scan batches.
	 */
	public function unschedule_all(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::BATCH_HOOK, array(), self::ACTION_GROUP );
		}
	}

	/**
	 * Action Scheduler callback to process a queued batch.
	 *
	 * @param string $scan_id    Scan UUID passed by Action Scheduler.
	 * @param int    $batch_size Batch size passed by Action Scheduler.
	 */
	public function handle_scheduled_batch( string $scan_id = '', int $batch_size = 50 ): void {
		$scanner = new Didban_Scanner( $this );
		$state   = $scanner->get_state();

		if ( '' !== $scan_id && $state['scan_id'] !== $scan_id ) {
			return;
		}

		if ( ! in_array( $state['status'], array( Didban_Scanner::STATUS_PENDING, Didban_Scanner::STATUS_RUNNING ), true ) ) {
			return;
		}

		$scanner->process_batch( $batch_size );
	}
}