<?php
/**
 * PuzzlingCRM Login with SMS AJAX Handler
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load settings handler
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-settings-handler.php';
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-logger.php';

class PuzzlingCRM_Login_Ajax_Handler {

    public function __construct() {
        // AJAX actions for both logged-in and non-logged-in users
        add_action('wp_ajax_puzzling_send_login_otp', [$this, 'send_login_otp']);
        add_action('wp_ajax_nopriv_puzzling_send_login_otp', [$this, 'send_login_otp']);
        
        add_action('wp_ajax_puzzling_verify_login_otp', [$this, 'verify_login_otp']);
        add_action('wp_ajax_nopriv_puzzling_verify_login_otp', [$this, 'verify_login_otp']);
        
        add_action('wp_ajax_puzzling_login_with_password', [$this, 'login_with_password']);
        add_action('wp_ajax_nopriv_puzzling_login_with_password', [$this, 'login_with_password']);
    }

    /**
     * AJAX handler for sending OTP code via SMS
     */
    public function send_login_otp() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!isset($_POST['phone_number'])) {
            wp_send_json_error(['message' => 'شماره تلفن الزامی است.']);
        }

        try {
            $phone_number = sanitize_text_field($_POST['phone_number']);
            
            // Validate phone number format (Iranian mobile numbers)
            if (!preg_match('/^(09|\u06f0\u06f9)[0-9\u06f0-\u06f9]{9}$/', $phone_number)) {
                wp_send_json_error(['message' => 'فرمت شماره موبایل صحیح نیست. (مثال: 09123456789)']);
            }

            // Convert Persian/Arabic numerals to English
            if (function_exists('tr_num')) {
                $phone_number = tr_num($phone_number, 'en');
            }

            // Find user by phone number
            $user = $this->find_user_by_phone($phone_number);
            
            if (!$user) {
                wp_send_json_error(['message' => 'کاربری با این شماره موبایل یافت نشد.']);
            }

            // Generate 6-digit OTP code
            $otp_code = $this->generate_otp_code();
            
            // Store OTP in transient (expires in 5 minutes)
            $transient_key = 'puzzling_otp_' . md5($phone_number);
            set_transient($transient_key, [
                'code' => $otp_code,
                'user_id' => $user->ID,
                'attempts' => 0
            ], 5 * MINUTE_IN_SECONDS);

            // Send SMS
            $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
            $sms_handler = puzzling_get_sms_handler($settings);

            if (!$sms_handler) {
                wp_send_json_error(['message' => 'سرویس پیامک به درستی پیکربندی نشده است.']);
            }

            // Get OTP pattern/template from settings
            $sms_service = $settings['sms_service'] ?? '';
            $success = false;

            if ($sms_service === 'melipayamak') {
                // Use pattern for Melipayamak
                $pattern_code = $settings['melipayamak_login_pattern'] ?? '';
                if (empty($pattern_code)) {
                    // Fallback to simple message
                    $message = "کد ورود شما: $otp_code\nاعتبار: 5 دقیقه";
                    $success = $sms_handler->send_sms($phone_number, $message);
                } else {
                    // Send with pattern
                    $success = $sms_handler->send_sms($phone_number, $pattern_code, ['amount' => $otp_code]);
                }
            } else {
                // For other services, use simple message
                $message_template = $settings['login_sms_template'] ?? 'کد ورود شما: %CODE%';
                $message = str_replace('%CODE%', $otp_code, $message_template);
                $success = $sms_handler->send_sms($phone_number, $message);
            }

            if ($success) {
                PuzzlingCRM_Logger::add('ارسال کد ورود', [
                    'content' => "کد ورود برای کاربر {$user->user_login} ارسال شد.",
                    'type' => 'log'
                ]);
                wp_send_json_success([
                    'message' => 'کد تایید به شماره موبایل شما ارسال شد.',
                    'expires_in' => 300 // 5 minutes in seconds
                ]);
            } else {
                wp_send_json_error(['message' => 'خطا در ارسال پیامک. لطفاً بعداً تلاش کنید.']);
            }
        } catch (Exception $e) {
            error_log('PuzzlingCRM Login OTP Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'یک خطای سیستمی رخ داد.']);
        }
    }

    /**
     * AJAX handler for verifying OTP code
     */
    public function verify_login_otp() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!isset($_POST['phone_number']) || !isset($_POST['otp_code'])) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        try {
            $phone_number = sanitize_text_field($_POST['phone_number']);
            $otp_code = sanitize_text_field($_POST['otp_code']);

            // Convert Persian/Arabic numerals to English
            if (function_exists('tr_num')) {
                $phone_number = tr_num($phone_number, 'en');
                $otp_code = tr_num($otp_code, 'en');
            }

            // Get OTP from transient
            $transient_key = 'puzzling_otp_' . md5($phone_number);
            $otp_data = get_transient($transient_key);

            if (!$otp_data) {
                wp_send_json_error(['message' => 'کد تایید منقضی شده است. لطفاً کد جدید درخواست کنید.']);
            }

            // Check attempts
            if ($otp_data['attempts'] >= 3) {
                delete_transient($transient_key);
                wp_send_json_error(['message' => 'تعداد تلاش‌های مجاز تمام شده است. لطفاً کد جدید درخواست کنید.']);
            }

            // Verify OTP code
            if ($otp_data['code'] !== $otp_code) {
                $otp_data['attempts']++;
                set_transient($transient_key, $otp_data, 5 * MINUTE_IN_SECONDS);
                
                $remaining = 3 - $otp_data['attempts'];
                wp_send_json_error([
                    'message' => "کد تایید اشتباه است. ($remaining تلاش باقی‌مانده)"
                ]);
            }

            // OTP is correct, log in the user
            $user = get_user_by('ID', $otp_data['user_id']);
            
            if (!$user) {
                wp_send_json_error(['message' => 'کاربر یافت نشد.']);
            }

            // Clear the OTP transient
            delete_transient($transient_key);

            // Log in the user
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true);
            
            // Log the login
            PuzzlingCRM_Logger::add('ورود با پیامک', [
                'content' => "کاربر {$user->user_login} با پیامک وارد شد.",
                'type' => 'log',
                'user_id' => $user->ID
            ]);

            // Get redirect URL based on user role
            $redirect_url = $this->get_redirect_url_for_user($user);

            wp_send_json_success([
                'message' => 'ورود موفقیت‌آمیز بود.',
                'redirect_url' => $redirect_url
            ]);

        } catch (Exception $e) {
            error_log('PuzzlingCRM Verify OTP Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'یک خطای سیستمی رخ داد.']);
        }
    }

    /**
     * AJAX handler for traditional password login
     */
    public function login_with_password() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!isset($_POST['username']) || !isset($_POST['password'])) {
            wp_send_json_error(['message' => 'نام کاربری و رمز عبور الزامی است.']);
        }

        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;

        // Authenticate user
        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            wp_send_json_error(['message' => 'نام کاربری یا رمز عبور اشتباه است.']);
        }

        // Log in the user
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);

        // Log the login
        PuzzlingCRM_Logger::add('ورود با رمز عبور', [
            'content' => "کاربر {$user->user_login} با رمز عبور وارد شد.",
            'type' => 'log',
            'user_id' => $user->ID
        ]);

        // Get redirect URL based on user role
        $redirect_url = $this->get_redirect_url_for_user($user);

        wp_send_json_success([
            'message' => 'ورود موفقیت‌آمیز بود.',
            'redirect_url' => $redirect_url
        ]);
    }

    /**
     * Find user by phone number (checks multiple meta keys)
     */
    private function find_user_by_phone($phone_number) {
        $phone_meta_keys = [
            'pzl_mobile_phone',
            'wpyarud_phone',
            'puzzling_phone_number',
            'user_phone_number',
            'billing_phone' // WooCommerce billing phone
        ];

        foreach ($phone_meta_keys as $meta_key) {
            $users = get_users([
                'meta_key' => $meta_key,
                'meta_value' => $phone_number,
                'number' => 1
            ]);

            if (!empty($users)) {
                return $users[0];
            }
        }

        return null;
    }

    /**
     * Generate a 6-digit OTP code
     */
    private function generate_otp_code() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get redirect URL based on user role
     */
    private function get_redirect_url_for_user($user) {
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        }
        
        if (in_array('system_manager', $user->roles)) {
            return home_url('/dashboard');
        }
        
        if (in_array('team_member', $user->roles)) {
            return home_url('/dashboard');
        }
        
        if (in_array('client', $user->roles) || in_array('customer', $user->roles)) {
            return home_url('/dashboard');
        }

        // Default
        return home_url('/dashboard');
    }
}

