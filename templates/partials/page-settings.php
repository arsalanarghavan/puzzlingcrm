<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_key($_GET[ 'tab' ]) : 'payment';
$base_url = remove_query_arg('puzzling_notice');
?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-admin-settings"></span> تنظیمات</h3>
    
    <div class="pzl-dashboard-tabs">
        <a href="<?php echo add_query_arg('tab', 'payment', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'payment' ? 'active' : ''; ?>">درگاه پرداخت</a>
        <a href="<?php echo add_query_arg('tab', 'sms', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'sms' ? 'active' : ''; ?>">سامانه پیامک</a>
    </div>

    <div class="pzl-dashboard-tab-content" style="margin-top: 20px;">
        <?php
        if ( $active_tab == 'sms' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-sms.php';
        } else {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-payment.php';
        }
        ?>
    </div>
</div>