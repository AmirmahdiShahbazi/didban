<?php
/**
 * Custom database table installation and schema upgrades.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages Didban custom database tables.
 */
final class Didban_Database {

	/**
	 * Current database schema version.
	 */
	public const DB_VERSION = '1.0.0';

	/**
	 * Option key storing installed schema version.
	 */
	public const DB_VERSION_OPTION = 'didban_db_version';

	/**
	 * Get full issues table name with WordPress prefix.
	 */
	public static function get_issues_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'didban_issues';
	}

    /**
	 * Run schema installation or upgrade if needed.
	 */
	public static function maybe_upgrade(): void {
		global $wpdb;

		$installed_version = (string) get_option( self::DB_VERSION_OPTION, '' );
		$table_name        = self::get_issues_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$table_exists      = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

		if ( self::DB_VERSION !== $installed_version || ! $table_exists ) {
			self::install();
		}
	}

    /**
	 * Create or update the custom database table using dbDelta.
	 */
	public static function install(): void {
		global $wpdb;

		$table_name      = self::get_issues_table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			scan_id CHAR(36) NOT NULL,
			product_id BIGINT(20) UNSIGNED NOT NULL,
			variation_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			product_name VARCHAR(255) NOT NULL DEFAULT '',
			sku VARCHAR(100) NOT NULL DEFAULT '',
			attributes_summary TEXT NOT NULL,
			issue_type VARCHAR(50) NOT NULL,
			message TEXT NOT NULL,
			detected_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_scan_id (scan_id),
			KEY idx_issue_type (issue_type),
			KEY idx_product_id (product_id),
			KEY idx_variation_id (variation_id),
			KEY idx_sku (sku),
			KEY idx_detected_at (detected_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Only record version if the table was actually created.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		}
	}
	/**
	 * Drop custom database table on plugin uninstall.
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$table_name = self::get_issues_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal constant.
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		delete_option( self::DB_VERSION_OPTION );
	}
}