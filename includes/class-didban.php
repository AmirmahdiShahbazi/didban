<?php
/**
 * Core plugin orchestrator.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main Didban class.
 */
final class Didban {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Admin handler.
	 *
	 * @var Didban_Admin|null
	 */
	private ?Didban_Admin $admin = null;

	/**
	 * Assets handler.
	 *
	 * @var Didban_Assets|null
	 */
	private ?Didban_Assets $assets = null;

	/**
	 * Get singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton.
	 */
	private function __construct() {}

	/**
	 * Initialize plugin components.
	 */
	public function init(): void {
		$this->load_dependencies();
		$this->load_textdomain();

		if ( ! Didban_WooCommerce::is_active() ) {
			add_action( 'admin_notices', array( Didban_WooCommerce::class, 'render_missing_notice' ) );
		}

		if ( is_admin() ) {
			$this->admin  = new Didban_Admin();
			$this->assets = new Didban_Assets( $this->admin );

			$this->admin->init();
			$this->assets->init();
		}
	}

	/**
	 * Load required class files.
	 */
	private function load_dependencies(): void {
		require_once DIDBAN_PATH . 'includes/class-capabilities.php';
		require_once DIDBAN_PATH . 'includes/class-woocommerce.php';
		require_once DIDBAN_PATH . 'includes/class-assets.php';
		require_once DIDBAN_PATH . 'includes/class-admin.php';
		require_once DIDBAN_PATH . 'admin/class-dashboard.php';
	}

	/**
	 * Load WordPress text domain for native integration strings.
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'didban',
			false,
			dirname( DIDBAN_BASENAME ) . '/languages'
		);
	}
}