<?php
/**
 * License AJAX Handler
 *
 * Handles all AJAX requests for license management
 *
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) {
    exit;
}

class PuzzlingCRM_License_Ajax_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_puzzlingcrm_get_licenses', [$this, 'ajax_get_licenses']);
        add_action('wp_ajax_puzzlingcrm_add_license', [$this, 'ajax_add_license']);
        add_action('wp_ajax_puzzlingcrm_update_license', [$this, 'ajax_update_license']);
        add_action('wp_ajax_puzzlingcrm_renew_license', [$this, 'ajax_renew_license']);
        add_action('wp_ajax_puzzlingcrm_cancel_license', [$this, 'ajax_cancel_license']);
        add_action('wp_ajax_puzzlingcrm_delete_license', [$this, 'ajax_delete_license']);
    }


    /**
     * Get all licenses
     */
    public function ajax_get_licenses() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $licenses = PuzzlingCRM_License_Manager::get_all_licenses();

        wp_send_json_success([
            'licenses' => $licenses
        ]);
    }

    /**
     * Add new license
     */
    public function ajax_add_license() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'puzzlingcrm-ajax-nonce')) {
            wp_send_json_error(['message' => 'خطای امنیتی. لطفاً صفحه را refresh کنید.']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما اجازه انجام این عمل را ندارید.']);
        }

        $project_name = isset($_POST['project_name']) ? sanitize_text_field($_POST['project_name']) : '';
        $domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : '';
        $expiry_date = isset($_POST['expiry_date']) && !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : current_time('mysql');
        $logo_url = isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active';

        if (empty($project_name) || empty($domain)) {
            wp_send_json_error(['message' => 'لطفاً تمام فیلدهای الزامی را پر کنید']);
        }

        // Normalize domain (remove www, lowercase, trim)
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^www\./', '', $domain);
        
        // Validate domain format (more lenient)
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/', $domain)) {
            wp_send_json_error(['message' => 'فرمت دامنه نامعتبر است. مثال: example.com']);
        }

        // Domain is the license - no license_key needed
        $data = [
            'project_name' => $project_name,
            'domain' => $domain,
            'expiry_date' => $expiry_date,
            'start_date' => $start_date,
            'logo_url' => $logo_url,
            'status' => $status
        ];

        $license_id = PuzzlingCRM_License_Manager::add_license($data);

        if ($license_id === false) {
            // More detailed error message
            global $wpdb;
            $table_name = $wpdb->prefix . 'puzzlingcrm_licenses';
            $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE domain = %s", $domain));
            
            if ($existing) {
                wp_send_json_error(['message' => 'این دامنه قبلاً ثبت شده است.']);
            } else {
                wp_send_json_error(['message' => 'خطا در افزودن لایسنس. لطفاً دوباره تلاش کنید.']);
            }
        }

        $license = PuzzlingCRM_License_Manager::get_all_licenses();
        $new_license = null;
        foreach ($license as $l) {
            if ($l['id'] == $license_id) {
                $new_license = $l;
                break;
            }
        }

        wp_send_json_success([
            'message' => 'لایسنس با موفقیت افزوده شد',
            'license' => $new_license
        ]);
    }

    /**
     * Update license
     */
    public function ajax_update_license() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(['message' => 'شناسه لایسنس نامعتبر است']);
        }

        $data = [];

        if (isset($_POST['project_name'])) {
            $data['project_name'] = sanitize_text_field($_POST['project_name']);
        }
        if (isset($_POST['domain'])) {
            $data['domain'] = sanitize_text_field($_POST['domain']);
        }
        if (isset($_POST['license_key'])) {
            $data['license_key'] = sanitize_text_field($_POST['license_key']);
        }
        if (isset($_POST['expiry_date'])) {
            $data['expiry_date'] = sanitize_text_field($_POST['expiry_date']);
        }
        if (isset($_POST['start_date'])) {
            $data['start_date'] = sanitize_text_field($_POST['start_date']);
        }
        if (isset($_POST['logo_url'])) {
            $data['logo_url'] = esc_url_raw($_POST['logo_url']);
        }
        if (isset($_POST['status'])) {
            $data['status'] = sanitize_text_field($_POST['status']);
        }

        if (empty($data)) {
            wp_send_json_error(['message' => 'هیچ داده‌ای برای به‌روزرسانی ارسال نشده است']);
        }

        $result = PuzzlingCRM_License_Manager::update_license($id, $data);

        if (!$result) {
            wp_send_json_error(['message' => 'خطا در به‌روزرسانی لایسنس']);
        }

        wp_send_json_success(['message' => 'لایسنس با موفقیت به‌روزرسانی شد']);
    }

    /**
     * Renew license
     */
    public function ajax_renew_license() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $new_expiry_date = isset($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : '';

        if (!$id || empty($new_expiry_date)) {
            wp_send_json_error(['message' => 'لطفاً تاریخ انقضای جدید را وارد کنید']);
        }

        $result = PuzzlingCRM_License_Manager::renew_license($id, $new_expiry_date);

        if (!$result) {
            wp_send_json_error(['message' => 'خطا در تمدید لایسنس']);
        }

        wp_send_json_success(['message' => 'لایسنس با موفقیت تمدید شد']);
    }

    /**
     * Cancel license
     */
    public function ajax_cancel_license() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(['message' => 'شناسه لایسنس نامعتبر است']);
        }

        $result = PuzzlingCRM_License_Manager::cancel_license($id);

        if (!$result) {
            wp_send_json_error(['message' => 'خطا در لغو لایسنس']);
        }

        wp_send_json_success(['message' => 'لایسنس با موفقیت لغو شد']);
    }

    /**
     * Delete license
     */
    public function ajax_delete_license() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(['message' => 'شناسه لایسنس نامعتبر است']);
        }

        $result = PuzzlingCRM_License_Manager::delete_license($id);

        if (!$result) {
            wp_send_json_error(['message' => 'خطا در حذف لایسنس']);
        }

        wp_send_json_success(['message' => 'لایسنس با موفقیت حذف شد']);
    }
}

