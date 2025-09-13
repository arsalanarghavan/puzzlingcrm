<?php
/**
 * System Manager Dashboard Template - FULLY COMPLETED AND UPGRADED with Caching
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_tab = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'overview';

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
    ];
    // Cache the stats for 1 hour.
    set_transient( 'puzzling_system_manager_stats', $stats, HOUR_IN_SECONDS );
}

$base_url = puzzling_get_dashboard_url();
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
</div>

<div class="pzl-dashboard-tabs">
    <a href="<?php echo esc_url(add_query_arg('view', 'overview', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>"> <span class="dashicons dashicons-dashboard"></span> <?php esc_html_e('Overview', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('view', 'projects', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'projects' ? 'active' : ''; ?>"> <span class="dashicons dashicons-portfolio"></span> <?php esc_html_e('Manage Projects', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('view', 'contracts', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'contracts' ? 'active' : ''; ?>"> <span class="dashicons dashicons-media-text"></span> <?php esc_html_e('Manage Contracts', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('view', 'tickets', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'tickets' ? 'active' : ''; ?>"> <span class="dashicons dashicons-sos"></span> <?php esc_html_e('Support', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('view', 'logs', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'logs' ? 'active' : ''; ?>"> <span class="dashicons dashicons-list-view"></span> <?php esc_html_e('Event Logs', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('view', 'settings', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>"> <span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Settings', 'puzzlingcrm'); ?></a>
</div>

<div class="pzl-dashboard-tab-content">
    <?php
    $template_map = [
        'projects' => 'page-projects',
        'contracts' => 'page-contracts',
        'tickets' => 'list-tickets',
        'logs' => 'view-logs',
        'settings' => 'page-settings',
    ];

    if (isset($template_map[$active_tab]) && file_exists(PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $template_map[$active_tab] . '.php')) {
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $template_map[$active_tab] . '.php';
    } else {
        // Overview is the default
        echo '<h4>' . esc_html__('Welcome to the System Management Dashboard.', 'puzzlingcrm') . '</h4>';
        echo '<p>' . esc_html__('From this panel, you can get an overview of the system, manage projects and contracts, and configure the plugin settings.', 'puzzlingcrm') . '</p>';
    }
    ?>
</div>