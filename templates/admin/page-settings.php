<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// This page will now act as a tabbed interface for all settings.
$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'payment';
?>
<h1><span class="dashicons dashicons-admin-settings"></span> تنظیمات</h1>

<h2 class="nav-tab-wrapper">
    <a href="?page=pzl-settings&tab=payment" class="nav-tab <?php echo $active_tab == 'payment' ? 'nav-tab-active' : ''; ?>">درگاه پرداخت</a>
    <a href="?page=pzl-settings&tab=sms" class="nav-tab <?php echo $active_tab == 'sms' ? 'nav-tab-active' : ''; ?>">سامانه پیامک</a>
</h2>

<?php
if ( $active_tab == 'sms' ) {
    $sms_settings_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-sms.php';
    if (file_exists($sms_settings_path)) include $sms_settings_path;
} else {
    $payment_settings_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/settings-payment.php';
    if (file_exists($payment_settings_path)) include $payment_settings_path;
}
?>