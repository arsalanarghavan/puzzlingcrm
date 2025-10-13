<?php
/**
 * PuzzlingCRM Lead AJAX Handler
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Lead_Ajax_Handler {

    public function __construct() {
        // Add a new lead
        add_action('wp_ajax_puzzling_add_lead', [ $this, 'add_lead' ]);
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
        
        // Save custom fields
        update_post_meta($lead_id, '_first_name', $first_name);
        update_post_meta($lead_id, '_last_name', $last_name);
        update_post_meta($lead_id, '_mobile', $mobile);
        update_post_meta($lead_id, '_business_name', $business_name);
        
        // Set default status
        $lead_statuses = get_option('puzzling_lead_statuses', [['name' => 'جدید', 'color' => '#0073aa']]);
        if ( ! empty( $lead_statuses ) ) {
            wp_set_object_terms($lead_id, $lead_statuses[0]['name'], 'lead_status');
        }
        
        // Send automatic SMS if enabled
        $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        if (isset($settings['lead_auto_sms_enabled']) && $settings['lead_auto_sms_enabled'] == '1' && !empty($settings['lead_auto_sms_template'])) {
            $sms_message = str_replace(
                ['{first_name}', '{last_name}', '{business_name}'],
                [$first_name, $last_name, $business_name],
                $settings['lead_auto_sms_template']
            );
            
            $sms_service = PuzzlingCRM_SMS_Handler::get_sms_service();
            if ($sms_service) {
                $sms_service->send($mobile, $sms_message);
            }
        }
        
        wp_send_json_success(['message' => __('سرنخ با موفقیت ثبت شد.', 'puzzlingcrm')]);
    }
}