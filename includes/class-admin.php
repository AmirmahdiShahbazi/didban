<?php
/**
 * WordPress Admin menu and standalone panel routing.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers Didban admin menu and routes to the standalone Didban panel.
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
	 * Menu slug for the issues management page.
	 */
	public const ISSUES_SLUG = 'didban-issues';

	/**
	 * Registered hook suffix for the issues page.
	 *
	 * @var string
	 */
	private string $issues_hook = '';

	/**
	 * Menu slug for the settings page.
	 */
	public const SETTINGS_SLUG = 'didban-settings';

	/**
	 * Registered hook suffix for the settings page.
	 *
	 * @var string
	 */
	private string $settings_hook = '';



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
			'__return_null',
			'dashicons-visibility',
			56
		);

		if ( '' !== $this->page_hook ) {
			add_action( 'load-' . $this->page_hook, array( $this, 'render_standalone_panel' ) );
		}
		$this->issues_hook = (string) add_submenu_page(
			self::MENU_SLUG,
			'گزارش مشکلات',
			'گزارش مشکلات',
			Didban_Capabilities::get_manage_capability(),
			self::ISSUES_SLUG,
			'__return_null'
		);

		if ( '' !== $this->issues_hook ) {
			add_action( 'load-' . $this->issues_hook, array( $this, 'render_standalone_issues' ) );
		}

		$this->settings_hook = (string) add_submenu_page(
			self::MENU_SLUG,
			'تنظیمات دیدبان',
			'تنظیمات',
			Didban_Capabilities::get_manage_capability(),
			self::SETTINGS_SLUG,
			'__return_null'
		);

		if ( '' !== $this->settings_hook ) {
			add_action( 'load-' . $this->settings_hook, array( $this, 'render_standalone_settings' ) );
		}
	}

	/**
	 * Render the standalone Settings page.
	 */
	public function render_standalone_settings(): void {
		Didban_Capabilities::verify_access();

		if ( ! Didban_WooCommerce::is_active() ) {
			require DIDBAN_PATH . 'admin/views/not-available.php';
			exit;
		}

		$settings_page = new Didban_Settings();
		$settings_page->render();
		exit;
	}

	/**
	 * Render the standalone Issues Management page.
	 */
	public function render_standalone_issues(): void {
		Didban_Capabilities::verify_access();

		if ( ! Didban_WooCommerce::is_active() ) {
			require DIDBAN_PATH . 'admin/views/not-available.php';
			exit;
		}

		$issues_page = new Didban_Issues_Page();
		$issues_page->render();
		exit;
	}

	/**
	 * Get the registered admin page hook suffix.
	 */
	public function get_page_hook(): string {
		return $this->page_hook;
	}

	/**
	 * Render the standalone Didban panel outside the WordPress admin layout.
	 */
	public function render_standalone_panel(): void {
		Didban_Capabilities::verify_access();

		if ( ! Didban_WooCommerce::is_active() ) {
			require DIDBAN_PATH . 'admin/views/not-available.php';
			exit;
		}

		$dashboard = new Didban_Dashboard();
		$dashboard->render();
		exit;
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