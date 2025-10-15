<?php
/**
 * PuzzlingCRM SMS AJAX Handler
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load settings handler and logger
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-settings-handler.php';
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-logger.php';

class PuzzlingCRM_SMS_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_send_custom_sms', [$this, 'send_custom_sms']);
        add_action('wp_ajax_puzzling_save_settings', [$this, 'save_settings']);
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

    /**
     * AJAX handler for saving settings.
     */
    public function save_settings() {
        // Log the start of settings save operation
        error_log('PuzzlingCRM: Starting SMS settings save operation');
        
        // Test logging
        if (class_exists('PuzzlingCRM_Logger')) {
            PuzzlingCRM_Logger::add('تست لاگ پیامک', [
                'content' => 'تست لاگ‌گذاری برای تنظیمات پیامک',
                'type' => 'log',
                'details' => [
                    'user_id' => get_current_user_id(),
                    'timestamp' => current_time('mysql')
                ]
            ]);
        }
        
        try {
            // Check nonce
            if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security')) {
                error_log('PuzzlingCRM: Invalid nonce in SMS settings save');
                wp_send_json_error(['message' => 'درخواست نامعتبر. لطفاً صفحه را رفرش کنید.']);
            }
            
            // Check user permissions
            if (!current_user_can('manage_options')) {
                error_log('PuzzlingCRM: User does not have permission to save SMS settings. User ID: ' . get_current_user_id());
                wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
            }

            // Validate POST data
            if (!isset($_POST['puzzling_settings']) || !is_array($_POST['puzzling_settings'])) {
                error_log('PuzzlingCRM: Invalid POST data for SMS settings. Data: ' . print_r($_POST, true));
                wp_send_json_error(['message' => 'داده‌های تنظیمات نامعتبر است.']);
            }

            $settings = $_POST['puzzling_settings'];
            error_log('PuzzlingCRM: Received SMS settings data: ' . print_r($settings, true));
            
            // Get existing settings
            $existing_settings = PuzzlingCRM_Settings_Handler::get_all_settings();
            error_log('PuzzlingCRM: Existing settings before merge: ' . print_r($existing_settings, true));
            
            // Sanitize new settings
            $sanitized_settings = [];
            foreach ($settings as $key => $value) {
                if (is_array($value)) {
                    $sanitized_settings[$key] = array_map('sanitize_text_field', $value);
                } else {
                    // Use sanitize_textarea_field for textarea fields
                    if (strpos($key, 'template') !== false || strpos($key, 'msg') !== false || strpos($key, 'pattern') !== false) {
                        $sanitized_settings[$key] = sanitize_textarea_field($value);
                    } else {
                        $sanitized_settings[$key] = sanitize_text_field($value);
                    }
                }
                error_log("PuzzlingCRM: Sanitized setting '{$key}': '{$sanitized_settings[$key]}'");
            }

            // Merge with existing settings
            $merged_settings = array_merge($existing_settings, $sanitized_settings);
            error_log('PuzzlingCRM: Merged settings: ' . print_r($merged_settings, true));

            // Save settings using the settings handler
            $result = PuzzlingCRM_Settings_Handler::update_settings($merged_settings);
            error_log('PuzzlingCRM: Settings save result: ' . ($result ? 'SUCCESS' : 'FAILED'));

            if ($result) {
                // Log successful save
                error_log('PuzzlingCRM: SMS settings saved successfully');
                
                // Add to system logs
                if (class_exists('PuzzlingCRM_Logger')) {
                    PuzzlingCRM_Logger::add('تنظیمات پیامک', [
                        'content' => 'تنظیمات پیامک با موفقیت ذخیره شد.',
                        'type' => 'log',
                        'details' => [
                            'sms_service' => $merged_settings['sms_service'] ?? 'not_set',
                            'lead_auto_sms_enabled' => $merged_settings['lead_auto_sms_enabled'] ?? '0',
                            'user_id' => get_current_user_id()
                        ]
                    ]);
                }
                
                wp_send_json_success(['message' => 'تنظیمات با موفقیت ذخیره شد.']);
            } else {
                error_log('PuzzlingCRM: Failed to save SMS settings - update_option returned false');
                wp_send_json_error(['message' => 'خطا در ذخیره تنظیمات. لطفاً دوباره تلاش کنید.']);
            }
        } catch (Exception $e) {
            error_log('PuzzlingCRM Settings Error: ' . $e->getMessage());
            error_log('PuzzlingCRM Settings Error Stack Trace: ' . $e->getTraceAsString());
            
            // Add error to system logs
            if (class_exists('PuzzlingCRM_Logger')) {
                PuzzlingCRM_Logger::add('خطای تنظیمات پیامک', [
                    'content' => 'خطا در ذخیره تنظیمات پیامک: ' . $e->getMessage(),
                    'type' => 'error',
                    'details' => [
                        'user_id' => get_current_user_id(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine()
                    ]
                ]);
            }
            
            wp_send_json_error(['message' => 'یک خطای سیستمی در هنگام ذخیره تنظیمات رخ داد.']);
        }
    }
}