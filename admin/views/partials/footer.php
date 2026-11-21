<?php
/**
 * Standalone Didban panel footer partial (Bootstrap 5 RTL).
 *
 * @package Didban
 */

defined( 'ABSPATH' ) || exit;

$didban_js_config = array(
	'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
	'nonce'           => wp_create_nonce( Didban_Ajax::NONCE_ACTION ),
	'isScanning'      => ! empty( $is_scanning ),
	'initialProgress' => (float) ( $scan_progress ?? 0 ),
);
?>
</main>

<footer class="py-3 mt-auto">
	<div class="container d-flex flex-wrap align-items-center justify-content-between gap-2 text-muted small">
		<span>دیدبان — ابزار پایش سلامت تنوع محصولات ووکامرس</span>
		<span>نسخه <?php echo esc_html( DIDBAN_VERSION ); ?></span>
	</div>
</footer>

<script>
window.didbanData = <?php echo wp_json_encode( $didban_js_config ); ?>;
</script>
<script src="<?php echo esc_url( DIDBAN_URL . 'admin/assets/js/admin.js?ver=' . DIDBAN_VERSION ); ?>"></script>
</body>
</html>