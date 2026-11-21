<?php
/**
 * Main Persian RTL dashboard view (Phase 1 initial empty state).
 *
 * @package Didban
 *
 * @var array<string, mixed> $stats
 * @var bool                 $has_scan
 */

defined( 'ABSPATH' ) || exit;

$summary_cards = array(
	array(
		'label' => 'تعداد محصولات متغیر',
		'value' => $stats['total_products'],
		'tone'  => 'primary',
	),
	array(
		'label' => 'تعداد تنوع‌ها',
		'value' => $stats['total_variations'],
		'tone'  => 'info',
	),
	array(
		'label' => 'قیمت ثبت نشده',
		'value' => $stats['missing_price'],
		'tone'  => 'danger',
	),
	array(
		'label' => 'کد SKU ثبت نشده',
		'value' => $stats['missing_sku'],
		'tone'  => 'warning',
	),
	array(
		'label' => 'تصویر اختصاصی ندارد',
		'value' => $stats['missing_image'],
		'tone'  => 'warning',
	),
	array(
		'label' => 'محصولات بدون تنوع فعال',
		'value' => $stats['without_active_vars'],
		'tone'  => 'danger',
	),
	array(
		'label' => 'ترکیب ویژگی تکراری',
		'value' => $stats['duplicate_combinations'],
		'tone'  => 'secondary',
	),
);
?>

<section class="didban-toolbar card border-0 shadow-sm mb-4">
	<div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
		<div class="d-flex align-items-center gap-3">
			<button type="button" class="btn btn-primary px-4" disabled aria-disabled="true">
				شروع بررسی
			</button>
			<span class="text-muted small">
				موتور بررسی خودکار در فاز بعدی فعال خواهد شد.
			</span>
		</div>

		<div class="text-muted small">
			<span>آخرین بررسی:</span>
			<strong class="text-dark">هنوز انجام نشده</strong>
		</div>
	</div>
</section>

<section class="didban-stats row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
	<?php foreach ( $summary_cards as $card ) : ?>
		<div class="col">
			<div class="card didban-stat-card h-100 border-0 shadow-sm">
				<div class="card-body">
					<div class="text-muted small mb-2"><?php echo esc_html( $card['label'] ); ?></div>
					<div class="fs-3 fw-bold text-<?php echo esc_attr( $card['tone'] ); ?>">
						<?php echo esc_html( (string) $card['value'] ); ?>
					</div>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</section>

<?php if ( ! $has_scan ) : ?>
	<section class="didban-empty-state card border-0 shadow-sm">
		<div class="card-body text-center py-5">
			<div class="didban-empty-icon mb-3">
				<span class="dashicons dashicons-search"></span>
			</div>
			<h2 class="h5 fw-bold mb-2">هنوز هیچ بررسی‌ای انجام نشده است</h2>
			<p class="text-muted mb-0 mx-auto" style="max-width: 520px;">
				دیدبان محصولات متغیر و تنوع‌های فروشگاه شما را برای یافتن قیمت‌های ثبت‌نشده،
				کدهای SKU خالی، تصاویر اختصاصی مفقود، محصولات بدون تنوع فعال و ترکیب‌های تکراری بررسی می‌کند.
			</p>
		</div>
	</section>
<?php endif; ?>