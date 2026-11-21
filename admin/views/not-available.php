<?php
/**
 * Displayed on the Didban page when WooCommerce is not active.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

require DIDBAN_PATH . 'admin/views/partials/header.php';
?>
<section class="didban-not-available card border-0 shadow-sm">
	<div class="card-body text-center py-5 px-4">
		<div class="didban-warning-icon mb-3">
			<span class="dashicons dashicons-warning" aria-hidden="true"></span>
		</div>

		<h2 class="h5 fw-bold mb-2">افزونه ووکامرس فعال نیست</h2>

		<p class="text-muted mb-4 mx-auto" style="max-width: 480px;">
			دیدبان برای بررسی محصولات متغیر و تنوع‌ها به افزونه ووکامرس نیاز دارد.
			لطفاً ابتدا ووکامرس را نصب و فعال کنید.
		</p>

		<?php if ( current_user_can( 'activate_plugins' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="btn btn-primary px-4">
				مدیریت افزونه‌ها
			</a>
		<?php endif; ?>
	</div>
</section>
<?php
require DIDBAN_PATH . 'admin/views/partials/footer.php';