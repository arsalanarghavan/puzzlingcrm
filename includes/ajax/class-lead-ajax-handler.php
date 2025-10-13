<?php
/**
 * PuzzlingCRM Lead AJAX Handler (Final Patched Version - Hotfix for Edit Status & JS Error)
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure the Settings Handler class is available.
require_once dirname( __FILE__ ) . '/../class-settings-handler.php';

class PuzzlingCRM_Lead_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_add_lead', [ $this, 'add_lead' ]);
        add_action('wp_ajax_puzzling_delete_lead', [ $this, 'delete_lead' ]);
        add_action('wp_ajax_puzzling_edit_lead', [ $this, 'edit_lead' ]);
        add_action('wp_ajax_puzzling_change_lead_status', [ $this, 'change_lead_status' ]);
        add_action('wp_ajax_puzzling_add_lead_status', [ $this, 'add_lead_status' ]);
        add_action('wp_ajax_puzzling_delete_lead_status', [ $this, 'delete_lead_status' ]);
    }

    /**
     * Handles adding a new lead.
     */
    public function add_lead() {
        check_ajax_referer('puzzling_add_lead_nonce', 'security');
        if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'system_manager' ) ) ) {
            wp_send_json_error(['message' => __('شما دسترسی لازم برای انجام این کار را ندارید.', 'puzzlingcrm')]);
        }

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $mobile = sanitize_text_field($_POST['mobile']);
        $business_name = sanitize_text_field($_POST['business_name']);
        $notes = sanitize_textarea_field($_POST['notes']);

        if (empty($first_name) || empty($last_name) || empty($mobile)) {
            wp_send_json_error(['message' => __('نام، نام خانوادگی و شماره موبایل ضروری هستند.', 'puzzlingcrm')]);
        }

        $lead_id = wp_insert_post([
            'post_type' => 'pzl_lead',
            'post_title' => $first_name . ' ' . $last_name,
            'post_content' => $notes,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($lead_id)) {
            wp_send_json_error(['message' => __('خطایی در ثبت سرنخ رخ داد.', 'puzzlingcrm')]);
        }
        
        update_post_meta($lead_id, '_first_name', $first_name);
        update_post_meta($lead_id, '_last_name', $last_name);
        update_post_meta($lead_id, '_mobile', $mobile);
        update_post_meta($lead_id, '_business_name', $business_name);
        
        $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        $default_status = !empty($settings['lead_default_status']) ? $settings['lead_default_status'] : null;

        if ($default_status) {
            wp_set_object_terms($lead_id, $default_status, 'lead_status');
        } else {
            $first_status = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false, 'number' => 1, 'orderby' => 'term_id', 'order' => 'ASC']);
            if (!empty($first_status) && !is_wp_error($first_status)) {
                wp_set_object_terms($lead_id, $first_status[0]->slug, 'lead_status');
            }
        }
        
        wp_send_json_success(['message' => __('سرنخ با موفقیت ثبت شد.', 'puzzlingcrm'), 'reload' => true]);
    }

    /**
     * Handles editing an existing lead.
     */
    public function edit_lead() {
        check_ajax_referer('puzzling_edit_lead_nonce', 'security');
        if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'system_manager' ) ) ) {
            wp_send_json_error(['message' => __('شما دسترسی لازم برای انجام این کار را ندارید.', 'puzzlingcrm')]);
        }

        $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
        if ($lead_id <= 0) {
            wp_send_json_error(['message' => __('شناسه سرنخ نامعتبر است.', 'puzzlingcrm')]);
        }

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $mobile = sanitize_text_field($_POST['mobile']);
        $business_name = sanitize_text_field($_POST['business_name']);
        $notes = sanitize_textarea_field($_POST['notes']);

        if (empty($first_name) || empty($last_name) || empty($mobile)) {
            wp_send_json_error(['message' => __('نام، نام خانوادگی و شماره موبایل ضروری هستند.', 'puzzlingcrm')]);
        }

        $post_data = ['ID' => $lead_id, 'post_title' => $first_name . ' ' . $last_name, 'post_content' => $notes];
        $result = wp_update_post($post_data, true);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => __('خطایی در به‌روزرسانی سرنخ رخ داد.', 'puzzlingcrm')]);
        }

        update_post_meta($lead_id, '_first_name', $first_name);
        update_post_meta($lead_id, '_last_name', $last_name);
        update_post_meta($lead_id, '_mobile', $mobile);
        update_post_meta($lead_id, '_business_name', $business_name);

        // **CRITICAL FIX**: Added error checking for term setting.
        if (isset($_POST['lead_status'])) {
            $status_slug = sanitize_text_field($_POST['lead_status']);
            if (!empty($status_slug)) {
                $term_result = wp_set_object_terms($lead_id, $status_slug, 'lead_status');
                if (is_wp_error($term_result)) {
                    wp_send_json_error(['message' => 'خطایی در هنگام به‌روزرسانی وضعیت رخ داد: ' . $term_result->get_error_message()]);
                }
            }
        }

        $redirect_url = admin_url('admin.php?page=puzzling-leads');
        if ( ! empty( $_POST['_wp_http_referer'] ) ) {
            $redirect_url = esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) );
        }
        
        wp_send_json_success(['message' => __('تغییرات با موفقیت ذخیره شد.', 'puzzlingcrm'), 'redirect_url' => $redirect_url]);
    }

    /**
     * Handles changing a lead's status from the list view.
     */
    public function change_lead_status() {
        check_ajax_referer('puzzling_change_lead_status_nonce', 'security');
        if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'system_manager' ) ) ) {
            wp_send_json_error(['message' => __('شما دسترسی لازم برای انجام این کار را ندارید.', 'puzzlingcrm')]);
        }

        $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';

        if ($lead_id <= 0 || empty($new_status)) {
            wp_send_json_error(['message' => __('اطلاعات ارسالی نامعتبر است.', 'puzzlingcrm')]);
        }

        $result = wp_set_object_terms($lead_id, $new_status, 'lead_status');
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => __('خطایی در تغییر وضعیت رخ داد.', 'puzzlingcrm')]);
        }

        wp_send_json_success(['message' => __('وضعیت با موفقیت به‌روزرسانی شد.', 'puzzlingcrm')]);
    }

    /**
     * Handles deleting a lead.
     */
    public function delete_lead() {
        check_ajax_referer('puzzling_delete_lead_nonce', 'security');
        if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'system_manager' ) ) ) {
            wp_send_json_error(['message' => __('شما دسترسی لازم برای انجام این کار را ندارید.', 'puzzlingcrm')]);
        }

        $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
        if ($lead_id <= 0) {
            wp_send_json_error(['message' => __('شناسه سرنخ نامعتبر است.', 'puzzlingcrm')]);
        }

        $result = wp_delete_post($lead_id, true);
        if ($result === false) {
            wp_send_json_error(['message' => __('خطایی در حذف سرنخ رخ داد.', 'puzzlingcrm')]);
        }

        wp_send_json_success(['message' => __('سرنخ با موفقیت حذف شد.', 'puzzlingcrm'), 'reload' => true]);
    }

    /**
     * AJAX handler to add a new lead status term from the settings page.
     */
    public function add_lead_status() {
        check_ajax_referer('puzzling_add_lead_status_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        $status_name = isset($_POST['status_name']) ? sanitize_text_field(trim($_POST['status_name'])) : '';
        if (empty($status_name)) {
            wp_send_json_error(['message' => 'نام وضعیت نمی‌تواند خالی باشد.']);
        }

        if (term_exists($status_name, 'lead_status')) {
            wp_send_json_error(['message' => 'این وضعیت از قبل وجود دارد.']);
        }

        $result = wp_insert_term($status_name, 'lead_status');
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        $term = get_term($result['term_id'], 'lead_status');
        wp_send_json_success(['term_id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug]);
    }

    /**
     * AJAX handler to delete a lead status term from the settings page.
     */
    public function delete_lead_status() {
        check_ajax_referer('puzzling_delete_lead_status_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        if ($term_id <= 0) {
            wp_send_json_error(['message' => 'شناسه وضعیت نامعتبر است.']);
        }

        $term = get_term($term_id, 'lead_status');
        if (!$term || is_wp_error($term)) {
            wp_send_json_error(['message' => 'وضعیت مورد نظر یافت نشد.']);
        }

        $result = wp_delete_term($term_id, 'lead_status');
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'وضعیت با موفقیت حذف شد.', 'slug' => $term->slug]);
    }
}