<?php
/**
 * Standalone Didban panel header partial (Bootstrap 5 RTL).
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>پیشخوان دیدبان | <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( DIDBAN_URL . 'admin/assets/css/bootstrap.rtl.min.css?ver=5.3.3' ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( includes_url( 'css/dashicons.min.css' ) ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( DIDBAN_URL . 'admin/assets/css/admin.css?ver=' . DIDBAN_VERSION ); ?>">
</head>
<body class="didban-body d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark didban-navbar shadow-sm sticky-top">
	<div class="container">
		<a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Didban_Admin::MENU_SLUG ) ); ?>">
			<span class="dashicons dashicons-visibility text-info" aria-hidden="true"></span>
			<span>دیدبان</span>
			<span class="badge bg-primary rounded-pill fw-normal fs-6">نسخه <?php echo esc_html( DIDBAN_VERSION ); ?></span>
		</a>

		<div class="d-flex flex-wrap align-items-center gap-3 me-auto">
			<?php $active_tab = $current_page ?? 'dashboard'; ?>
			<ul class="navbar-nav flex-row gap-3">
				<li class="nav-item">
					<a class="nav-link <?php echo 'dashboard' === $active_tab ? 'active fw-semibold' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Didban_Admin::MENU_SLUG ) ); ?>">
						پیشخوان
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?php echo 'issues' === $active_tab ? 'active fw-semibold' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Didban_Admin::ISSUES_SLUG ) ); ?>">
						گزارش مشکلات
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?php echo 'settings' === $active_tab ? 'active fw-semibold' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Didban_Admin::SETTINGS_SLUG ) ); ?>">
						تنظیمات
					</a>
				</li>
			</ul>
		</div>

		<div class="d-flex align-items-center gap-2">
			<span class="badge bg-secondary-subtle text-light border border-secondary px-3 py-2 d-none d-md-inline-block">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
			</span>
			<a href="<?php echo esc_url( admin_url() ); ?>" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-1">
				<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
				<span>بازگشت به وردپرس</span>
			</a>
		</div>
	</div>
</nav>

<main class="container py-4 flex-grow-1">