<?php
/**
 * PuzzlingCRM Lead AJAX Handler (Final Patched Version for Redirect)
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure the Settings Handler class is available, as it's crucial for the logic below.
require_once dirname( __FILE__ ) . '/../class-settings-handler.php';

class PuzzlingCRM_Lead_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_add_lead', [ $this, 'add_lead' ]);
        add_action('wp_ajax_puzzling_delete_lead', [ $this, 'delete_lead' ]);
        add_action('wp_ajax_puzzling_edit_lead', [ $this, 'edit_lead' ]);
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

        if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
            wp_send_json_error(['message' => __('لطفا شماره موبایل را در قالب صحیح وارد کنید. مثال: 09123456789', 'puzzlingcrm')]);
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
        $default_status_from_settings = !empty($settings['lead_default_status']) ? $settings['lead_default_status'] : null;

        if ($default_status_from_settings) {
            wp_set_object_terms($lead_id, $default_status_from_settings, 'lead_status');
        } else {
            $all_statuses = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false, 'number' => 1, 'orderby' => 'term_id', 'order' => 'ASC']);
            if (!empty($all_statuses) && !is_wp_error($all_statuses)) {
                wp_set_object_terms($lead_id, $all_statuses[0]->slug, 'lead_status');
            }
        }
        
        $success_message = __('سرنخ با موفقیت ثبت شد.', 'puzzlingcrm');
        wp_send_json_success(['message' => $success_message, 'reload' => true]);
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

        if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
            wp_send_json_error(['message' => __('لطفا شماره موبایل را در قالب صحیح وارد کنید. مثال: 09123456789', 'puzzlingcrm')]);
        }

        $post_data = [
            'ID'           => $lead_id,
            'post_title'   => $first_name . ' ' . $last_name,
            'post_content' => $notes,
        ];

        $result = wp_update_post($post_data, true);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => __('خطایی در به‌روزرسانی سرنخ رخ داد.', 'puzzlingcrm')]);
        }

        update_post_meta($lead_id, '_first_name', $first_name);
        update_post_meta($lead_id, '_last_name', $last_name);
        update_post_meta($lead_id, '_mobile', $mobile);
        update_post_meta($lead_id, '_business_name', $business_name);

        // **PATCHED**: Use the referer URL sent from the form for a correct redirect.
        $redirect_url = admin_url('admin.php?page=puzzling-leads'); // Default fallback
        if ( ! empty( $_POST['_wp_http_referer'] ) ) {
            // The unslash is important for nested query args.
            $redirect_url = esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) );
        }
        
        wp_send_json_success([
            'message'      => __('تغییرات با موفقیت ذخیره شد.', 'puzzlingcrm'),
            'redirect_url' => $redirect_url
        ]);
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

        $result = wp_delete_post($lead_id, true); // true = force delete

        if ($result === false) {
            wp_send_json_error(['message' => __('خطایی در حذف سرنخ رخ داد.', 'puzzlingcrm')]);
        }

        wp_send_json_success(['message' => __('سرنخ با موفقیت حذف شد.', 'puzzlingcrm'), 'reload' => true]);
    }
}