<?php
/**
 * Settings AJAX Handler
 * Handles all settings-related AJAX requests
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

class PuzzlingCRM_Settings_Ajax_Handler {

    public function __construct() {
        // Save general settings
        add_action('wp_ajax_puzzling_save_settings', [$this, 'save_settings']);
        add_action('wp_ajax_save_auth_settings', [$this, 'save_auth_settings']);
        
        // Save user preferences (language, theme, sidebar, etc.)
        add_action('wp_ajax_puzzling_save_user_preference', [$this, 'save_user_preference']);
        add_action('wp_ajax_nopriv_puzzling_save_user_preference', [$this, 'save_user_preference']);
        
        // Manage canned responses
        add_action('wp_ajax_puzzling_manage_canned_response', [$this, 'manage_canned_response']);
        add_action('wp_ajax_puzzling_delete_canned_response', [$this, 'delete_canned_response']);
        
        // Manage positions
        add_action('wp_ajax_puzzling_manage_position', [$this, 'manage_position']);
        add_action('wp_ajax_puzzling_delete_position', [$this, 'delete_position']);
        
        // Manage task categories
        add_action('wp_ajax_puzzling_manage_task_category', [$this, 'manage_task_category']);
        add_action('wp_ajax_puzzling_delete_task_category', [$this, 'delete_task_category']);
    }
    
    /**
     * Save user preference (language, theme, sidebar, etc.)
     */
    public function save_user_preference() {
        // No nonce check needed for user preferences (public action)
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $user_id = get_current_user_id();
        $preference = isset($_POST['preference']) ? sanitize_text_field($_POST['preference']) : '';
        $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
        
        if (empty($preference) || empty($value)) {
            wp_send_json_error(['message' => 'Invalid preference or value']);
        }
        
        // Allowed preferences
        $allowed_preferences = ['pzl_language', 'pzl_theme_mode', 'pzl_sidebar_state'];
        
        if (!in_array($preference, $allowed_preferences)) {
            wp_send_json_error(['message' => 'Invalid preference name']);
        }
        
        // Validate values
        if ($preference === 'pzl_language' && !in_array($value, ['fa', 'en'])) {
            wp_send_json_error(['message' => 'Invalid language value']);
        }
        
        if ($preference === 'pzl_theme_mode' && !in_array($value, ['light', 'dark'])) {
            wp_send_json_error(['message' => 'Invalid theme mode value']);
        }
        
        if ($preference === 'pzl_sidebar_state' && !in_array($value, ['open', 'closed'])) {
            wp_send_json_error(['message' => 'Invalid sidebar state value']);
        }
        
        // Save to user meta
        update_user_meta($user_id, $preference, $value);
        
        wp_send_json_success([
            'message' => 'Preference saved successfully',
            'preference' => $preference,
            'value' => $value
        ]);
    }

    /**
     * Save General Settings
     */
    public function save_settings() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم برای این عملیات را ندارید.']);
        }

        $settings_tab = isset($_POST['settings_tab']) ? sanitize_key($_POST['settings_tab']) : '';
        
        switch ($settings_tab) {
            case 'sms':
                $this->save_sms_settings();
                break;
                
            case 'payment':
                $this->save_payment_settings();
                break;
                
            case 'notifications':
                $this->save_notification_settings();
                break;
                
            case 'automations':
                $this->save_automation_settings();
                break;
                
            case 'workflow':
                $this->save_workflow_settings();
                break;
                
            case 'style':
                $this->save_style_settings();
                break;
                
            default:
                wp_send_json_error(['message' => 'نوع تنظیمات مشخص نیست.']);
        }
    }

    /**
     * Save SMS Settings
     */
    private function save_sms_settings() {
        $sms_service = isset($_POST['sms_service']) ? sanitize_text_field($_POST['sms_service']) : '';
        $sms_username = isset($_POST['sms_username']) ? sanitize_text_field($_POST['sms_username']) : '';
        $sms_password = isset($_POST['sms_password']) ? sanitize_text_field($_POST['sms_password']) : '';
        $sms_sender = isset($_POST['sms_sender']) ? sanitize_text_field($_POST['sms_sender']) : '';
        
        update_option('puzzlingcrm_sms_service', $sms_service);
        update_option('puzzlingcrm_sms_username', $sms_username);
        update_option('puzzlingcrm_sms_password', $sms_password);
        update_option('puzzlingcrm_sms_sender', $sms_sender);
        
        wp_send_json_success([
            'message' => 'تنظیمات پیامک با موفقیت ذخیره شد.',
            'reload' => true
        ]);
    }

    /**
     * Save Payment Settings
     */
    private function save_payment_settings() {
        $zarinpal_merchant = isset($_POST['zarinpal_merchant']) ? sanitize_text_field($_POST['zarinpal_merchant']) : '';
        $zarinpal_sandbox = isset($_POST['zarinpal_sandbox']) ? '1' : '0';
        
        update_option('puzzlingcrm_zarinpal_merchant', $zarinpal_merchant);
        update_option('puzzlingcrm_zarinpal_sandbox', $zarinpal_sandbox);
        
        wp_send_json_success([
            'message' => 'تنظیمات درگاه پرداخت ذخیره شد.',
            'reload' => true
        ]);
    }

    /**
     * Save Notification Settings
     */
    private function save_notification_settings() {
        $telegram_bot_token = isset($_POST['telegram_bot_token']) ? sanitize_text_field($_POST['telegram_bot_token']) : '';
        $telegram_chat_id = isset($_POST['telegram_chat_id']) ? sanitize_text_field($_POST['telegram_chat_id']) : '';
        
        update_option('puzzlingcrm_telegram_bot_token', $telegram_bot_token);
        update_option('puzzlingcrm_telegram_chat_id', $telegram_chat_id);
        
        wp_send_json_success([
            'message' => 'تنظیمات اعلانات ذخیره شد.',
            'reload' => true
        ]);
    }

    /**
     * Save Automation Settings
     */
    private function save_automation_settings() {
        // Implementation for automation settings
        wp_send_json_success([
            'message' => 'تنظیمات اتوماسیون ذخیره شد.',
            'reload' => true
        ]);
    }

    /**
     * Save Workflow Settings
     */
    private function save_workflow_settings() {
        // Implementation for workflow settings
        wp_send_json_success([
            'message' => 'تنظیمات گردش کار ذخیره شد.',
            'reload' => true
        ]);
    }

    /**
     * Save Style Settings
     */
    private function save_style_settings() {
        $primary_color = isset($_POST['primary_color']) ? sanitize_hex_color($_POST['primary_color']) : '#845adf';
        $font_family = isset($_POST['font_family']) ? sanitize_text_field($_POST['font_family']) : 'IRANSans';
        $font_size = isset($_POST['font_size']) ? intval($_POST['font_size']) : 14;
        
        update_option('puzzlingcrm_primary_color', $primary_color);
        update_option('puzzlingcrm_font_family', $font_family);
        update_option('puzzlingcrm_font_size', $font_size);
        
        wp_send_json_success([
            'message' => 'تنظیمات استایل ذخیره شد.',
            'reload' => true
        ]);
    }

    /**
     * Manage Canned Response
     */
    public function manage_canned_response() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم برای این عملیات را ندارید.']);
        }

        $response_id = isset($_POST['response_id']) ? intval($_POST['response_id']) : 0;
        $title = isset($_POST['response_title']) ? sanitize_text_field($_POST['response_title']) : '';
        $content = isset($_POST['response_content']) ? wp_kses_post($_POST['response_content']) : '';

        if (empty($title) || empty($content)) {
            wp_send_json_error(['message' => 'لطفاً تمام فیلدهای ضروری را پر کنید.']);
        }

        $post_data = [
            'post_type' => 'canned_response',
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish'
        ];

        if ($response_id > 0) {
            $post_data['ID'] = $response_id;
            $result = wp_update_post($post_data);
            $message = 'پاسخ آماده با موفقیت بروزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data);
            $message = 'پاسخ آماده جدید با موفقیت ایجاد شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در ذخیره پاسخ آماده.']);
        }

        wp_send_json_success([
            'message' => $message,
            'reload' => true
        ]);
    }

    /**
     * Delete Canned Response
     */
    public function delete_canned_response() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $response_id = isset($_POST['response_id']) ? intval($_POST['response_id']) : 0;
        
        if ($response_id && wp_delete_post($response_id, true)) {
            wp_send_json_success(['message' => 'پاسخ آماده حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف پاسخ آماده.']);
        }
    }

    /**
     * Manage Position
     */
    public function manage_position() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $position_id = isset($_POST['position_id']) ? intval($_POST['position_id']) : 0;
        $title = isset($_POST['position_title']) ? sanitize_text_field($_POST['position_title']) : '';
        $permissions = isset($_POST['position_permissions']) ? array_map('sanitize_text_field', $_POST['position_permissions']) : [];

        if (empty($title)) {
            wp_send_json_error(['message' => 'عنوان موقعیت شغلی الزامی است.']);
        }

        $post_data = [
            'post_type' => 'position',
            'post_title' => $title,
            'post_status' => 'publish'
        ];

        if ($position_id > 0) {
            $post_data['ID'] = $position_id;
            $result = wp_update_post($post_data);
            $message = 'موقعیت شغلی بروزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data);
            $message = 'موقعیت شغلی جدید ایجاد شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در ذخیره موقعیت شغلی.']);
        }

        // Save permissions
        update_post_meta($result, '_position_permissions', $permissions);

        wp_send_json_success([
            'message' => $message,
            'reload' => true
        ]);
    }

    /**
     * Delete Position
     */
    public function delete_position() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $position_id = isset($_POST['position_id']) ? intval($_POST['position_id']) : 0;
        
        if ($position_id && wp_delete_post($position_id, true)) {
            wp_send_json_success(['message' => 'موقعیت شغلی حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف.']);
        }
    }

    /**
     * Manage Task Category
     */
    public function manage_task_category() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $name = isset($_POST['category_name']) ? sanitize_text_field($_POST['category_name']) : '';
        $color = isset($_POST['category_color']) ? sanitize_hex_color($_POST['category_color']) : '#845adf';

        if (empty($name)) {
            wp_send_json_error(['message' => 'نام دسته‌بندی الزامی است.']);
        }

        if ($category_id > 0) {
            $result = wp_update_term($category_id, 'task_category', ['name' => $name]);
            $message = 'دسته‌بندی بروزرسانی شد.';
        } else {
            $result = wp_insert_term($name, 'task_category');
            $message = 'دسته‌بندی جدید ایجاد شد.';
            $category_id = $result['term_id'];
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در ذخیره دسته‌بندی.']);
        }

        // Save color
        update_term_meta($category_id, 'category_color', $color);

        wp_send_json_success([
            'message' => $message,
            'reload' => true
        ]);
    }

    /**
     * Delete Task Category
     */
    public function delete_task_category() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        
        if ($category_id && wp_delete_term($category_id, 'task_category')) {
            wp_send_json_success(['message' => 'دسته‌بندی حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف.']);
        }
    }

    /**
     * Save Authentication Settings
     */
    public function save_auth_settings() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم برای این عملیات را ندارید.']);
        }

        $current_settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        
        // Authentication settings
        $auth_settings = [
            'login_phone_pattern' => sanitize_text_field($_POST['login_phone_pattern'] ?? '^09[0-9]{9}$'),
            'login_phone_length' => intval($_POST['login_phone_length'] ?? 11),
            'login_sms_template' => sanitize_textarea_field($_POST['login_sms_template'] ?? 'کد ورود شما: %CODE%'),
            'otp_expiry_minutes' => intval($_POST['otp_expiry_minutes'] ?? 5),
            'otp_max_attempts' => intval($_POST['otp_max_attempts'] ?? 3),
            'otp_length' => intval($_POST['otp_length'] ?? 6),
            'melipayamak_login_pattern' => sanitize_text_field($_POST['melipayamak_login_pattern'] ?? ''),
            'parsgreen_login_template' => sanitize_textarea_field($_POST['parsgreen_login_template'] ?? ''),
            'enable_password_login' => isset($_POST['enable_password_login']) ? 1 : 0,
            'enable_sms_login' => isset($_POST['enable_sms_login']) ? 1 : 0,
            'login_redirect_url' => esc_url_raw($_POST['login_redirect_url'] ?? ''),
            'logout_redirect_url' => esc_url_raw($_POST['logout_redirect_url'] ?? ''),
            'force_logout_inactive' => isset($_POST['force_logout_inactive']) ? 1 : 0,
            'inactive_timeout_minutes' => intval($_POST['inactive_timeout_minutes'] ?? 30)
        ];

        // Validate regex pattern
        if (@preg_match('/' . $auth_settings['login_phone_pattern'] . '/', '') === false) {
            wp_send_json_error(['message' => 'پترن Regex نامعتبر است.']);
        }

        // Merge with existing settings
        $updated_settings = array_merge($current_settings, $auth_settings);
        
        if (PuzzlingCRM_Settings_Handler::update_settings($updated_settings)) {
            wp_send_json_success(['message' => 'تنظیمات احراز هویت با موفقیت ذخیره شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در ذخیره تنظیمات.']);
        }
    }
}

