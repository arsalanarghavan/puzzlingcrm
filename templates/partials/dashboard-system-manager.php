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
?>

<div class="pzl-dashboard-stats">
    <div class="stat-widget">
        <h4>کل پروژه‌ها</h4>
        <span class="stat-number"><?php echo esc_html($total_projects); ?></span>
    </div>
    <div class="stat-widget">
        <h4>تسک‌های فعال</h4>
        <span class="stat-number"><?php echo esc_html($active_tasks_count); ?></span>
    </div>
    <div class="stat-widget">
        <h4>اقساط در انتظار پرداخت</h4>
        <span class="stat-number"><?php echo esc_html($pending_installments); ?></span>
    </div>
</div>

<div class="pzl-dashboard-tabs">
    <a href="?view=overview" class="pzl-tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>"> <span class="dashicons dashicons-dashboard"></span> نمای کلی </a>
    <a href="?view=contracts" class="pzl-tab <?php echo $active_tab === 'contracts' ? 'active' : ''; ?>"> <span class="dashicons dashicons-media-text"></span> مدیریت قراردادها </a>
    <a href="?view=tickets" class="pzl-tab <?php echo $active_tab === 'tickets' ? 'active' : ''; ?>"> <span class="dashicons dashicons-sos"></span> پشتیبانی </a>
    <a href="?view=logs" class="pzl-tab <?php echo $active_tab === 'logs' ? 'active' : ''; ?>"> <span class="dashicons dashicons-list-view"></span> لاگ رویدادها </a>
    <a href="?view=settings" class="pzl-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>"> <span class="dashicons dashicons-admin-settings"></span> تنظیمات </a>
</div>

<div class="pzl-dashboard-tab-content">
    <?php
    if ($active_tab === 'settings') {
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/dashboard-settings.php';
    } elseif ($active_tab === 'contracts') {
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/common/contract-form.php';
    } elseif ($active_tab === 'tickets') {
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/list-tickets.php';
    } elseif ($active_tab === 'logs') {
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/view-logs.php';
    } else {
        echo '<h4>به داشبورد مدیریت سیستم خوش آمدید.</h4><p>از این پنل می‌توانید نمای کلی سیستم را مشاهده کنید، قراردادها را مدیریت کرده و تنظیمات پلاگین را پیکربندی نمایید.</p>';
    }
    ?>
</div>

<style>
/* This inline style is kept for simplicity, but ideally should be in the main CSS file */
.pzl-dashboard-tabs { border-bottom: 1px solid #ddd; margin-bottom: 25px; display: flex; }
.pzl-tab { padding: 12px 20px; text-decoration: none; color: #555; border-bottom: 3px solid transparent; margin-bottom: -1px; font-weight: 500; transition: color 0.3s, border-color 0.3s; }
.pzl-tab.active, .pzl-tab:hover { color: var(--primary-color, #F0192A); border-bottom-color: var(--primary-color, #F0192A); font-weight: 600; }
.pzl-tab .dashicons { vertical-align: middle; margin-left: 8px; }
.pzl-dashboard-stats { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
.stat-widget { flex: 1; min-width: 200px; background: #fff; border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px; text-align: center; transition: transform 0.3s, box-shadow 0.3s; }
.stat-widget:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
.stat-widget h4 { margin: 0 0 10px; font-size: 16px; color: #555; font-weight: 600; }
.stat-widget .stat-number { font-size: 36px; font-weight: 700; color: var(--primary-color, #F0192A); }
</style>