<?php
/**
 * Client Dashboard Template
 *
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$current_user = wp_get_current_user();
$customer_id = $current_user->ID;
?>

<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-portfolio" style="vertical-align: middle;"></span> <?php esc_html_e('Your Projects', 'puzzlingcrm'); ?></h3>
    <?php
    $projects = get_posts([
        'post_type' => 'project',
        'author' => $customer_id,
        'posts_per_page' => -1,
    ]);

    if (empty($projects)) {
        echo '<p>' . esc_html__('You currently have no projects.', 'puzzlingcrm') . '</p>';
    } else {
        foreach ($projects as $project) {
            echo '<h4>' . esc_html($project->post_title) . '</h4>';
        }
    }
    ?>
</div>

<hr style="margin: 30px 0;">

<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-money-alt" style="vertical-align: middle;"></span> <?php esc_html_e('Payments & Installments Status', 'puzzlingcrm'); ?></h3>
    <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/common/payments-table.php'; ?>
</div>

<hr style="margin: 30px 0;">

<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-sos" style="vertical-align: middle;"></span> <?php esc_html_e('Support & Tickets', 'puzzlingcrm'); ?></h3>
    <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/list-tickets.php'; ?>
</div>

<style>
.pzl-dashboard-section { margin-bottom: 30px; }
.pzl-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
.pzl-table th, .pzl-table td { padding: 12px 15px; border: 1px solid #e0e0e0; text-align: right; vertical-align: middle; }
.pzl-table th { background-color: #f9f9f9; font-weight: bold; }
.pzl-status { display: inline-block; padding: 5px 10px; border-radius: 15px; font-size: 12px; color: #fff; min-width: 90px; text-align: center; }
.status-paid { background-color: var(--success-color, #28a745); }
.status-pending { background-color: var(--warning-color, #ffc107); color: #333; }
</style>