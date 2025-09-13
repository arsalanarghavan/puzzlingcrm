<?php
/**
 * System Manager Dashboard Template - FULLY COMPLETED AND UPGRADED with Caching
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Stats for widgets - Cached using transients for better performance.
if ( false === ( $stats = get_transient( 'puzzling_system_manager_stats' ) ) ) {
    $total_projects = wp_count_posts('project')->publish;

    $active_tasks_query = new WP_Query([
        'post_type' => 'task', 
        'post_status' => 'publish', 
        'posts_per_page' => -1, 
        'tax_query' => [[
            'taxonomy' => 'task_status', 
            'field' => 'slug', 
            'terms' => 'done', 
            'operator' => 'NOT IN'
        ]]
    ]);
    $active_tasks_count = $active_tasks_query->post_count;

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
    // Cache the stats for 1 hour.
    set_transient( 'puzzling_system_manager_stats', $stats, HOUR_IN_SECONDS );
}

?>

<div class="pzl-dashboard-stats">
    <div class="stat-widget">
        <h4><?php esc_html_e('Total Projects', 'puzzlingcrm'); ?></h4>
        <span class="stat-number"><?php echo esc_html($stats['total_projects']); ?></span>
    </div>
    <div class="stat-widget">
        <h4><?php esc_html_e('Active Tasks', 'puzzlingcrm'); ?></h4>
        <span class="stat-number"><?php echo esc_html($stats['active_tasks_count']); ?></span>
    </div>
    <div class="stat-widget">
        <h4><?php esc_html_e('Pending Installments', 'puzzlingcrm'); ?></h4>
        <span class="stat-number"><?php echo esc_html($stats['pending_installments']); ?></span>
    </div>
    <div class="stat-widget">
        <h4><?php esc_html_e('Active Subscriptions', 'puzzlingcrm'); ?></h4>
        <span class="stat-number"><?php echo esc_html($stats['active_subscriptions']); ?></span>
    </div>
</div>

<div class="pzl-dashboard-section">
    <h3><?php esc_html_e('System Overview', 'puzzlingcrm'); ?></h3>
    <p><?php esc_html_e('Welcome to the System Management Dashboard. From this panel, you can get a quick overview of the system status.', 'puzzlingcrm'); ?></p>
    <p><?php esc_html_e('To manage different parts of the CRM, please create separate pages and use the corresponding shortcodes. This provides greater flexibility in how you structure your admin area.', 'puzzlingcrm'); ?></p>
    
    <h4><?php esc_html_e('Available Management Shortcodes:', 'puzzlingcrm'); ?></h4>
    <ul>
        <li><code>[puzzling_projects]</code> - <?php esc_html_e('Manage all projects', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_contracts]</code> - <?php esc_html_e('Manage all contracts', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_tasks]</code> - <?php esc_html_e('Manage all tasks in the system', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_customers]</code> - <?php esc_html_e('Manage customer accounts', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_staff]</code> - <?php esc_html_e('Manage staff accounts', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_subscriptions]</code> - <?php esc_html_e('View WooCommerce customer subscriptions', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_appointments]</code> - <?php esc_html_e('Manage appointments', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_tickets_manager]</code> - <?php esc_html_e('Manage all support tickets', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_reports]</code> - <?php esc_html_e('View financial and task reports', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_logs]</code> - <?php esc_html_e('View system event logs', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_settings]</code> - <?php esc_html_e('Configure plugin settings', 'puzzlingcrm'); ?></li>
    </ul>
</div>