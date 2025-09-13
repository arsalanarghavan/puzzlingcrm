<?php
/**
 * The main template wrapper for the PuzzlingCRM dashboard.
 *
 * This template checks the current user's role and loads the appropriate
 * partial template for their dashboard view.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Get current user information
$current_user = wp_get_current_user();
if ( ! ( $current_user instanceof WP_User ) || $current_user->ID === 0 ) {
    echo '<p>لطفاً برای مشاهده داشبورد، ابتدا وارد حساب کاربری خود شوید.</p>';
    return;
}

$user_roles = (array) $current_user->roles;
$primary_role = !empty($user_roles) ? $user_roles[0] : '';
$dashboard_title = 'داشبورد';

// Determine which partial to load based on the user role
$partial_to_load = '';

if ( in_array( 'administrator', $user_roles ) || in_array( 'system_manager', $user_roles ) ) {
    $partial_to_load = 'dashboard-system-manager.php';
    $dashboard_title = 'داشبورد مدیر سیستم';
} elseif ( in_array( 'finance_manager', $user_roles ) ) {
    $partial_to_load = 'dashboard-finance.php';
    $dashboard_title = 'داشبورد مدیر مالی';
} elseif ( in_array( 'team_member', $user_roles ) ) {
    $partial_to_load = 'dashboard-team-member.php';
    $dashboard_title = 'داشبورد تیم';
} elseif ( in_array( 'customer', $user_roles ) ) {
    $partial_to_load = 'dashboard-client.php';
    $dashboard_title = 'داشبورد مشتری';
} else {
    echo '<p>نقش کاربری شما تعریف نشده است.</p>';
    return;
}

// Get the full path to the partial template
$template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $partial_to_load;

?>
<div class="puzzling-dashboard-wrapper">
    <header class="puzzling-dashboard-header">
        <h1><?php echo esc_html( $dashboard_title ); ?></h1>
        <div class="user-info">
            خوش آمدید، <strong><?php echo esc_html( $current_user->display_name ); ?></strong>
            <br>
            <a href="<?php echo wp_logout_url( home_url() ); ?>">خروج از حساب</a>
        </div>
    </header>

    <main class="puzzling-dashboard-content">
        <?php
        // ** NEW **: Display payment status messages
        if ( isset($_GET['payment_status']) ) {
            $status = sanitize_key($_GET['payment_status']);
            if ( $status === 'success' ) {
                echo '<div class="pzl-alert pzl-alert-success">پرداخت شما با موفقیت انجام شد. سپاسگزاریم!</div>';
            } elseif ( $status === 'failed' ) {
                echo '<div class="pzl-alert pzl-alert-error">تراکنش ناموفق بود یا توسط شما لغو شد.</div>';
            } elseif ( $status === 'failed_verification' ) {
                echo '<div class="pzl-alert pzl-alert-error">خطا در تایید پرداخت. لطفاً با پشتیبانی تماس بگیرید.</div>';
            }
        }
        
        if ( file_exists( $template_path ) ) {
            // Include the specific dashboard partial
            include $template_path;
        } else {
            echo '<p>خطا: فایل قالب داشبورد یافت نشد.</p>';
        }
        ?>
    </main>
</div>
<style>
.pzl-alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
.pzl-alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
.pzl-alert-error { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
</style>