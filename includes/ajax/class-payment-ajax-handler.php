<?php
/**
 * Payment AJAX Handler - Online Payment Integration
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

class PuzzlingCRM_Payment_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_initiate_payment', [$this, 'initiate_payment']);
        add_action('wp_ajax_nopriv_puzzling_verify_payment', [$this, 'verify_payment']);
        add_action('wp_ajax_puzzling_verify_payment', [$this, 'verify_payment']);
    }

    /**
     * Initiate Payment
     */
    public function initiate_payment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'لطفاً وارد شوید.']);
        }

        $installment_id = isset($_POST['installment_id']) ? intval($_POST['installment_id']) : 0;
        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
        
        if (!$contract_id || !$amount) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        // Get Zarinpal settings
        $merchant = get_option('puzzlingcrm_zarinpal_merchant', '');
        $sandbox = get_option('puzzlingcrm_zarinpal_sandbox', '0');
        
        if (empty($merchant)) {
            wp_send_json_error(['message' => 'درگاه پرداخت تنظیم نشده است.']);
        }

        // Request payment
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-zarinpal-handler.php';
        
        $description = 'پرداخت قسط قرارداد شماره ' . get_post_meta($contract_id, '_contract_number', true);
        $callback_url = home_url('/dashboard/payment-callback');
        
        $zarinpal = new PuzzlingCRM_Zarinpal_Handler($merchant, $sandbox === '1');
        $result = $zarinpal->request_payment($amount, $description, $callback_url);
        
        if ($result['success']) {
            // Save payment info
            $payment_data = [
                'contract_id' => $contract_id,
                'installment_id' => $installment_id,
                'amount' => $amount,
                'authority' => $result['authority'],
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ];
            
            update_option('pzl_pending_payment_' . $result['authority'], $payment_data, false);
            
            wp_send_json_success([
                'message' => 'در حال انتقال به درگاه پرداخت...',
                'payment_url' => $result['payment_url']
            ]);
        } else {
            wp_send_json_error(['message' => $result['message'] ?? 'خطا در اتصال به درگاه پرداخت.']);
        }
    }

    /**
     * Verify Payment
     */
    public function verify_payment() {
        $authority = isset($_GET['Authority']) ? sanitize_text_field($_GET['Authority']) : '';
        $status = isset($_GET['Status']) ? sanitize_text_field($_GET['Status']) : '';
        
        if (!$authority || $status !== 'OK') {
            wp_send_json_error(['message' => 'پرداخت ناموفق بود.']);
        }

        // Get payment data
        $payment_data = get_option('pzl_pending_payment_' . $authority);
        
        if (!$payment_data) {
            wp_send_json_error(['message' => 'اطلاعات پرداخت یافت نشد.']);
        }

        // Verify with Zarinpal
        $merchant = get_option('puzzlingcrm_zarinpal_merchant', '');
        $sandbox = get_option('puzzlingcrm_zarinpal_sandbox', '0');
        
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-zarinpal-handler.php';
        
        $zarinpal = new PuzzlingCRM_Zarinpal_Handler($merchant, $sandbox === '1');
        $result = $zarinpal->verify_payment($authority, $payment_data['amount']);
        
        if ($result['success']) {
            // Update installment status
            $contract_id = $payment_data['contract_id'];
            $installments = get_post_meta($contract_id, '_installments', true) ?: [];
            
            foreach ($installments as &$inst) {
                if ($inst['id'] == $payment_data['installment_id']) {
                    $inst['status'] = 'paid';
                    $inst['paid_date'] = current_time('mysql');
                    $inst['ref_id'] = $result['ref_id'];
                    break;
                }
            }
            
            update_post_meta($contract_id, '_installments', $installments);
            
            // Delete pending payment
            delete_option('pzl_pending_payment_' . $authority);
            
            // Log
            PuzzlingCRM_Logger::log('payment_success', [
                'contract_id' => $contract_id,
                'amount' => $payment_data['amount'],
                'ref_id' => $result['ref_id']
            ]);
            
            wp_send_json_success([
                'message' => 'پرداخت با موفقیت انجام شد.',
                'ref_id' => $result['ref_id']
            ]);
        } else {
            wp_send_json_error(['message' => $result['message'] ?? 'تایید پرداخت ناموفق بود.']);
        }
    }
}

