<?php
/**
 * System Manager Dashboard Template - Updated with Consolidated Shortcode Info
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Stats for widgets - Cached using transients for better performance.
if ( false === ( $stats = get_transient( 'puzzling_system_manager_stats' ) ) ) {
    $total_projects = wp_count_posts('project')->publish;
    $active_tasks_count = count(get_posts(['post_type' => 'task', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done', 'operator' => 'NOT IN']]]));
    $pending_installments = 0;
    $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);
    if ($contracts) {
        foreach($contracts as $contract){
            $installments = get_post_meta($contract->ID, '_installments', true);
            if(is_array($installments)){
                foreach($installments as $inst){
                    if(isset($inst['status']) && $inst['status'] === 'pending') {
                        $pending_installments++;
                    }
                }
            }
        }
    }
    $stats = [
        'total_projects' => $total_projects,
        'active_tasks_count' => $active_tasks_count,
        'pending_installments' => $pending_installments,
        'active_subscriptions' => class_exists('WC_Subscriptions') ? wcs_get_subscription_count( 'active' ) : 0,
    ];
    set_transient( 'puzzling_system_manager_stats', $stats, HOUR_IN_SECONDS );
}
?>

<div class="pzl-dashboard-stats">
    <div class="stat-widget"><h4><?php esc_html_e('Total Projects', 'puzzlingcrm'); ?></h4><span class="stat-number"><?php echo esc_html($stats['total_projects']); ?></span></div>
    <div class="stat-widget"><h4><?php esc_html_e('Active Tasks', 'puzzlingcrm'); ?></h4><span class="stat-number"><?php echo esc_html($stats['active_tasks_count']); ?></span></div>
    <div class="stat-widget"><h4><?php esc_html_e('Pending Installments', 'puzzlingcrm'); ?></h4><span class="stat-number"><?php echo esc_html($stats['pending_installments']); ?></span></div>
    <div class="stat-widget"><h4><?php esc_html_e('Active Subscriptions', 'puzzlingcrm'); ?></h4><span class="stat-number"><?php echo esc_html($stats['active_subscriptions']); ?></span></div>
</div>

<div class="pzl-dashboard-section">
    <h3><?php esc_html_e('System Overview', 'puzzlingcrm'); ?></h3>
    <p><?php esc_html_e('Welcome to the System Management Dashboard. Use the shortcodes below to build your management pages.', 'puzzlingcrm'); ?></p>
    
    <h4><?php esc_html_e('Available Management Shortcodes:', 'puzzlingcrm'); ?></h4>
    <ul>
        <li><code>[puzzling_projects]</code> - <?php esc_html_e('Manage projects', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_contracts]</code> - <?php esc_html_e('Manage contracts', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_tasks]</code> - <?php esc_html_e('Manage all system tasks', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_customers]</code> - <?php esc_html_e('Manage customer accounts', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_staff]</code> - <?php esc_html_e('Manage staff accounts', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_subscriptions]</code> - <?php esc_html_e('View WooCommerce customer subscriptions', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_appointments]</code> - <?php esc_html_e('Manage appointments', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_pro_invoices]</code> - <?php esc_html_e('Manage pro-forma invoices', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_tickets]</code> - <?php esc_html_e('Manage all support tickets', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_reports]</code> - <?php esc_html_e('View financial and task reports', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_logs]</code> - <?php esc_html_e('View system event logs', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_settings]</code> - <?php esc_html_e('Configure plugin settings', 'puzzlingcrm'); ?></li>
    </ul>
</div>