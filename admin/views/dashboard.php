<?php
/**
 * Main Persian RTL dashboard view (Midnight-Slate Dark UI, Bootstrap 5.x compatible).
 *
 * @package Didban
 *
 * @var array<string, mixed>                 $stats
 * @var bool                                 $has_scan
 * @var bool                                 $is_scanning
 * @var float                                $scan_progress
 * @var int|null                             $health_score
 * @var string                               $status_label
 * @var array<int, array<string, mixed>>     $latest_issues
 * @var array<string, array<string, string>> $issue_labels
 */

defined( 'ABSPATH' ) || exit;

$total_products   = (int) $stats['total_products'];
$total_variations = (int) $stats['total_variations'];
$total_issues     = (int) $stats['total_issues'];
$avg_variations   = $total_products > 0 ? round( $total_variations / $total_products, 1 ) : 0;

$gauge_radius        = 48;
$gauge_circumference = 2 * M_PI * $gauge_radius;
$gauge_offset        = null !== $health_score
	? $gauge_circumference * ( 1 - ( $health_score / 100 ) )
	: $gauge_circumference;

$diagnostic_checks = array(
	array(
		'type'        => Didban_Price_Check::ISSUE_TYPE,
		'label'       => 'قیمت ثبت نشده',
		'description' => 'تنوع‌هایی که قیمت عادی و قیمت فروش آن‌ها خالی است و امکان خرید ندارند.',
		'value'       => (int) $stats['missing_price'],
		'base'        => max( 1, $total_variations ),
		'color'       => 'danger',
		'icon'        => 'dashicons-tag',
	),
	array(
		'type'        => Didban_SKU_Check::ISSUE_TYPE,
		'label'       => 'کد SKU ثبت نشده',
		'description' => 'تنوع‌هایی که شناسه یکتای انبار (SKU) ندارند و مدیریت موجودی را سخت می‌کنند.',
		'value'       => (int) $stats['missing_sku'],
		'base'        => max( 1, $total_variations ),
		'color'       => 'warning',
		'icon'        => 'dashicons-media-code',
	),
	array(
		'type'        => Didban_Image_Check::ISSUE_TYPE,
		'label'       => 'تصویر اختصاصی ندارد',
		'description' => 'تنوع‌هایی که تصویر مستقل ندارند و از تصویر محصول مادر استفاده می‌کنند.',
		'value'       => (int) $stats['missing_image'],
		'base'        => max( 1, $total_variations ),
		'color'       => 'info',
		'icon'        => 'dashicons-format-image',
	),
	array(
		'type'        => Didban_Empty_Product_Check::ISSUE_TYPE,
		'label'       => 'محصولات بدون تنوع فعال',
		'description' => 'محصولات متغیر منتشرشده‌ای که هیچ تنوع فعالی برای نمایش به مشتری ندارند.',
		'value'       => (int) $stats['without_active_vars'],
		'base'        => max( 1, $total_products ),
		'color'       => 'danger',
		'icon'        => 'dashicons-dismiss',
	),
	array(
		'type'        => Didban_Duplicate_Check::ISSUE_TYPE,
		'label'       => 'ترکیب ویژگی تکراری',
		'description' => 'تنوع‌هایی در یک محصول که دقیقاً دارای ویژگی‌های یکسان هستند.',
		'value'       => (int) $stats['duplicate_combinations'],
		'base'        => max( 1, $total_variations ),
		'color'       => 'primary',
		'icon'        => 'dashicons-admin-page',
	),
);
?>

<div class="d-flex flex-column gap-4">

	<?php if ( Didban_Scanner::STATUS_FAILED === $stats['last_scan_status'] && '' !== $stats['scan_error'] ) : ?>
		<div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-0 rounded-3" role="alert">
			<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			<div>
				<strong>آخرین بررسی با خطا متوقف شد:</strong>
				<span><?php echo esc_html( $stats['scan_error'] ); ?></span>
			</div>
		</div>
	<?php endif; ?>

	<div id="didban-ajax-error" class="alert alert-danger border-0 shadow-sm d-none mb-0 rounded-3" role="alert"></div>

	<!-- 1. Command Center Hero Card with SVG Health Gauge -->
	<section class="card didban-command-card text-white rounded-4 overflow-hidden">
		<div class="card-body p-4 p-md-5 d-flex flex-column align-items-center text-center gap-3">
			<div class="d-flex flex-wrap justify-content-center gap-2">
				<span class="badge didban-pill-indigo px-3 py-2 rounded-pill fw-normal">
					مرکز پایش سلامت ووکامرس
				</span>
				<span class="badge didban-pill-cyan px-3 py-2 rounded-pill fw-normal">
					وضعیت: <?php echo esc_html( $status_label ); ?>
				</span>
			</div>

			<!-- SVG Circular Gauge -->
			<div class="position-relative d-inline-flex align-items-center justify-content-center my-2">
				<svg class="didban-gauge-svg" viewBox="0 0 118 118" aria-hidden="true">
					<circle class="didban-gauge-track" cx="59" cy="59" r="<?php echo esc_attr( (string) $gauge_radius ); ?>"></circle>
					<circle
						class="didban-gauge-fill"
						cx="59"
						cy="59"
						r="<?php echo esc_attr( (string) $gauge_radius ); ?>"
						stroke-dasharray="<?php echo esc_attr( (string) round( $gauge_circumference, 2 ) ); ?>"
						stroke-dashoffset="<?php echo esc_attr( (string) round( $gauge_offset, 2 ) ); ?>"
					></circle>
				</svg>
				<div class="position-absolute text-center">
					<div class="fs-4 fw-bold lh-1 text-white">
						<?php echo null !== $health_score ? esc_html( $health_score . '٪' ) : '—'; ?>
					</div>
					<div class="didban-muted mt-1" style="font-size: 0.7rem;">سلامت کاتالوگ</div>
				</div>
			</div>

			<div>
				<h1 class="h4 fw-bold mb-2 text-white">پایش هوشمند محصولات متغیر و تنوع‌ها</h1>
				<p class="mb-0 didban-muted mx-auto" style="max-width: 540px;">
					دیدبان تمام تنوع‌های فروشگاه شما را بررسی می‌کند تا خطاهای قیمت‌گذاری، کد انبار، تصاویر و تداخل ویژگی‌ها را قبل از مشتری پیدا کنید.
				</p>
			</div>

			<button
				type="button"
				id="didban-start-scan-btn"
				class="btn btn-info text-dark fw-bold px-5 py-2 rounded-pill d-inline-flex align-items-center gap-2 shadow mt-1"
				<?php disabled( $is_scanning ); ?>
			>
				<span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
				<span id="didban-start-scan-text">
					<?php echo $is_scanning ? 'در حال بررسی...' : 'شروع بررسی جدید'; ?>
				</span>
			</button>

			<div class="small didban-muted d-flex align-items-center gap-1">
				<span class="dashicons dashicons-clock" aria-hidden="true"></span>
				<span>آخرین بررسی:</span>
				<strong class="text-white"><?php echo esc_html( $stats['last_scan_date'] ?? 'هنوز انجام نشده' ); ?></strong>
			</div>

			<!-- Live Progress Bar -->
			<div id="didban-progress-wrap" class="w-100 mt-2 <?php echo $is_scanning ? '' : 'd-none'; ?>" style="max-width: 560px;">
				<div class="d-flex justify-content-between small mb-1">
					<span id="didban-progress-label">در حال آماده‌سازی بررسی...</span>
					<span id="didban-progress-percent" class="fw-bold text-info"><?php echo esc_html( (string) $scan_progress ); ?>٪</span>
				</div>
				<div class="progress rounded-pill didban-progress-track" style="height: 10px;">
					<div
						id="didban-progress-bar"
						class="progress-bar progress-bar-striped progress-bar-animated bg-info rounded-pill"
						role="progressbar"
						style="width: <?php echo esc_attr( (string) $scan_progress ); ?>%;"
						aria-valuenow="<?php echo esc_attr( (string) $scan_progress ); ?>"
						aria-valuemin="0"
						aria-valuemax="100"
					></div>
				</div>
			</div>
		</div>
	</section>

	<!-- 2. Catalog Overview Card -->
	<section class="card didban-surface rounded-4">
		<div class="card-body p-4 d-flex flex-column gap-3">
			<div class="d-flex align-items-center justify-content-between">
				<h2 class="h6 fw-bold mb-0 text-white">نمای کلی کاتالوگ</h2>
				<span class="badge didban-pill-neutral rounded-pill px-3 py-2">
					میانگین <?php echo esc_html( (string) $avg_variations ); ?> تنوع در هر محصول
				</span>
			</div>

			<div class="d-flex flex-column gap-2">
				<div class="p-3 didban-inner-box rounded-3 d-flex align-items-center justify-content-between">
					<div class="d-flex align-items-center gap-3">
						<span class="didban-tint-primary rounded-3 p-2 d-inline-flex">
							<span class="dashicons dashicons-products" aria-hidden="true"></span>
						</span>
						<span class="fw-semibold text-white">تعداد کل محصولات متغیر</span>
					</div>
					<span class="fs-5 fw-bold text-info"><?php echo esc_html( (string) $total_products ); ?></span>
				</div>

				<div class="p-3 didban-inner-box rounded-3 d-flex align-items-center justify-content-between">
					<div class="d-flex align-items-center gap-3">
						<span class="didban-tint-info rounded-3 p-2 d-inline-flex">
							<span class="dashicons dashicons-networking" aria-hidden="true"></span>
						</span>
						<span class="fw-semibold text-white">تعداد کل تنوع‌های بررسی‌شده</span>
					</div>
					<span class="fs-5 fw-bold text-info"><?php echo esc_html( (string) $total_variations ); ?></span>
				</div>
			</div>
		</div>
	</section>

	<!-- 3. Diagnostic Breakdown Cards (Vertical) -->
	<section class="d-flex flex-column gap-3">
		<div class="d-flex align-items-center justify-content-between px-1">
			<h2 class="h6 fw-bold mb-0 text-white">تحلیل وضعیت ۵ شاخص کلیدی</h2>
			<span class="didban-muted small">مجموع مشکلات: <?php echo esc_html( (string) $total_issues ); ?></span>
		</div>

		<?php foreach ( $diagnostic_checks as $check ) : ?>
			<?php
			$count   = $check['value'];
			$percent = $has_scan ? min( 100, round( ( $count / $check['base'] ) * 100, 1 ) ) : 0;
			$is_ok   = $has_scan && 0 === $count;
			?>
			<article class="card didban-surface didban-diag-card didban-border-<?php echo esc_attr( $check['color'] ); ?> rounded-3">
				<div class="card-body p-4 d-flex flex-column gap-3">
					<div class="d-flex align-items-start justify-content-between gap-3">
						<div class="d-flex align-items-start gap-3">
							<span class="didban-tint-<?php echo esc_attr( $check['color'] ); ?> rounded-3 p-2 d-inline-flex">
								<span class="dashicons <?php echo esc_attr( $check['icon'] ); ?>" aria-hidden="true"></span>
							</span>
							<div>
								<h3 class="h6 fw-bold mb-1 text-white"><?php echo esc_html( $check['label'] ); ?></h3>
								<p class="didban-muted small mb-0"><?php echo esc_html( $check['description'] ); ?></p>
							</div>
						</div>

						<div class="text-start flex-shrink-0">
							<div class="fs-4 fw-bold text-white lh-1">
								<?php echo esc_html( (string) $count ); ?>
							</div>
							<?php if ( $has_scan ) : ?>
								<span class="badge <?php echo $is_ok ? 'didban-tint-success' : 'didban-tint-' . esc_attr( $check['color'] ); ?> rounded-pill mt-2">
									<?php echo $is_ok ? 'سالم ✓' : 'نیاز به بررسی'; ?>
								</span>
							<?php endif; ?>
						</div>
					</div>

					<div>
						<div class="d-flex justify-content-between small didban-muted mb-1">
							<span>نسبت درگیری کاتالوگ</span>
							<span><?php echo esc_html( (string) $percent ); ?>٪</span>
						</div>
						<div class="progress didban-progress-track" style="height: 6px;">
							<div
								class="progress-bar bg-<?php echo esc_attr( $check['color'] ); ?>"
								role="progressbar"
								style="width: <?php echo esc_attr( (string) $percent ); ?>%;"
								aria-valuenow="<?php echo esc_attr( (string) $percent ); ?>"
								aria-valuemin="0"
								aria-valuemax="100"
							></div>
						</div>
					</div>

					<?php if ( $has_scan && $count > 0 ) : ?>
						<div>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Didban_Admin::ISSUES_SLUG . '&issue_type=' . $check['type'] ) ); ?>" class="btn btn-sm btn-outline-<?php echo esc_attr( $check['color'] ); ?> rounded-pill px-3">
								مشاهده موارد (<?php echo esc_html( (string) $count ); ?>)
							</a>
						</div>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</section>

	<!-- 4. Actionable Issue Feed / Success / Empty State -->
	<?php if ( ! $has_scan ) : ?>
		<section class="card didban-surface rounded-4">
			<div class="card-body p-4 text-center">
				<span class="dashicons dashicons-search text-info fs-1 mb-2 d-block" aria-hidden="true"></span>
				<h3 class="h6 fw-bold mb-1 text-white">آماده برای اولین بررسی</h3>
				<p class="didban-muted small mb-0">
					روی دکمه «شروع بررسی جدید» در بالای صفحه کلیک کنید تا گزارش کامل مشکلات نمایش داده شود.
				</p>
			</div>
		</section>
	<?php elseif ( 0 === $total_issues ) : ?>
		<section class="card didban-surface rounded-4">
			<div class="card-body p-5 text-center">
				<span class="dashicons dashicons-yes-alt text-success fs-1 mb-3 d-block" aria-hidden="true"></span>
				<h3 class="h5 fw-bold mb-1 text-white">تبریک! کاتالوگ شما کاملاً سالم است</h3>
				<p class="didban-muted small mb-0">
					هیچ مشکلی در قیمت، کد SKU، تصاویر، تنوع‌های فعال یا ترکیب ویژگی‌ها یافت نشد.
				</p>
			</div>
		</section>
	<?php else : ?>
		<section class="card didban-surface rounded-4 overflow-hidden">
			<div class="card-header bg-transparent py-3 px-4 border-bottom border-secondary d-flex flex-wrap align-items-center justify-content-between gap-2">
				<div>
					<h3 class="h6 fw-bold mb-0 text-white">آخرین موارد نیازمند اصلاح</h3>
					<span class="didban-muted small">نمایش ۱۰ مورد اخیر از مجموع <?php echo esc_html( (string) $total_issues ); ?> مشکل</span>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Didban_Admin::ISSUES_SLUG ) ); ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
					مشاهده همه مشکلات
				</a>
			</div>

			<div class="table-responsive">
				<table class="table didban-table align-middle mb-0">
					<thead>
						<tr>
							<th class="px-4 py-3 small">محصول</th>
							<th class="py-3 small">مشخصات تنوع</th>
							<th class="py-3 small">SKU</th>
							<th class="py-3 small">نوع مشکل</th>
							<th class="px-4 py-3 text-end small">عملیات</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $latest_issues as $issue ) : ?>
							<?php
							$issue_type  = (string) $issue['issue_type'];
							$meta        = $issue_labels[ $issue_type ] ?? array(
								'label' => $issue_type,
								'badge' => 'secondary',
							);
							$badge_color = match ( $issue_type ) {
								Didban_Price_Check::ISSUE_TYPE,
								Didban_Empty_Product_Check::ISSUE_TYPE => 'danger',
								Didban_SKU_Check::ISSUE_TYPE,
								Didban_Image_Check::ISSUE_TYPE         => 'warning',
								Didban_Duplicate_Check::ISSUE_TYPE     => 'primary',
								default                                => 'secondary',
							};
							$edit_url  = get_edit_post_link( (int) $issue['product_id'], 'raw' );
							$var_id    = (int) $issue['variation_id'];
							$attr_text = '' !== (string) $issue['attributes_summary'] ? (string) $issue['attributes_summary'] : '—';
							$sku_text  = '' !== (string) $issue['sku'] ? (string) $issue['sku'] : '—';
							?>
							<tr>
								<td class="px-4 py-3">
									<div class="fw-semibold text-white"><?php echo esc_html( (string) $issue['product_name'] ); ?></div>
									<div class="didban-muted small">#<?php echo esc_html( (string) $issue['product_id'] ); ?></div>
								</td>
								<td class="py-3">
									<div class="small text-white"><?php echo esc_html( $attr_text ); ?></div>
									<?php if ( $var_id > 0 ) : ?>
										<div class="didban-muted small">تنوع #<?php echo esc_html( (string) $var_id ); ?></div>
									<?php endif; ?>
								</td>
								<td class="py-3"><span class="badge didban-pill-neutral font-monospace"><?php echo esc_html( $sku_text ); ?></span></td>
								<td class="py-3">
									<span class="badge didban-tint-<?php echo esc_attr( $badge_color ); ?> rounded-pill px-3 py-2">
										<?php echo esc_html( $meta['label'] ); ?>
									</span>
								</td>
								<td class="px-4 py-3 text-end">
									<?php if ( $edit_url ) : ?>
										<a href="<?php echo esc_url( $edit_url ); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info rounded-pill px-3">
											اصلاح در ووکامرس
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
	<?php endif; ?>

</div>