<?php
/**
 * The main template wrapper for the PuzzlingCRM dashboard - FULLY UPGRADED
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();
if ( ! ( $current_user instanceof WP_User ) || $current_user->ID === 0 ) {
    echo '<p>لطفاً برای مشاهده داشبورد، ابتدا وارد حساب کاربری خود شوید.</p>';
    return;
}

// Check if a specific view like 'project' is requested.
$current_view = isset($_GET['view']) ? sanitize_key($_GET['view']) : '';
$project_id_to_view = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($current_view === 'project' && $project_id_to_view > 0) {
    // Load the single project view template
    $template_to_load = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/single-project.php';
    $project_title_to_view = get_the_title($project_id_to_view);
    $dashboard_title = 'نمایش پروژه: ' . ($project_title_to_view ? esc_html($project_title_to_view) : 'ناشناس');
} else {
    // Load the role-based dashboard view
    $user_roles = (array) $current_user->roles;
    $dashboard_title = 'داشبورد';
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
    $template_to_load = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $partial_to_load;
}

?>
<div class="puzzling-dashboard-wrapper">
    <header class="puzzling-dashboard-header">
        <h1><?php echo esc_html( $dashboard_title ); ?></h1>
        <div class="user-info">
            خوش آمدید، <strong><?php echo esc_html( $current_user->display_name ); ?></strong>
            <br>
            <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">خروج از حساب</a>
        </div>
    </header>

    <main class="puzzling-dashboard-content">
        <?php
        if ( isset($_GET['puzzling_notice']) ) {
            $notice_type = 'info';
            $message = '';
            $notice_key = sanitize_key($_GET['puzzling_notice']);
            
            $messages = [
                'contract_created_success' => ['type' => 'success', 'text' => 'قرارداد جدید با موفقیت ایجاد شد.'],
                'settings_saved' => ['type' => 'success', 'text' => 'تنظیمات با موفقیت ذخیره شد.'],
                'payment_success' => ['type' => 'success', 'text' => 'پرداخت شما با موفقیت انجام شد. سپاسگزاریم!'],
                'payment_cancelled' => ['type' => 'warning', 'text' => 'تراکنش توسط شما لغو شد.'],
                'payment_failed' => ['type' => 'error', 'text' => 'خطا در اتصال به درگاه پرداخت.'],
                'payment_failed_verification' => ['type' => 'error', 'text' => 'خطا در تایید پرداخت. لطفاً با پشتیبانی تماس بگیرید.'],
                'contract_error_data_invalid' => ['type' => 'error', 'text' => 'اطلاعات ارسال شده برای ایجاد قرارداد ناقص یا نامعتبر است.'],
                'contract_error_project_not_found' => ['type' => 'error', 'text' => 'پروژه انتخاب شده یافت نشد.'],
                'contract_error_no_installments' => ['type' => 'error', 'text' => 'حداقل یک قسط باید برای قرارداد تعریف شود.'],
            ];

            if (array_key_exists($notice_key, $messages)) {
                $notice_type = $messages[$notice_key]['type'];
                $message = $messages[$notice_key]['text'];
            }
            
            if ($message) {
                echo '<div class="pzl-alert pzl-alert-' . esc_attr($notice_type) . '">' . esc_html($message) . '</div>';
            }
        }
        
        if ( file_exists( $template_to_load ) ) {
            // Make variables available to the included template file
            global $puzzling_project_id;
            $puzzling_project_id = $project_id_to_view;
            
            include $template_to_load;
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
.pzl-alert-warning { color: #856404; background-color: #fff3cd; border-color: #ffeeba; }
.pzl-alert-info { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; }
</style>