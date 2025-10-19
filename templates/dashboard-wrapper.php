<?php
/**
 * The main template wrapper for the PuzzlingCRM dashboard - FULLY UPGRADED
 *
 * This template acts as the main shell for the entire frontend dashboard.
 * It handles loading the correct view based on user role and URL parameters,
 * displays system-wide notices, and includes the header with the real-time notification center.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();
if ( ! ( $current_user instanceof WP_User ) || $current_user->ID === 0 ) {
    echo '<div class="puzzling-dashboard-wrapper">';
    echo '<h3><i class="ri-lock-line"></i> دسترسی غیرمجاز</h3>';
    echo '<p>لطفاً برای مشاهده داشبورد، ابتدا وارد حساب کاربری خود شوید.</p>';
    wp_login_form();
    echo '</div>';
    return;
}

// Sanitize GET parameters to determine the correct view
$current_view = isset($_GET['view']) ? sanitize_key($_GET['view']) : '';
$item_id_to_view = isset($_GET['project_id']) ? intval($_GET['project_id']) : (isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0);
$dashboard_title = 'داشبورد'; // Default title
$template_to_load = '';

// Determine the main template based on the user's role
$user_roles = (array) $current_user->roles;
$partial_to_load = '';

if ( in_array( 'administrator', $user_roles ) || in_array( 'system_manager', $user_roles ) ) {
    $partial_to_load = 'dashboard-system-manager.php';
    $dashboard_title = 'داشبورد مدیر سیستم';
} elseif ( in_array( 'finance_manager', $user_roles ) ) {
    $partial_to_load = 'dashboard-finance.php';
    $dashboard_title = 'داشبورد مدیر مالی';
} elseif ( in_array( 'team_member', $user_roles ) ) {
    $partial_to_load = 'dashboard-team-member.php';
    $dashboard_title = 'داشبورد عضو تیم';
} elseif ( in_array( 'customer', $user_roles ) ) {
    $partial_to_load = 'dashboard-client.php';
    $dashboard_title = 'داشبورد مشتری';
} else {
    echo '<p>نقش کاربری شما برای دسترسی به داشبورد تعریف نشده است.</p>';
    return;
}

$template_to_load = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $partial_to_load;

?>
<div class="puzzling-dashboard-wrapper">
    <header class="puzzling-dashboard-header">
        <h1><?php echo esc_html( $dashboard_title ); ?></h1>
        <div class="header-controls">
             <div class="pzl-notification-center">
                <div class="pzl-notification-bell" title="اعلانات">
                    <i class="ri-notification-3-line"></i>
                    <span class="pzl-notification-count"></span>
                </div>
                <div class="pzl-notification-panel">
                    <div class="panel-header">اعلانات</div>
                    <ul>
                        <li class="pzl-no-notifications">در حال بارگذاری...</li>
                    </ul>
                </div>
            </div>
            <div class="user-info">
                خوش آمدید، <strong><?php echo esc_html( $current_user->display_name ); ?></strong>
                <a href="<?php echo esc_url( add_query_arg('view', 'my_profile', get_permalink()) ); ?>" title="پروفایل من">پروفایل من</a>
                <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" title="خروج از حساب کاربری">خروج</a>
            </div>
        </div>
    </header>

    <main class="puzzling-dashboard-content">
        <?php
        // Display system-wide notices
        if ( isset($_GET['puzzling_notice']) ) {
            $notice_key = sanitize_key($_GET['puzzling_notice']);
            $messages = [
                'security_failed' => ['type' => 'error', 'text' => 'خطای امنیتی! درخواست شما معتبر نبود.'],
                'permission_denied' => ['type' => 'error', 'text' => 'شما دسترسی لازم برای انجام این کار را ندارید.'],
                'project_created_success' => ['type' => 'success', 'text' => 'پروژه جدید با موفقیت ایجاد شد.'],
                'project_updated_success' => ['type' => 'success', 'text' => 'پروژه با موفقیت به‌روزرسانی شد.'],
                'project_deleted_success' => ['type' => 'success', 'text' => 'پروژه با موفقیت حذف شد.'],
                'project_error_failed' => ['type' => 'error', 'text' => 'خطا در پردازش پروژه.'],
                'contract_created_success' => ['type' => 'success', 'text' => 'قرارداد جدید با موفقیت ایجاد شد.'],
                'settings_saved' => ['type' => 'success', 'text' => 'تنظیمات با موفقیت ذخیره شد.'],
                'payment_success' => ['type' => 'success', 'text' => 'پرداخت شما با موفقیت انجام شد. سپاسگزاریم!'],
                'payment_cancelled' => ['type' => 'warning', 'text' => 'تراکنش توسط شما لغو شد.'],
                'payment_failed' => ['type' => 'error', 'text' => 'خطا در اتصال به درگاه پرداخت.'],
                'payment_failed_verification' => ['type' => 'error', 'text' => 'خطا در تایید پرداخت. لطفاً با پشتیبانی تماس بگیرید.'],
                 'profile_updated_success' => ['type' => 'success', 'text' => 'پروفایل شما با موفقیت به‌روزرسانی شد.'],
            ];

            if (array_key_exists($notice_key, $messages)) {
                $notice_type = esc_attr($messages[$notice_key]['type']);
                $message = esc_html($messages[$notice_key]['text']);
                echo "<div class='pzl-alert pzl-alert-{$notice_type}'>{$message}</div>";
            }
        }
        
        // Load the main content template
        if ( file_exists( $template_to_load ) ) {
            include $template_to_load;
        } else {
            echo '<div class="pzl-alert pzl-alert-error">خطای سیستمی: فایل قالب داشبورد یافت نشد.</div>';
        }
        ?>
    </main>
</div>