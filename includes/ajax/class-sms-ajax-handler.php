<?php
/**
 * PuzzlingCRM SMS AJAX Handler
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_SMS_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_send_custom_sms', [$this, 'send_custom_sms']);
    }

    /**
     * AJAX handler for sending a custom SMS from the user management page.
     */
    public function send_custom_sms() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options') || !isset($_POST['user_id']) || !isset($_POST['message'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        try {
            $user_id = intval($_POST['user_id']);
            $message = sanitize_textarea_field($_POST['message']);
            $user_phone = get_user_meta($user_id, 'pzl_mobile_phone', true);

            if (empty($user_phone)) {
                wp_send_json_error(['message' => 'شماره موبایل برای این کاربر ثبت نشده است.']);
            }
            if (empty($message)) {
                wp_send_json_error(['message' => 'متن پیام نمی‌تواند خالی باشد.']);
            }
            
            $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
            $sms_handler = puzzling_get_sms_handler($settings);

            if (!$sms_handler) {
                wp_send_json_error(['message' => 'سرویس پیامک به درستی پیکربندی نشده است. لطفاً به بخش تنظیمات مراجعه کنید.']);
            }

            $success = $sms_handler->send_sms($user_phone, $message);

            if ($success) {
                PuzzlingCRM_Logger::add('ارسال پیامک دستی', ['content' => "یک پیامک دستی به کاربر با شناسه {$user_id} ارسال شد.", 'type' => 'log']);
                wp_send_json_success(['message' => 'پیامک با موفقیت ارسال شد.']);
            } else {
                wp_send_json_error(['message' => 'خطا در ارسال پیامک. لطفاً تنظیمات سرویس پیامک و لاگ‌های سرور را بررسی کنید.']);
            }
        } catch (Exception $e) {
            error_log('PuzzlingCRM SMS Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'یک خطای سیستمی در هنگام ارسال پیامک رخ داد. جزئیات خطا در لاگ سرور ثبت شد.']);
        }
    }
}