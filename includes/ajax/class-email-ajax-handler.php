<?php
/**
 * Email AJAX Handler
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

class PuzzlingCRM_Email_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_send_contract_email', [$this, 'send_contract_email']);
        add_action('wp_ajax_puzzling_send_invoice_email', [$this, 'send_invoice_email']);
    }

    /**
     * Send Contract Email
     */
    public function send_contract_email() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (!$contract_id) {
            wp_send_json_error(['message' => 'شناسه قرارداد نامعتبر است.']);
        }

        if (!$email || !is_email($email)) {
            wp_send_json_error(['message' => 'ایمیل نامعتبر است.']);
        }

        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-email-handler.php';
        
        $sent = PuzzlingCRM_Email_Handler::send_contract_email($contract_id, $email);
        
        if ($sent) {
            wp_send_json_success(['message' => 'ایمیل با موفقیت ارسال شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در ارسال ایمیل. لطفاً تنظیمات ایمیل سرور را بررسی کنید.']);
        }
    }

    /**
     * Send Invoice Email
     */
    public function send_invoice_email() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (!$invoice_id) {
            wp_send_json_error(['message' => 'شناسه پیش‌فاکتور نامعتبر است.']);
        }

        if (!$email || !is_email($email)) {
            wp_send_json_error(['message' => 'ایمیل نامعتبر است.']);
        }

        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-email-handler.php';
        
        $sent = PuzzlingCRM_Email_Handler::send_invoice_email($invoice_id, $email);
        
        if ($sent) {
            wp_send_json_success(['message' => 'پیش‌فاکتور با موفقیت ارسال شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در ارسال ایمیل.']);
        }
    }
}

