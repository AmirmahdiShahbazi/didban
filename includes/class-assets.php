<?php
/**
 * Admin asset loader.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues CSS and JS strictly on Didban admin pages.
 */
final class Didban_Assets {

	/**
	 * Admin instance.
	 *
	 * @var Didban_Admin
	 */
	private Didban_Admin $admin;

	/**
	 * Constructor.
	 *
	 * @param Didban_Admin $admin Admin instance.
	 */
	public function __construct( Didban_Admin $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Register enqueue hook.
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue styles and scripts only on Didban pages.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$page_hook = $this->admin->get_page_hook();

		if ( '' === $page_hook || $hook_suffix !== $page_hook ) {
			return;
		}

		wp_enqueue_style(
			'didban-bootstrap-rtl',
			DIDBAN_URL . 'admin/assets/css/bootstrap.rtl.min.css',
			array(),
			'5.3.3'
		);

		wp_enqueue_style(
			'didban-admin',
			DIDBAN_URL . 'admin/assets/css/admin.css',
			array( 'didban-bootstrap-rtl' ),
			DIDBAN_VERSION
		);

		wp_enqueue_script(
			'didban-admin',
			DIDBAN_URL . 'admin/assets/js/admin.js',
			array(),
			DIDBAN_VERSION,
			true
		);
	}
}