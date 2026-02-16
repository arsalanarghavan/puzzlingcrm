<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_key( $_GET[ 'tab' ] ) : 'payment';
$base_url = remove_query_arg('puzzling_notice');
?>
<div class="pzl-dashboard-section">
    <h3><i class="ri-settings-3-line"></i> تنظیمات</h3>
    
    <div class="pzl-dashboard-tabs">
        <a href="<?php echo add_query_arg('tab', 'authentication', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'authentication' ? 'active' : ''; ?>"><i class="ri-shield-keyhole-line"></i> احراز هویت</a>
        <a href="<?php echo add_query_arg('tab', 'style', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'style' ? 'active' : ''; ?>"><i class="ri-palette-line"></i> ظاهر و استایل</a>
        <a href="<?php echo add_query_arg('tab', 'payment', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'payment' ? 'active' : ''; ?>"><i class="ri-bank-card-line"></i> درگاه پرداخت</a>
        <a href="<?php echo add_query_arg('tab', 'sms', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'sms' ? 'active' : ''; ?>"><i class="ri-message-3-line"></i> سامانه پیامک</a>
        <a href="<?php echo add_query_arg('tab', 'workflow', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'workflow' ? 'active' : ''; ?>"><i class="ri-git-branch-line"></i> گردش کار</a>
        <a href="<?php echo add_query_arg('tab', 'positions', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'positions' ? 'active' : ''; ?>"><i class="ri-organization-chart"></i> جایگاه‌های شغلی</a>
        <a href="<?php echo add_query_arg('tab', 'task_categories', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'task_categories' ? 'active' : ''; ?>"><i class="ri-price-tag-3-line"></i> دسته‌بندی وظایف</a>
        <a href="<?php echo add_query_arg('tab', 'automations', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'automations' ? 'active' : ''; ?>"><i class="ri-robot-line"></i> اتوماسیون</a>
        <a href="<?php echo add_query_arg('tab', 'notifications', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'notifications' ? 'active' : ''; ?>"><i class="ri-notification-3-line"></i> اطلاع‌رسانی‌ها</a>
        <a href="<?php echo add_query_arg('tab', 'forms', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'forms' ? 'active' : ''; ?>"><i class="ri-file-list-3-line"></i> فرم‌ها</a>
        <a href="<?php echo add_query_arg('tab', 'canned_responses', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'canned_responses' ? 'active' : ''; ?>"><i class="ri-chat-quote-line"></i> پاسخ‌های آماده</a>
        <a href="<?php echo add_query_arg('tab', 'leads', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'leads' ? 'active' : ''; ?>"><i class="ri-user-add-line"></i> وضعیت‌های لید</a>
        <a href="<?php echo add_query_arg('tab', 'log_debug', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'log_debug' ? 'active' : ''; ?>"><i class="ri-bug-line"></i> لاگ و دیباگ</a>
        <a href="<?php echo add_query_arg('tab', 'visitor_statistics', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'visitor_statistics' ? 'active' : ''; ?>"><i class="ri-line-chart-line"></i> آمار بازدید</a>
    </div>

    <div class="pzl-dashboard-tab-content">
        <div class="pzl-card">
        <?php
        if ( $active_tab == 'authentication' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-authentication.php';
        } elseif ( $active_tab == 'style' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-style.php';
        } elseif ( $active_tab == 'sms' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-sms.php';
        } elseif ( $active_tab == 'workflow' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-workflow.php';
        } elseif ( $active_tab == 'positions' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-positions.php';
        } elseif ( $active_tab == 'task_categories' ) {
            $task_categories_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-task-categories.php';
            if (file_exists($task_categories_path)) {
                include $task_categories_path;
            } else {
                 echo '<p>فایل تنظیمات دسته‌بندی وظایف یافت نشد.</p>';
            }
        } elseif ( $active_tab == 'automations' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-automations.php';
        } elseif ( $active_tab == 'forms' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-forms.php';
        } elseif ( $active_tab == 'notifications' ) {
            $notification_settings_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-notifications.php';
            if (file_exists($notification_settings_path)) {
                include $notification_settings_path;
            } else {
                echo '<p>فایل تنظیمات اطلاع‌رسانی یافت نشد.</p>';
            }
        } elseif ( $active_tab == 'canned_responses' ) {
            // Include the new template for canned responses management
            $canned_responses_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-canned-responses.php';
            if (file_exists($canned_responses_path)) {
                include $canned_responses_path;
            } else {
                echo '<p>فایل تنظیمات پاسخ‌های آماده یافت نشد.</p>';
            }
        } elseif ($active_tab == 'leads') {
            $lead_settings_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-leads.php';
            if (file_exists($lead_settings_path)) {
                include $lead_settings_path;
            } else {
                echo '<p>فایل تنظیمات وضعیت‌های لید یافت نشد.</p>';
            }
        } elseif ( $active_tab == 'log_debug' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-log-debug.php';
        } elseif ( $active_tab == 'visitor_statistics' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-visitor-statistics.php';
        } else { // Default to 'payment'
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-payment.php';
        }
        ?>
        </div>
    </div>
</div>