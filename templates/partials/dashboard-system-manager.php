<?php
/**
 * System Manager Dashboard Template - FULLY COMPLETED AND UPGRADED
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_tab = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'overview';

// Stats for widgets - This logic can be cached using transients for better performance in the future.
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
$base_url = puzzling_get_dashboard_url();
?>

<div class="pzl-dashboard-stats">
    <div class="stat-widget">
        <h4><?php esc_html_e('Total Projects', 'puzzlingcrm'); ?></h4>
        <span class="stat-number"><?php echo esc_html($total_projects); ?></span>
    </div>
    <div class="stat-widget">
        <h4><?php esc_html_e('Active Tasks', 'puzzlingcrm'); ?></h4>
        <span class="stat-number"><?php echo esc_html($active_tasks_count); ?></span>
    </div>
    <div class="stat-widget">
        <h4><?php esc_html_e('Pending Installments', 'puzzlingcrm'); ?></h4>
        <span class="stat-number"><?php echo esc_html($pending_installments); ?></span>
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

    if (isset($template_map[$active_tab])) {
        $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $template_map[$active_tab] . '.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    } else {
        // Overview is the default
        echo '<h4>' . esc_html__('Welcome to the System Management Dashboard.', 'puzzlingcrm') . '</h4>';
        echo '<p>' . esc_html__('From this panel, you can get an overview of the system, manage projects and contracts, and configure the plugin settings.', 'puzzlingcrm') . '</p>';
    }
    ?>
</div>

<style>
/* This inline style is kept for simplicity, but ideally should be in the main CSS file */
.pzl-dashboard-tabs { border-bottom: 1px solid #ddd; margin-bottom: 25px; display: flex; flex-wrap: wrap; }
.pzl-tab { padding: 12px 20px; text-decoration: none; color: #555; border-bottom: 3px solid transparent; margin-bottom: -1px; font-weight: 500; transition: color 0.3s, border-color 0.3s; }
.pzl-tab.active, .pzl-tab:hover { color: var(--primary-color, #F0192A); border-bottom-color: var(--primary-color, #F0192A); font-weight: 600; }
.pzl-tab .dashicons { vertical-align: middle; margin-left: 8px; }
.pzl-dashboard-stats { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
.stat-widget { flex: 1; min-width: 200px; background: #fff; border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px; text-align: center; transition: transform 0.3s, box-shadow 0.3s; }
.stat-widget:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
.stat-widget h4 { margin: 0 0 10px; font-size: 16px; color: #555; font-weight: 600; }
.stat-widget .stat-number { font-size: 36px; font-weight: 700; color: var(--primary-color, #F0192A); }
</style>