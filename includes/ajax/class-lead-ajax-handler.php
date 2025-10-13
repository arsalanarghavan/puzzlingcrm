<?php
/**
 * PuzzlingCRM Lead AJAX Handler (Production Ready)
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure the Settings Handler class is available, as it's crucial for the logic below.
require_once dirname( __FILE__ ) . '/../class-settings-handler.php';

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

        // Validate mobile number format
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
        
        // Save custom fields
        update_post_meta($lead_id, '_first_name', $first_name);
        update_post_meta($lead_id, '_last_name', $last_name);
        update_post_meta($lead_id, '_mobile', $mobile);
        update_post_meta($lead_id, '_business_name', $business_name);
        
        // Set default status
        $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        $default_status_from_settings = !empty($settings['lead_default_status']) ? $settings['lead_default_status'] : null;

        if ($default_status_from_settings) {
            wp_set_object_terms($lead_id, $default_status_from_settings, 'lead_status');
        } else {
            $all_statuses = get_terms([
                'taxonomy' => 'lead_status',
                'hide_empty' => false,
                'number' => 1,
                'orderby' => 'term_id',
                'order' => 'ASC',
            ]);

            if (!empty($all_statuses) && !is_wp_error($all_statuses)) {
                wp_set_object_terms($lead_id, $all_statuses[0]->slug, 'lead_status');
            }
        }
        
        $sms_sent = true;
        $sms_error_message = '';
        
        // Robust SMS Sending Logic
        if (isset($settings['lead_auto_sms_enabled']) && $settings['lead_auto_sms_enabled'] == '1' && !empty($settings['lead_auto_sms_template'])) {
            if (class_exists('PuzzlingCRM_SMS_Handler')) {
                $sms_message = str_replace(
                    ['{first_name}', '{last_name}', '{business_name}'],
                    [$first_name, $last_name, $business_name],
                    $settings['lead_auto_sms_template']
                );
                
                $sms_service = PuzzlingCRM_SMS_Handler::get_sms_service();
                if ($sms_service) {
                    try {
                        $result = $sms_service->send($mobile, $sms_message);
                        if (!$result) {
                            $sms_sent = false;
                            $sms_error_message = __('ارسال پیامک با خطا مواجه شد.', 'puzzlingcrm');
                        }
                    } catch (Exception $e) {
                        $sms_sent = false;
                        $sms_error_message = __('خطایی در سرویس پیامک رخ داد.', 'puzzlingcrm');
                    }
                } else {
                    $sms_sent = false;
                    $sms_error_message = __('سرویس پیامک فعال نیست.', 'puzzlingcrm');
                }
            } else {
                 $sms_sent = false;
                 $sms_error_message = __('کلاس مدیریت پیامک یافت نشد.', 'puzzlingcrm');
            }
        }
        
        $success_message = __('سرنخ با موفقیت ثبت شد.', 'puzzlingcrm');
        if (!$sms_sent) {
            $success_message .= ' ' . $sms_error_message;
        }

        wp_send_json_success(['message' => $success_message, 'reload' => true]); // Added reload flag
    }
}