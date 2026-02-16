<?php
/**
 * Log & Debug Settings
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
$enable_logging_system   = isset( $settings['enable_logging_system'] ) ? $settings['enable_logging_system'] : '1';
$log_system_errors       = isset( $settings['log_system_errors'] ) ? $settings['log_system_errors'] : '1';
$log_system_debug        = isset( $settings['log_system_debug'] ) ? $settings['log_system_debug'] : '0';
$log_console_messages    = isset( $settings['log_console_messages'] ) ? $settings['log_console_messages'] : '1';
$log_button_errors       = isset( $settings['log_button_errors'] ) ? $settings['log_button_errors'] : '1';
$enable_user_logging     = isset( $settings['enable_user_logging'] ) ? $settings['enable_user_logging'] : '1';
$log_button_clicks       = isset( $settings['log_button_clicks'] ) ? $settings['log_button_clicks'] : '1';
$log_form_submissions    = isset( $settings['log_form_submissions'] ) ? $settings['log_form_submissions'] : '1';
$log_ajax_calls          = isset( $settings['log_ajax_calls'] ) ? $settings['log_ajax_calls'] : '1';
$log_page_views          = isset( $settings['log_page_views'] ) ? $settings['log_page_views'] : '0';
$log_max_file_size       = isset( $settings['log_max_file_size'] ) ? (int) $settings['log_max_file_size'] : 5000;
$log_retention_days      = isset( $settings['log_retention_days'] ) ? (int) $settings['log_retention_days'] : 90;
$enable_auto_log_cleanup = isset( $settings['enable_auto_log_cleanup'] ) ? $settings['enable_auto_log_cleanup'] : '0';
?>
<div class="pzl-form-container">
    <h4><i class="ri-bug-line"></i> لاگ و دیباگ</h4>
    <form method="post" class="pzl-form" style="margin-top: 20px;">
        <?php wp_nonce_field( 'puzzling_save_settings_nonce', 'security' ); ?>
        <input type="hidden" name="puzzling_action" value="save_puzzling_settings" />

        <div class="pzl-settings-section">
            <h5>لاگ سیستم</h5>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[enable_logging_system]" value="1" <?php checked( $enable_logging_system, '1' ); ?>>
                    <?php esc_html_e( 'فعال‌سازی لاگ سیستم', 'puzzlingcrm' ); ?>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[log_system_errors]" value="1" <?php checked( $log_system_errors, '1' ); ?>>
                    <?php esc_html_e( 'ثبت خطاهای سیستمی', 'puzzlingcrm' ); ?>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[log_system_debug]" value="1" <?php checked( $log_system_debug, '1' ); ?>>
                    <?php esc_html_e( 'ثبت لاگ دیباگ (فقط وقتی WP_DEBUG فعال است)', 'puzzlingcrm' ); ?>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[log_console_messages]" value="1" <?php checked( $log_console_messages, '1' ); ?>>
                    <?php esc_html_e( 'ثبت پیام‌های کنسول مرورگر', 'puzzlingcrm' ); ?>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[log_button_errors]" value="1" <?php checked( $log_button_errors, '1' ); ?>>
                    <?php esc_html_e( 'ثبت خطاهای دکمه‌ها', 'puzzlingcrm' ); ?>
                </label>
            </div>
        </div>

        <div class="pzl-settings-section">
            <h5>لاگ کاربر</h5>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[enable_user_logging]" value="1" <?php checked( $enable_user_logging, '1' ); ?>>
                    <?php esc_html_e( 'فعال‌سازی لاگ اقدامات کاربر', 'puzzlingcrm' ); ?>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[log_button_clicks]" value="1" <?php checked( $log_button_clicks, '1' ); ?>>
                    <?php esc_html_e( 'ثبت کلیک دکمه', 'puzzlingcrm' ); ?>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[log_form_submissions]" value="1" <?php checked( $log_form_submissions, '1' ); ?>>
                    <?php esc_html_e( 'ثبت ارسال فرم', 'puzzlingcrm' ); ?>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[log_ajax_calls]" value="1" <?php checked( $log_ajax_calls, '1' ); ?>>
                    <?php esc_html_e( 'ثبت فراخوانی AJAX', 'puzzlingcrm' ); ?>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[log_page_views]" value="1" <?php checked( $log_page_views, '1' ); ?>>
                    <?php esc_html_e( 'ثبت مشاهده صفحه', 'puzzlingcrm' ); ?>
                </label>
            </div>
        </div>

        <div class="pzl-settings-section">
            <h5>نگهداری و پاکسازی</h5>
            <div class="form-group">
                <label for="log_max_file_size"><?php esc_html_e( 'حداکثر طول پیام (کاراکتر)', 'puzzlingcrm' ); ?></label>
                <input type="number" id="log_max_file_size" name="puzzling_settings[log_max_file_size]" value="<?php echo esc_attr( $log_max_file_size ); ?>" min="0" class="ltr-input" style="width:120px;">
            </div>
            <div class="form-group">
                <label for="log_retention_days"><?php esc_html_e( 'مدت نگهداری لاگ (روز)', 'puzzlingcrm' ); ?></label>
                <input type="number" id="log_retention_days" name="puzzling_settings[log_retention_days]" value="<?php echo esc_attr( $log_retention_days ); ?>" min="7" max="365" class="ltr-input" style="width:120px;">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[enable_auto_log_cleanup]" value="1" <?php checked( $enable_auto_log_cleanup, '1' ); ?>>
                    <?php esc_html_e( 'پاکسازی خودکار لاگ‌های قدیمی', 'puzzlingcrm' ); ?>
                </label>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="btn btn-primary"><?php esc_html_e( 'ذخیره تنظیمات', 'puzzlingcrm' ); ?></button>
        </p>
    </form>
</div>
