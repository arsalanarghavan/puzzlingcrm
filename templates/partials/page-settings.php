<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_key($_GET[ 'tab' ]) : 'payment';
$base_url = remove_query_arg('puzzling_notice');
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-cog"></i> تنظیمات</h3>
    
    <div class="pzl-dashboard-tabs">
        <a href="<?php echo add_query_arg('tab', 'payment', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'payment' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> درگاه پرداخت</a>
        <a href="<?php echo add_query_arg('tab', 'sms', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'sms' ? 'active' : ''; ?>"><i class="fas fa-sms"></i> سامانه پیامک</a>
        <a href="<?php echo add_query_arg('tab', 'workflow', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'workflow' ? 'active' : ''; ?>"><i class="fas fa-project-diagram"></i> گردش کار</a>
        <a href="<?php echo add_query_arg('tab', 'automations', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'automations' ? 'active' : ''; ?>"><i class="fas fa-robot"></i> اتوماسیون</a>
        <a href="<?php echo add_query_arg('tab', 'forms', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'forms' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> فرم‌ها</a>
    </div>

    <div class="pzl-dashboard-tab-content">
        <div class="pzl-card">
        <?php
        if ( $active_tab == 'sms' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-sms.php';
        } elseif ( $active_tab == 'workflow' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-workflow.php';
        } elseif ( $active_tab == 'automations' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-automations.php';
        } elseif ( $active_tab == 'forms' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-forms.php';
        } else {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-payment.php';
        }
        ?>
        </div>
    </div>
</div>