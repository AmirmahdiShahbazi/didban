<?php
/**
 * WordPress Admin menu and page routing.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers Didban admin menus and plugin links.
 */
final class Didban_Admin {

	/**
	 * Menu slug for the main Didban page.
	 */
	public const MENU_SLUG = 'didban';

	/**
	 * Registered hook suffix for the main Didban page.
	 *
	 * @var string
	 */
	private string $page_hook = '';

	/**
	 * Register WordPress admin hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_filter( 'plugin_action_links_' . DIDBAN_BASENAME, array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Register the top-level Didban admin menu.
	 */
	public function register_menu(): void {
		$this->page_hook = (string) add_menu_page(
			'پیشخوان دیدبان',
			'دیدبان',
			Didban_Capabilities::get_manage_capability(),
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-visibility',
			56
		);
	}

	/**
	 * Get the registered admin page hook suffix.
	 */
	public function get_page_hook(): string {
		return $this->page_hook;
	}

	/**
	 * Render the Didban admin page.
	 */
	public function render_page(): void {
		Didban_Capabilities::verify_access();

		if ( ! Didban_WooCommerce::is_active() ) {
			require DIDBAN_PATH . 'admin/views/not-available.php';
			return;
		}

		$dashboard = new Didban_Dashboard();
		$dashboard->render();
	}

	/**
	 * Add a direct link to Didban on the Plugins screen (native WP localized).
	 *
	 * @param array<int, string> $links Existing plugin action links.
	 * @return array<int, string>
	 */
	public function add_plugin_action_links( array $links ): array {
		if ( ! Didban_Capabilities::current_user_can_manage() ) {
			return $links;
		}

		$dashboard_url = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		$custom_link   = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $dashboard_url ),
			esc_html__( 'Dashboard', 'didban' )
		);

		array_unshift( $links, $custom_link );

		return $links;
	}
}