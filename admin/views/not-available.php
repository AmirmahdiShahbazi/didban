<?php
/**
 * Displayed on the Didban page when WooCommerce is not active.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

require DIDBAN_PATH . 'admin/views/partials/header.php';
?>
<div class="alert alert-warning border-0 shadow-sm" role="alert">
	<h2 class="h6 fw-bold mb-1">افزونه ووکامرس فعال نیست</h2>
	<p class="mb-0">
		برای استفاده از امکانات دیدبان، ابتدا باید افزونه ووکامرس را نصب و فعال کنید.
	</p>
</div>
<?php
require DIDBAN_PATH . 'admin/views/partials/footer.php';