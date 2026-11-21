<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Didban
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';

Didban_Database::drop_tables();

delete_option( 'didban_version' );
delete_option( 'didban_scan_state' );
delete_option( 'didban_last_completed_scan' );
delete_option( 'didban_settings' );
delete_transient( 'didban_scan_batch_lock' );
