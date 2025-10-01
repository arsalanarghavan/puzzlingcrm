<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_key( $_GET[ 'tab' ] ) : 'payment';
$base_url = remove_query_arg('puzzling_notice');
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-cog"></i> تنظیمات</h3>
    
    <div class="pzl-dashboard-tabs">
        <a href="<?php echo add_query_arg('tab', 'payment', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'payment' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> درگاه پرداخت</a>
        <a href="<?php echo add_query_arg('tab', 'sms', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'sms' ? 'active' : ''; ?>"><i class="fas fa-sms"></i> سامانه پیامک</a>
        <a href="<?php echo add_query_arg('tab', 'workflow', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'workflow' ? 'active' : ''; ?>"><i class="fas fa-project-diagram"></i> گردش کار</a>
        <a href="<?php echo add_query_arg('tab', 'positions', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'positions' ? 'active' : ''; ?>"><i class="fas fa-sitemap"></i> جایگاه‌های شغلی</a>
        <a href="<?php echo add_query_arg('tab', 'task_categories', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'task_categories' ? 'active' : ''; ?>"><i class="fas fa-tags"></i> دسته‌بندی وظایف</a>
        <a href="<?php echo add_query_arg('tab', 'automations', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'automations' ? 'active' : ''; ?>"><i class="fas fa-robot"></i> اتوماسیون</a>
        <a href="<?php echo add_query_arg('tab', 'notifications', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'notifications' ? 'active' : ''; ?>"><i class="fas fa-bell"></i> اطلاع‌رسانی‌ها</a>
        <a href="<?php echo add_query_arg('tab', 'forms', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'forms' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> فرم‌ها</a>
        <a href="<?php echo add_query_arg('tab', 'canned_responses', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'canned_responses' ? 'active' : ''; ?>"><i class="fas fa-comment-dots"></i> پاسخ‌های آماده</a>
    </div>

    <div class="pzl-dashboard-tab-content">
        <div class="pzl-card">
        <?php
        if ( $active_tab == 'sms' ) {
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
        } else { // Default to 'payment'
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-payment.php';
        }
        ?>
        </div>
    </div>
</div>