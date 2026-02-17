<?php
/**
 * Accounting module placeholder (content is handled by React SPA when available).
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="pzl-dashboard-section pzl-accounting-placeholder">
	<h3><i class="ri-calculator-line"></i> <?php esc_html_e( 'حسابداری', 'puzzlingcrm' ); ?></h3>
	<p class="description"><?php esc_html_e( 'ماژول حسابداری. در صورت استفاده از داشبورد React، این بخش از طریق منوی سمت راست در دسترس است.', 'puzzlingcrm' ); ?></p>
	<p><a href="<?php echo esc_url( home_url( '/dashboard/accounting' ) ); ?>" class="pzl-button"><?php esc_html_e( 'داشبورد حسابداری', 'puzzlingcrm' ); ?></a></p>
</div>
