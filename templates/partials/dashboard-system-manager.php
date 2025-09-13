<?php
/**
 * System Manager Dashboard Template - VISUALLY REVAMPED & ERROR FIXED
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

    // FATAL ERROR FIX: Check if WooCommerce Subscriptions function exists before calling it.
    $active_subscriptions = 0;
    if ( function_exists('wcs_get_subscription_count') ) {
        $active_subscriptions = wcs_get_subscription_count( 'active' );
    }

    $stats = [
        'total_projects' => $total_projects,
        'active_tasks_count' => $active_tasks_count,
        'pending_installments' => $pending_installments,
        'active_subscriptions' => $active_subscriptions,
    ];
    set_transient( 'puzzling_system_manager_stats', $stats, HOUR_IN_SECONDS );
}
?>

<div class="pzl-dashboard-stats-grid">
    <div class="stat-widget-card gradient-1">
        <div class="stat-widget-icon">
            <span class="dashicons dashicons-portfolio"></span>
        </div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['total_projects']); ?></span>
            <span class="stat-title"><?php esc_html_e('پروژه کل', 'puzzlingcrm'); ?></span>
        </div>
    </div>
    <div class="stat-widget-card gradient-2">
        <div class="stat-widget-icon">
            <span class="dashicons dashicons-marker"></span>
        </div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['active_tasks_count']); ?></span>
            <span class="stat-title"><?php esc_html_e('وظایف فعال', 'puzzlingcrm'); ?></span>
        </div>
    </div>
    <div class="stat-widget-card gradient-3">
        <div class="stat-widget-icon">
            <span class="dashicons dashicons-money-alt"></span>
        </div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['pending_installments']); ?></span>
            <span class="stat-title"><?php esc_html_e('اقساط در انتظار', 'puzzlingcrm'); ?></span>
        </div>
    </div>
    <div class="stat-widget-card gradient-4">
        <div class="stat-widget-icon">
            <span class="dashicons dashicons-update-alt"></span>
        </div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['active_subscriptions']); ?></span>
            <span class="stat-title"><?php esc_html_e('اشتراک‌های فعال', 'puzzlingcrm'); ?></span>
        </div>
    </div>
</div>

<div class="pzl-dashboard-section pzl-card">
    <h3><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e('راهنمای مدیریت سیستم', 'puzzlingcrm'); ?></h3>
    <p><?php esc_html_e('به پنل مدیریت سیستم خوش آمدید. برای مدیریت بخش‌های مختلف، برگه‌های جدیدی در وردپرس بسازید و از شورت‌کدهای زیر در آن‌ها استفاده کنید تا پنل کاربری خود را به دلخواه بچینید.', 'puzzlingcrm'); ?></p>
    
    <h4><?php esc_html_e('لیست شورت‌کدهای مدیریتی:', 'puzzlingcrm'); ?></h4>
    <ul class="pzl-shortcode-list">
        <li><code>[puzzling_projects]</code> - <?php esc_html_e('مدیریت پروژه‌ها', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_contracts]</code> - <?php esc_html_e('مدیریت قراردادها', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_tasks]</code> - <?php esc_html_e('مدیریت وظایف کل سیستم', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_customers]</code> - <?php esc_html_e('مدیریت مشتریان', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_staff]</code> - <?php esc_html_e('مدیریت کارکنان', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_subscriptions]</code> - <?php esc_html_e('مشاهده اشتراک‌های ووکامرس', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_appointments]</code> - <?php esc_html_e('مدیریت قرار ملاقات‌ها', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_pro_invoices]</code> - <?php esc_html_e('مدیریت پیش‌فاکتورها', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_tickets]</code> - <?php esc_html_e('مدیریت تیکت‌های پشتیبانی', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_reports]</code> - <?php esc_html_e('مشاهده گزارش‌ها', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_logs]</code> - <?php esc_html_e('مشاهده لاگ رویدادها', 'puzzlingcrm'); ?></li>
        <li><code>[puzzling_settings]</code> - <?php esc_html_e('تنظیمات پلاگین', 'puzzlingcrm'); ?></li>
    </ul>
</div>