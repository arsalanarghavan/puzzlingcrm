<?php
/**
 * PDF AJAX Handler
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

class PuzzlingCRM_PDF_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_generate_contract_pdf', [$this, 'generate_contract_pdf']);
        add_action('wp_ajax_puzzling_generate_invoice_pdf', [$this, 'generate_invoice_pdf']);
        add_action('wp_ajax_puzzling_download_pdf', [$this, 'download_pdf']);
    }

    /**
     * Generate Contract PDF
     */
    public function generate_contract_pdf() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        
        if (!$contract_id) {
            wp_send_json_error(['message' => 'شناسه قرارداد نامعتبر است.']);
        }

        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-pdf-generator.php';
        
        $pdf = new PuzzlingCRM_PDF_Generator();
        $result = $pdf->generate_contract_pdf($contract_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'فایل PDF با موفقیت ایجاد شد.',
                'pdf_url' => $result['url'],
                'filename' => $result['filename']
            ]);
        } else {
            wp_send_json_error(['message' => 'خطا در ایجاد فایل PDF.']);
        }
    }

    /**
     * Generate Invoice PDF
     */
    public function generate_invoice_pdf() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
        
        if (!$invoice_id) {
            wp_send_json_error(['message' => 'شناسه پیش‌فاکتور نامعتبر است.']);
        }

        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-pdf-generator.php';
        
        $pdf = new PuzzlingCRM_PDF_Generator();
        $result = $pdf->generate_invoice_pdf($invoice_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'فایل PDF با موفقیت ایجاد شد.',
                'pdf_url' => $result['url'],
                'filename' => $result['filename']
            ]);
        } else {
            wp_send_json_error(['message' => 'خطا در ایجاد فایل PDF.']);
        }
    }

    /**
     * Download PDF
     */
    public function download_pdf() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $file_url = isset($_POST['file_url']) ? esc_url_raw($_POST['file_url']) : '';
        
        if (!$file_url) {
            wp_send_json_error(['message' => 'آدرس فایل نامعتبر است.']);
        }

        wp_send_json_success([
            'download_url' => $file_url
        ]);
    }
}

