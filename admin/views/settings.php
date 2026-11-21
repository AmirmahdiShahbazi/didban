<?php
/**
 * Persian RTL Settings view (Midnight-Slate Dark UI).
 *
 * @package Didban
 *
 * @var array<string, mixed>                $settings
 * @var array{type: string, message: string}|null $notice
 */

defined( 'ABSPATH' ) || exit;

$checks_meta = array(
	Didban_Price_Check::ISSUE_TYPE         => array(
		'label' => 'بررسی قیمت ثبت‌نشده',
		'desc'  => 'شناسایی تنوع‌هایی که قیمت عادی و فروش آن‌ها خالی است.',
	),
	Didban_SKU_Check::ISSUE_TYPE           => array(
		'label' => 'بررسی کد SKU ثبت‌نشده',
		'desc'  => 'شناسایی تنوع‌هایی که کد SKU ندارند.',
	),
	Didban_Image_Check::ISSUE_TYPE         => array(
		'label' => 'بررسی تصویر اختصاصی تنوع',
		'desc'  => 'شناسایی تنوع‌هایی که تصویر مستقل ندارند.',
	),
	Didban_Empty_Product_Check::ISSUE_TYPE => array(
		'label' => 'بررسی محصولات بدون تنوع فعال',
		'desc'  => 'شناسایی محصولات متغیر بدون هیچ تنوع فعال.',
	),
	Didban_Duplicate_Check::ISSUE_TYPE     => array(
		'label' => 'بررسی ترکیب ویژگی تکراری',
		'desc'  => 'شناسایی تنوع‌هایی با ترکیب ویژگی یکسان در یک محصول.',
	),
);
?>

<div class="d-flex flex-column gap-4">

	<!-- Header Card -->
	<section class="card didban-surface rounded-4">
		<div class="card-body p-4">
			<h1 class="h5 fw-bold mb-1 text-white">تنظیمات دیدبان</h1>
			<p class="didban-muted small mb-0">
				پیکربندی محدوده بررسی محصولات، ماژول‌های تشخیص و مدیریت داده‌های ذخیره‌شده
			</p>
		</div>
	</section>

	<?php if ( null !== $notice ) : ?>
		<div class="alert alert-<?php echo 'success' === $notice['type'] ? 'success' : 'danger'; ?> border-0 shadow-sm rounded-3 mb-0" role="alert">
			<?php echo esc_html( $notice['message'] ); ?>
		</div>
	<?php endif; ?>

	<!-- Settings Form -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . Didban_Admin::SETTINGS_SLUG ) ); ?>" class="d-flex flex-column gap-4">
		<?php wp_nonce_field( 'didban_save_settings', 'didban_settings_nonce' ); ?>

		<!-- 1. Product Scope -->
		<section class="card didban-surface rounded-4">
			<div class="card-body p-4 d-flex flex-column gap-3">
				<h2 class="h6 fw-bold mb-0 text-white">محدوده بررسی محصولات</h2>

				<div class="p-3 didban-inner-box rounded-3 d-flex align-items-center justify-content-between gap-3">
					<div>
						<label class="fw-semibold text-white d-block" for="didban-include-draft">بررسی محصولات پیش‌نویس (Draft)</label>
						<span class="didban-muted small">در صورت فعال‌سازی، محصولات متغیر پیش‌نویس نیز بررسی می‌شوند.</span>
					</div>
					<div class="form-check form-switch mb-0">
						<input class="form-check-input" type="checkbox" role="switch" id="didban-include-draft" name="include_draft" value="1" <?php checked( ! empty( $settings['include_draft'] ) ); ?>>
					</div>
				</div>

				<div class="p-3 didban-inner-box rounded-3 d-flex align-items-center justify-content-between gap-3">
					<div>
						<label class="fw-semibold text-white d-block" for="didban-include-private">بررسی محصولات خصوصی (Private)</label>
						<span class="didban-muted small">در صورت فعال‌سازی، محصولات متغیر با وضعیت خصوصی نیز بررسی می‌شوند.</span>
					</div>
					<div class="form-check form-switch mb-0">
						<input class="form-check-input" type="checkbox" role="switch" id="didban-include-private" name="include_private" value="1" <?php checked( ! empty( $settings['include_private'] ) ); ?>>
					</div>
				</div>
			</div>
		</section>

		<!-- 2. Individual Checks -->
		<section class="card didban-surface rounded-4">
			<div class="card-body p-4 d-flex flex-column gap-3">
				<h2 class="h6 fw-bold mb-0 text-white">ماژول‌های تشخیص فعال</h2>

				<?php foreach ( $checks_meta as $check_key => $meta ) : ?>
					<?php $is_enabled = ! empty( $settings['enabled_checks'][ $check_key ] ); ?>
					<div class="p-3 didban-inner-box rounded-3 d-flex align-items-center justify-content-between gap-3">
						<div>
							<label class="fw-semibold text-white d-block" for="didban-check-<?php echo esc_attr( $check_key ); ?>">
								<?php echo esc_html( $meta['label'] ); ?>
							</label>
							<span class="didban-muted small"><?php echo esc_html( $meta['desc'] ); ?></span>
						</div>
						<div class="form-check form-switch mb-0">
							<input
								class="form-check-input"
								type="checkbox"
								role="switch"
								id="didban-check-<?php echo esc_attr( $check_key ); ?>"
								name="enabled_checks[<?php echo esc_attr( $check_key ); ?>]"
								value="1"
								<?php checked( $is_enabled ); ?>
							>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- 3. Performance & Retention -->
		<section class="card didban-surface rounded-4">
			<div class="card-body p-4 d-flex flex-column gap-3">
				<h2 class="h6 fw-bold mb-0 text-white">عملکرد و نگهداری داده‌ها</h2>

				<div class="row g-3">
					<div class="col-12 col-md-6">
						<label for="didban-batch-size" class="form-label small didban-muted">
							تعداد محصول در هر دسته (Batch Size)
						</label>
						<input
							type="number"
							id="didban-batch-size"
							name="batch_size"
							class="form-control"
							min="10"
							max="200"
							value="<?php echo esc_attr( (string) $settings['batch_size'] ); ?>"
							required
						>
						<div class="form-text didban-muted">مقدار مجاز بین ۱۰ تا ۲۰۰ محصول (پیشنهاد: ۵۰).</div>
					</div>

					<div class="col-12 col-md-6">
						<label for="didban-retention-days" class="form-label small didban-muted">
							مدت نگهداری سوابق مشکلات (روز)
						</label>
						<input
							type="number"
							id="didban-retention-days"
							name="retention_days"
							class="form-control"
							min="1"
							max="365"
							value="<?php echo esc_attr( (string) $settings['retention_days'] ); ?>"
							required
						>
						<div class="form-text didban-muted">داده‌های قدیمی‌تر از این بازه به‌صورت ایمن پاک می‌شوند (۱ تا ۳۶۵ روز).</div>
					</div>
				</div>

				<div class="pt-2">
					<button type="submit" class="btn btn-info text-dark fw-bold px-5 py-2 rounded-pill">
						ذخیره تنظیمات
					</button>
				</div>
			</div>
		</section>
	</form>

	<!-- 4. Manual Cleanup Card -->
	<section class="card didban-surface rounded-4 border-danger">
		<div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
			<div>
				<h2 class="h6 fw-bold mb-1 text-white">پاک‌سازی دستی نتایج بررسی</h2>
				<p class="didban-muted small mb-0">
					تمام مشکلات ذخیره‌شده و اطلاعات آخرین بررسی پاک می‌شوند. این عمل محصولات فروشگاه شما را تغییر نمی‌دهد.
				</p>
			</div>

			<form
				method="post"
				action="<?php echo esc_url( admin_url( 'admin.php?page=' . Didban_Admin::SETTINGS_SLUG ) ); ?>"
				onsubmit="return confirm('آیا از پاک‌سازی تمام نتایج بررسی‌های قبلی مطمئن هستید؟');"
			>
				<?php wp_nonce_field( 'didban_manual_cleanup', 'didban_cleanup_nonce' ); ?>
				<button type="submit" class="btn btn-outline-danger rounded-pill px-4">
					پاک‌سازی کامل نتایج
				</button>
			</form>
		</div>
	</section>

</div>