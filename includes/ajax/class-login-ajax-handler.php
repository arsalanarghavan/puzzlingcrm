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
        
        add_action('wp_ajax_puzzling_set_password', [$this, 'set_password']);
        add_action('wp_ajax_nopriv_puzzling_set_password', [$this, 'set_password']);
        
        add_action('wp_ajax_puzzling_auto_login', [$this, 'auto_login']);
        add_action('wp_ajax_nopriv_puzzling_auto_login', [$this, 'auto_login']);

        add_action('wp_ajax_puzzling_send_email_otp', [$this, 'send_email_otp']);
        add_action('wp_ajax_nopriv_puzzling_send_email_otp', [$this, 'send_email_otp']);
        add_action('wp_ajax_puzzling_verify_email_otp', [$this, 'verify_email_otp']);
        add_action('wp_ajax_nopriv_puzzling_verify_email_otp', [$this, 'verify_email_otp']);
    }

    /**
     * AJAX handler for sending OTP code via SMS
     */
    public function send_login_otp() {
        // Log for debugging
        error_log('PuzzlingCRM: send_login_otp called');
        
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!isset($_POST['phone_number'])) {
            error_log('PuzzlingCRM: No phone number provided');
            wp_send_json_error(['message' => 'شماره تلفن الزامی است.']);
        }

        try {
            $phone_number = sanitize_text_field($_POST['phone_number']);
            error_log('PuzzlingCRM: Phone number received: ' . $phone_number);
            
            // Convert Persian/Arabic numerals to English first
            $phone_number = $this->convert_persian_numbers($phone_number);
            error_log('PuzzlingCRM: Phone number after conversion: ' . $phone_number);
            
            // Get pattern from settings (with fallback)
            $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
            $phone_pattern = $settings['login_phone_pattern'] ?? '^09[0-9]{9}$';
            $phone_length = intval($settings['login_phone_length'] ?? 11);
            
            error_log('PuzzlingCRM: Phone pattern: ' . $phone_pattern);
            error_log('PuzzlingCRM: Phone length: ' . $phone_length);
            
            // Validate phone number format
            if (!preg_match('/' . $phone_pattern . '/', $phone_number)) {
                error_log('PuzzlingCRM: Phone format invalid');
                wp_send_json_error(['message' => 'فرمت شماره موبایل صحیح نیست. (مثال: 09123456789)']);
            }
            
            // Validate length
            if (strlen($phone_number) != $phone_length) {
                error_log('PuzzlingCRM: Phone length invalid: ' . strlen($phone_number));
                wp_send_json_error(['message' => 'طول شماره موبایل باید ' . $phone_length . ' رقم باشد.']);
            }

            // Find user by phone number or create new one
            error_log('PuzzlingCRM: Searching for user with phone: ' . $phone_number);
            $user = $this->find_user_by_phone($phone_number);
            
            if (!$user) {
                error_log('PuzzlingCRM: User not found, creating new user with phone: ' . $phone_number);
                // Create new user
                $user = $this->create_user_from_phone($phone_number);
                
                if (!$user || is_wp_error($user)) {
                    error_log('PuzzlingCRM: Failed to create user');
                    wp_send_json_error(['message' => 'خطا در ایجاد حساب کاربری. لطفاً مجدداً تلاش کنید.']);
                }
                
                error_log('PuzzlingCRM: New user created: ' . $user->user_login);
            } else {
                error_log('PuzzlingCRM: User found: ' . $user->user_login);
            }

            // Generate OTP code with configurable length
            $otp_length = intval($settings['otp_length'] ?? 6);
            $otp_code = $this->generate_otp_code($otp_length);
            
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
                $message_template = $settings['parsgreen_login_template'] ?? 'کد ورود شما: %CODE%';
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
     * AJAX handler for sending OTP code via email
     */
    public function send_email_otp() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');

        if (!isset($_POST['email'])) {
            wp_send_json_error(['message' => 'ایمیل الزامی است.']);
        }

        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'فرمت ایمیل صحیح نیست.']);
        }

        $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        $otp_length = intval($settings['otp_length'] ?? 6);
        $otp_expiry_minutes = intval($settings['otp_expiry_minutes'] ?? 5);
        $expiry_seconds = $otp_expiry_minutes * MINUTE_IN_SECONDS;

        $user = get_user_by('email', $email);
        if (!$user) {
            $user = $this->create_user_from_email($email);
            if (!$user || is_wp_error($user)) {
                wp_send_json_error(['message' => 'خطا در ایجاد حساب کاربری. لطفاً مجدداً تلاش کنید.']);
            }
        }

        $otp_code = $this->generate_otp_code($otp_length);
        $transient_key = 'puzzling_email_otp_' . md5($email);
        set_transient($transient_key, [
            'code' => $otp_code,
            'user_id' => $user->ID,
            'attempts' => 0
        ], $expiry_seconds);

        $subject = $settings['login_email_otp_subject'] ?? 'کد ورود شما';
        $body_template = $settings['login_email_otp_body'] ?? "کد ورود شما: %CODE%\nاعتبار: {$otp_expiry_minutes} دقیقه";
        $body = str_replace('%CODE%', $otp_code, $body_template);

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        $sent = wp_mail($email, $subject, $body, $headers);

        if ($sent) {
            PuzzlingCRM_Logger::add('ارسال کد ورود به ایمیل', [
                'content' => "کد ورود برای {$email} ارسال شد.",
                'type' => 'log'
            ]);
            wp_send_json_success([
                'message' => 'کد تایید به ایمیل شما ارسال شد.',
                'expires_in' => $expiry_seconds
            ]);
        } else {
            wp_send_json_error(['message' => 'خطا در ارسال ایمیل. لطفاً بعداً تلاش کنید.']);
        }
    }

    /**
     * AJAX handler for verifying email OTP code
     */
    public function verify_email_otp() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');

        if (!isset($_POST['email']) || !isset($_POST['otp_code'])) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        $email = sanitize_email($_POST['email']);
        $otp_code = sanitize_text_field($_POST['otp_code']);
        if (function_exists('tr_num')) {
            $otp_code = tr_num($otp_code, 'en');
        }

        $transient_key = 'puzzling_email_otp_' . md5($email);
        $otp_data = get_transient($transient_key);

        if (!$otp_data) {
            wp_send_json_error(['message' => 'کد تایید منقضی شده است. لطفاً کد جدید درخواست کنید.']);
        }

        $otp_max_attempts = intval(PuzzlingCRM_Settings_Handler::get_setting('otp_max_attempts', 3));
        if ($otp_data['attempts'] >= $otp_max_attempts) {
            delete_transient($transient_key);
            wp_send_json_error(['message' => 'تعداد تلاش‌های مجاز تمام شده است. لطفاً کد جدید درخواست کنید.']);
        }

        if ($otp_data['code'] !== $otp_code) {
            $otp_data['attempts']++;
            $expiry = intval(PuzzlingCRM_Settings_Handler::get_setting('otp_expiry_minutes', 5)) * MINUTE_IN_SECONDS;
            set_transient($transient_key, $otp_data, $expiry);
            $remaining = $otp_max_attempts - $otp_data['attempts'];
            wp_send_json_error([
                'message' => "کد تایید اشتباه است. ({$remaining} تلاش باقی‌مانده)"
            ]);
        }

        $user = get_user_by('ID', $otp_data['user_id']);
        if (!$user) {
            wp_send_json_error(['message' => 'کاربر یافت نشد.']);
        }

        delete_transient($transient_key);
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        PuzzlingCRM_Logger::add('ورود با کد ایمیل', [
            'content' => "کاربر {$user->user_login} با کد ایمیل وارد شد.",
            'type' => 'log',
            'user_id' => $user->ID
        ]);

        $redirect_url = $this->get_redirect_url_for_user($user);
        wp_send_json_success([
            'message' => 'ورود موفقیت‌آمیز بود.',
            'redirect_url' => $redirect_url
        ]);
    }

    /**
     * Create new user from email address
     */
    private function create_user_from_email($email) {
        $username = sanitize_user(str_replace(['@', '.'], ['_', '_'], $email), true);
        if (username_exists($username)) {
            $username = $username . '_' . time();
        }
        $user_id = wp_create_user(
            $username,
            wp_generate_password(12, true, true),
            $email
        );
        if (is_wp_error($user_id)) {
            return false;
        }
        $user = new WP_User($user_id);
        $user->set_role('client');
        PuzzlingCRM_Logger::add('ثبت نام کاربر جدید', [
            'content' => "کاربر جدید با ایمیل {$email} ثبت نام کرد.",
            'type' => 'log',
            'user_id' => $user_id
        ]);
        return $user;
    }

    /**
     * AJAX handler for traditional password login
     * Accepts username, email, or phone number as identifier.
     */
    public function login_with_password() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!isset($_POST['username']) || !isset($_POST['password'])) {
            wp_send_json_error(['message' => 'نام کاربری و رمز عبور الزامی است.']);
        }

        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;

        $username = $this->convert_persian_numbers($username);
        $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        $phone_pattern = $settings['login_phone_pattern'] ?? '^09[0-9]{9}$';

        $user = null;
        if (preg_match('/' . $phone_pattern . '/', $username)) {
            $user = $this->find_user_by_phone($username);
        }
        if (!$user) {
            $user = get_user_by('login', $username);
        }
        if (!$user) {
            $user = get_user_by('email', $username);
        }

        if (!$user) {
            wp_send_json_error(['message' => 'نام کاربری، ایمیل یا شماره موبایل یافت نشد.']);
        }

        // Check password
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_send_json_error(['message' => 'رمز عبور اشتباه است.']);
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
     * Create new user from phone number
     */
    private function create_user_from_phone($phone_number) {
        // Generate username from phone number
        $username = 'user_' . $phone_number;
        
        // Check if username already exists (shouldn't happen, but just in case)
        if (username_exists($username)) {
            $username = 'user_' . $phone_number . '_' . time();
        }
        
        // Create user with phone as username
        $user_id = wp_create_user(
            $username,
            wp_generate_password(12, true, true), // Random temporary password
            '' // No email required for now
        );
        
        if (is_wp_error($user_id)) {
            error_log('PuzzlingCRM: Error creating user: ' . $user_id->get_error_message());
            return false;
        }
        
        // Save phone number in user meta
        update_user_meta($user_id, 'pzl_mobile_phone', $phone_number);
        
        // Set default role (client/customer)
        $user = new WP_User($user_id);
        $user->set_role('client');
        
        // Log the registration
        PuzzlingCRM_Logger::add('ثبت نام کاربر جدید', [
            'content' => "کاربر جدید با شماره موبایل {$phone_number} ثبت نام کرد.",
            'type' => 'log',
            'user_id' => $user_id
        ]);
        
        return $user;
    }

    /**
     * Generate an OTP code with specified length
     */
    private function generate_otp_code($length = 6) {
        $max = pow(10, $length) - 1;
        return str_pad(rand(0, $max), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Convert Persian/Arabic numerals to English
     */
    private function convert_persian_numbers($string) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        $string = str_replace($persian, $english, $string);
        $string = str_replace($arabic, $english, $string);
        
        return $string;
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

    /**
     * AJAX handler for setting password after OTP verification
     */
    public function set_password() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!isset($_POST['phone_number']) || !isset($_POST['password']) || !isset($_POST['confirm_password'])) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        $phone_number = sanitize_text_field($_POST['phone_number']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Convert Persian/Arabic numerals to English
        $phone_number = $this->convert_persian_numbers($phone_number);

        if (empty($phone_number) || empty($password) || empty($confirm_password)) {
            wp_send_json_error(['message' => 'تمام فیلدها الزامی است.']);
        }

        if ($password !== $confirm_password) {
            wp_send_json_error(['message' => 'رمز عبور و تکرار آن یکسان نیست.']);
        }

        if (strlen($password) < 6) {
            wp_send_json_error(['message' => 'رمز عبور باید حداقل 6 کاراکتر باشد.']);
        }

        // Check if user exists by phone
        $user = $this->find_user_by_phone($phone_number);
        
        if (!$user) {
            wp_send_json_error(['message' => 'کاربر یافت نشد. لطفاً مجدداً کد تایید را وارد کنید.']);
        }

        // Update user password
        wp_set_password($password, $user->ID);

        // Log in the user
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        // Log the password setting
        PuzzlingCRM_Logger::add('تنظیم رمز عبور', [
            'content' => "کاربر {$user->user_login} رمز عبور خود را تنظیم کرد.",
            'type' => 'log',
            'user_id' => $user->ID
        ]);

        // Get redirect URL
        $redirect_url = $this->get_redirect_url_for_user($user);

        wp_send_json_success([
            'message' => 'رمز عبور با موفقیت تنظیم شد.',
            'redirect_url' => $redirect_url
        ]);
    }

    /**
     * AJAX handler for auto login (without OTP)
     */
    public function auto_login() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!isset($_POST['phone_number'])) {
            wp_send_json_error(['message' => 'شماره موبایل الزامی است.']);
        }

        $phone_number = sanitize_text_field($_POST['phone_number']);
        $phone_number = $this->convert_persian_numbers($phone_number);

        if (empty($phone_number)) {
            wp_send_json_error(['message' => 'شماره موبایل الزامی است.']);
        }

        // Find user by phone number
        $user = $this->find_user_by_phone($phone_number);
        
        if (!$user) {
            wp_send_json_error(['message' => 'کاربری با این شماره موبایل یافت نشد. لطفاً ابتدا ثبت نام کنید.']);
        }

        // Check if user has a password set
        $user_data = get_userdata($user->ID);
        $has_password = !empty($user_data->user_pass);
        
        if (!$has_password) {
            wp_send_json_error(['message' => 'لطفاً ابتدا رمز عبور خود را تنظیم کنید.']);
        }

        // Log in the user
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        // Log the auto login
        PuzzlingCRM_Logger::add('ورود خودکار', [
            'content' => "کاربر {$user->user_login} با ورود خودکار وارد شد.",
            'type' => 'log',
            'user_id' => $user->ID
        ]);

        // Get redirect URL
        $redirect_url = $this->get_redirect_url_for_user($user);

        wp_send_json_success([
            'message' => 'ورود خودکار موفقیت‌آمیز بود.',
            'redirect_url' => $redirect_url
        ]);
    }
}

