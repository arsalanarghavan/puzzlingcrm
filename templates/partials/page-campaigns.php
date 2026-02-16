<?php
/**
 * Campaigns page - Coming Soon placeholder
 *
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'manage_options' ) ) {
	echo '<p>' . esc_html__( 'You do not have permission to view this page.', 'puzzlingcrm' ) . '</p>';
	return;
}
?>
<div class="pzl-dashboard-section">
	<div class="pzl-card text-center py-5">
		<i class="ri-megaphone-line fs-1 text-muted mb-3"></i>
		<h3><?php esc_html_e( 'کمپین‌ها', 'puzzlingcrm' ); ?></h3>
		<p class="text-muted mb-0"><?php esc_html_e( 'به زودی...', 'puzzlingcrm' ); ?></p>
	</div>
</div>
