<?php
/**
 * Dashboard header partial.
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap didban-wrap">
	<div class="didban-dashboard" lang="fa" dir="rtl">
		<header class="didban-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
			<div>
				<div class="d-flex align-items-center gap-2">
					<h1 class="didban-title h3 mb-0 fw-bold">پیشخوان دیدبان</h1>
					<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
						نسخه <?php echo esc_html( DIDBAN_VERSION ); ?>
					</span>
				</div>
				<p class="didban-subtitle text-muted mb-0 mt-1">
					بررسی و شناسایی مشکلات تنوع محصولات در فروشگاه ووکامرس
				</p>
			</div>

			<div class="didban-header-meta text-muted small">
				<span>وضعیت بررسی:</span>
				<span class="badge bg-secondary-subtle text-secondary ms-1">آماده به کار</span>
			</div>
		</header>