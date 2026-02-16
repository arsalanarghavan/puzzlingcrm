<?php
/**
 * Services & Products page - Fallback for non-SPA/embed.
 * Main content is rendered by React ServicesPage in SPA mode.
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
<div class="pzl-dashboard-section" data-pzl-services-placeholder>
	<div class="pzl-card">
		<h3><i class="ri-service-line"></i> <?php esc_html_e( 'Services & Products', 'puzzlingcrm' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Manage WooCommerce subscriptions and link products to task templates. Use the dashboard sidebar to access the full Services page.', 'puzzlingcrm' ); ?>
		</p>
		<p class="text-muted">
			<?php esc_html_e( 'If you see this message, the React dashboard may still be loading.', 'puzzlingcrm' ); ?>
		</p>
	</div>
</div>
