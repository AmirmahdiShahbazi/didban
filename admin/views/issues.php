<?php
/**
 * Issues management and filtering view (Persian RTL, Midnight-Slate Dark UI).
 *
 * @package Didban
 *
 * @var bool                                 $has_scan
 * @var array<string, mixed>                 $filters
 * @var array<string, mixed>                 $result
 * @var array<int, WP_Term>                  $categories
 * @var array<string, array<string, string>> $issue_labels
 */

defined( 'ABSPATH' ) || exit;

$items       = (array) ( $result['items'] ?? array() );
$total_items = (int) ( $result['total'] ?? 0 );
$total_pages = (int) ( $result['pages'] ?? 0 );
$paged       = (int) ( $result['page'] ?? 1 );
$has_filters = '' !== $filters['search'] || '' !== $filters['issue_type'] || $filters['category_id'] > 0;
$base_url    = admin_url( 'admin.php?page=' . Didban_Admin::ISSUES_SLUG );
$sort_value  = $filters['orderby'] . '|' . $filters['order'];
?>

<div class="d-flex flex-column gap-4">

	<!-- Page Header Card -->
	<section class="card didban-surface rounded-4">
		<div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
			<div>
				<h1 class="h5 fw-bold mb-1 text-white">مدیریت و بررسی مشکلات</h1>
				<p class="didban-muted small mb-0">
					جستجو، فیلتر و بررسی دقیق تمام مشکلات شناسایی‌شده در محصولات متغیر و تنوع‌ها
				</p>
			</div>

			<div class="d-flex align-items-center gap-2">
				<span class="badge didban-pill-indigo rounded-pill px-3 py-2 fs-6">
					تعداد نتایج: <?php echo esc_html( (string) $total_items ); ?>
				</span>
			</div>
		</div>
	</section>

	<?php if ( ! $has_scan ) : ?>
		<section class="card didban-surface rounded-4">
			<div class="card-body p-5 text-center">
				<span class="dashicons dashicons-search text-info fs-1 mb-3 d-block" aria-hidden="true"></span>
				<h2 class="h5 fw-bold mb-2 text-white">هنوز هیچ بررسی‌ای انجام نشده است</h2>
				<p class="didban-muted small mb-4">
					برای مشاهده و فیلتر مشکلات، ابتدا از پیشخوان یک بررسی جدید اجرا کنید.
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Didban_Admin::MENU_SLUG ) ); ?>" class="btn btn-info text-dark fw-bold rounded-pill px-4">
					رفتن به پیشخوان دیدبان
				</a>
			</div>
		</section>
	<?php else : ?>
		<!-- Search & Filter Toolbar -->
		<section class="card didban-surface rounded-4">
			<div class="card-body p-4">
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="row g-3 align-items-end">
					<input type="hidden" name="page" value="<?php echo esc_attr( Didban_Admin::ISSUES_SLUG ); ?>">

					<div class="col-12 col-lg-4">
						<label for="didban-filter-search" class="form-label small didban-muted">جستجو (نام محصول، شناسه یا SKU)</label>
						<input
							type="search"
							id="didban-filter-search"
							name="search"
							class="form-control"
							value="<?php echo esc_attr( (string) $filters['search'] ); ?>"
							placeholder="مثلاً: تیشرت، 120 یا SKU-10"
						>
					</div>

					<div class="col-12 col-sm-6 col-lg-3">
						<label for="didban-filter-type" class="form-label small didban-muted">نوع مشکل</label>
						<select id="didban-filter-type" name="issue_type" class="form-select">
							<option value="">همه مشکلات</option>
							<?php foreach ( $issue_labels as $type_key => $type_meta ) : ?>
								<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $filters['issue_type'], $type_key ); ?>>
									<?php echo esc_html( $type_meta['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="col-12 col-sm-6 col-lg-3">
						<label for="didban-filter-category" class="form-label small didban-muted">دسته‌بندی محصول</label>
						<select id="didban-filter-category" name="category_id" class="form-select">
							<option value="0">همه دسته‌بندی‌ها</option>
							<?php foreach ( $categories as $category ) : ?>
								<option value="<?php echo esc_attr( (string) $category->term_id ); ?>" <?php selected( (int) $filters['category_id'], (int) $category->term_id ); ?>>
									<?php echo esc_html( $category->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="col-6 col-lg-2">
						<label for="didban-filter-per-page" class="form-label small didban-muted">تعداد در صفحه</label>
						<select id="didban-filter-per-page" name="per_page" class="form-select">
							<?php foreach ( array( 20, 50, 100 ) as $limit_opt ) : ?>
								<option value="<?php echo esc_attr( (string) $limit_opt ); ?>" <?php selected( (int) $filters['per_page'], $limit_opt ); ?>>
									<?php echo esc_html( (string) $limit_opt ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="col-12 col-sm-6 col-lg-4">
						<label for="didban-filter-sort" class="form-label small didban-muted">مرتب‌سازی</label>
						<select id="didban-filter-sort" class="form-select" onchange="
							var parts = this.value.split('|');
							document.getElementById('didban-orderby-input').value = parts[0] || 'id';
							document.getElementById('didban-order-input').value = parts[1] || 'DESC';
						">
							<option value="id|DESC" <?php selected( $sort_value, 'id|DESC' ); ?>>جدیدترین موارد</option>
							<option value="product_name|ASC" <?php selected( $sort_value, 'product_name|ASC' ); ?>>نام محصول (الف - ی)</option>
							<option value="product_id|ASC" <?php selected( $sort_value, 'product_id|ASC' ); ?>>شناسه محصول (صعودی)</option>
							<option value="product_id|DESC" <?php selected( $sort_value, 'product_id|DESC' ); ?>>شناسه محصول (نزولی)</option>
							<option value="issue_type|ASC" <?php selected( $sort_value, 'issue_type|ASC' ); ?>>نوع مشکل</option>
							<option value="sku|ASC" <?php selected( $sort_value, 'sku|ASC' ); ?>>کد SKU</option>
						</select>
						<input type="hidden" id="didban-orderby-input" name="orderby" value="<?php echo esc_attr( (string) $filters['orderby'] ); ?>">
						<input type="hidden" id="didban-order-input" name="order" value="<?php echo esc_attr( (string) $filters['order'] ); ?>">
					</div>

					<div class="col-12 col-lg-8 d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
						<button type="submit" class="btn btn-info text-dark fw-bold px-4 rounded-pill">
							اعمال فیلتر
						</button>
						<?php if ( $has_filters ) : ?>
							<a href="<?php echo esc_url( $base_url ); ?>" class="btn btn-outline-light rounded-pill px-3">
								پاک‌سازی فیلترها
							</a>
						<?php endif; ?>
					</div>
				</form>
			</div>
		</section>

		<!-- Issues Table or Filtered Empty State -->
		<?php if ( empty( $items ) ) : ?>
			<section class="card didban-surface rounded-4">
				<div class="card-body p-5 text-center">
					<span class="dashicons dashicons-filter didban-muted fs-1 mb-3 d-block" aria-hidden="true"></span>
					<h2 class="h6 fw-bold mb-1 text-white">موردی با این فیلترها یافت نشد</h2>
					<p class="didban-muted small mb-3">
						عبارت جستجو یا فیلترهای انتخاب‌شده را تغییر دهید یا پاک‌سازی کنید.
					</p>
					<?php if ( $has_filters ) : ?>
						<a href="<?php echo esc_url( $base_url ); ?>" class="btn btn-outline-info rounded-pill px-4">
							نمایش همه مشکلات
						</a>
					<?php endif; ?>
				</div>
			</section>
		<?php else : ?>
			<section class="card didban-surface rounded-4 overflow-hidden">
				<div class="table-responsive">
					<table class="table didban-table align-middle mb-0">
						<thead>
							<tr>
								<th class="px-4 py-3 small">نام محصول</th>
								<th class="py-3 small">شناسه محصول</th>
								<th class="py-3 small">ویژگی‌های تنوع</th>
								<th class="py-3 small">شناسه تنوع</th>
								<th class="py-3 small">کد SKU</th>
								<th class="py-3 small">نوع مشکل</th>
								<th class="px-4 py-3 text-end small">عملیات</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $items as $issue ) : ?>
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
									<td class="px-4 py-3 fw-semibold text-white">
										<?php echo esc_html( (string) $issue['product_name'] ); ?>
									</td>
									<td class="py-3 didban-muted">
										#<?php echo esc_html( (string) $issue['product_id'] ); ?>
									</td>
									<td class="py-3 small text-white">
										<?php echo esc_html( $attr_text ); ?>
									</td>
									<td class="py-3 didban-muted">
										<?php echo $var_id > 0 ? '#' . esc_html( (string) $var_id ) : '—'; ?>
									</td>
									<td class="py-3">
										<span class="badge didban-pill-neutral font-monospace">
											<?php echo esc_html( $sku_text ); ?>
										</span>
									</td>
									<td class="py-3">
										<span class="badge didban-tint-<?php echo esc_attr( $badge_color ); ?> rounded-pill px-3 py-2">
											<?php echo esc_html( $meta['label'] ); ?>
										</span>
									</td>
									<td class="px-4 py-3 text-end">
										<?php if ( $edit_url ) : ?>
											<a href="<?php echo esc_url( $edit_url ); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info rounded-pill px-3">
												ویرایش در ووکامرس
											</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<?php if ( $total_pages > 1 ) : ?>
					<div class="card-footer bg-transparent border-top border-secondary py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
						<span class="didban-muted small">
							صفحه <?php echo esc_html( (string) $paged ); ?> از <?php echo esc_html( (string) $total_pages ); ?>
						</span>

						<nav aria-label="صفحه‌بندی مشکلات">
							<ul class="pagination pagination-sm mb-0">
								<?php
								$query_args = array(
									'page'        => Didban_Admin::ISSUES_SLUG,
									'search'      => $filters['search'],
									'issue_type'  => $filters['issue_type'],
									'category_id' => $filters['category_id'],
									'orderby'     => $filters['orderby'],
									'order'       => $filters['order'],
									'per_page'    => $filters['per_page'],
								);

								$prev_url = add_query_arg( array_merge( $query_args, array( 'paged' => max( 1, $paged - 1 ) ) ), admin_url( 'admin.php' ) );
								$next_url = add_query_arg( array_merge( $query_args, array( 'paged' => min( $total_pages, $paged + 1 ) ) ), admin_url( 'admin.php' ) );
								?>
								<li class="page-item <?php echo $paged <= 1 ? 'disabled' : ''; ?>">
									<a class="page-link" href="<?php echo esc_url( $prev_url ); ?>">قبلی</a>
								</li>

								<?php
								$start_page = max( 1, $paged - 2 );
								$end_page   = min( $total_pages, $paged + 2 );
								for ( $p = $start_page; $p <= $end_page; $p++ ) :
									$page_url = add_query_arg( array_merge( $query_args, array( 'paged' => $p ) ), admin_url( 'admin.php' ) );
									?>
									<li class="page-item <?php echo $p === $paged ? 'active' : ''; ?>">
										<a class="page-link" href="<?php echo esc_url( $page_url ); ?>"><?php echo esc_html( (string) $p ); ?></a>
									</li>
								<?php endfor; ?>

								<li class="page-item <?php echo $paged >= $total_pages ? 'disabled' : ''; ?>">
									<a class="page-link" href="<?php echo esc_url( $next_url ); ?>">بعدی</a>
								</li>
							</ul>
						</nav>
					</div>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	<?php endif; ?>

</div>