<?php
/**
 * PuzzlingCRM Consultation AJAX Handler
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Consultation_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_manage_consultation', [$this, 'ajax_manage_consultation']);
        add_action('wp_ajax_puzzling_convert_consultation_to_project', [$this, 'ajax_convert_consultation_to_project']);
    }

    /**
     * AJAX handler for creating and updating consultations.
     */
    public function ajax_manage_consultation() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $consultation_id = isset($_POST['consultation_id']) ? intval($_POST['consultation_id']) : 0;
        $name = sanitize_text_field($_POST['name']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        $type = sanitize_key($_POST['type']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $status_slug = sanitize_key($_POST['status']);
        $notes = isset($_POST['notes']) ? wp_kses_post($_POST['notes']) : '';

        if (empty($name) || empty($phone)) {
            wp_send_json_error(['message' => 'نام و شماره تماس الزامی است.']);
        }

        $post_title = $name;
        $datetime = !empty($date) ? puzzling_jalali_to_gregorian($date) . ' ' . $time : '';
        
        $post_data = [
            'post_title' => $post_title,
            'post_content' => $notes,
            'post_status' => 'publish',
            'post_type' => 'pzl_consultation',
        ];

        if ($consultation_id > 0) {
            $post_data['ID'] = $consultation_id;
            $result = wp_update_post($post_data, true);
            $message = 'مشاوره با موفقیت به‌روزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data, true);
            $message = 'مشاوره جدید با موفقیت ثبت شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در پردازش مشاوره.']);
        }

        $the_consultation_id = is_int($result) ? $result : $consultation_id;
        
        update_post_meta($the_consultation_id, '_consultation_phone', $phone);
        update_post_meta($the_consultation_id, '_consultation_email', $email);
        update_post_meta($the_consultation_id, '_consultation_type', $type);
        update_post_meta($the_consultation_id, '_consultation_datetime', $datetime);
        wp_set_object_terms($the_consultation_id, $status_slug, 'consultation_status');

        wp_send_json_success(['message' => $message, 'reload' => true]);
    }
    
    /**
     * AJAX handler for converting a consultation to a project.
     */
    public function ajax_convert_consultation_to_project() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        $consultation_id = intval($_POST['consultation_id']);
        $consultation = get_post($consultation_id);

        if (!$consultation || $consultation->post_type !== 'pzl_consultation') {
            wp_send_json_error(['message' => 'مشاوره یافت نشد.']);
        }

        $name = $consultation->post_title;
        $email = get_post_meta($consultation_id, '_consultation_email', true);
        $phone = get_post_meta($consultation_id, '_consultation_phone', true);

        // Find or create user
        $customer_id = 0;
        if (!empty($email) && email_exists($email)) {
            $user = get_user_by('email', $email);
            $customer_id = $user->ID;
        } else {
            // Create a new user
            $username = !empty($email) ? sanitize_user(substr($email, 0, strpos($email, '@')), true) : sanitize_user('user_' . $phone, true);
            if (empty($username) || username_exists($username)) {
                $username = 'user_' . time();
            }
            $password = wp_generate_password();
            $customer_id = wp_create_user($username, $password, $email);

            if (is_wp_error($customer_id)) {
                 wp_send_json_error(['message' => 'خطا در ایجاد کاربر جدید: ' . $customer_id->get_error_message()]);
            }
            wp_update_user(['ID' => $customer_id, 'display_name' => $name, 'first_name' => $name, 'role' => 'customer']);
            update_user_meta($customer_id, 'pzl_mobile_phone', $phone);
        }

        // Create Contract
        $contract_id = wp_insert_post([
            'post_title' => 'قرارداد برای ' . $name,
            'post_author' => $customer_id,
            'post_status' => 'publish',
            'post_type' => 'contract',
        ]);
        
        if (is_wp_error($contract_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد قرارداد.']);
        }
        
        // Create Project
        $project_id = wp_insert_post([
            'post_title' => 'پروژه جدید برای ' . $name,
            'post_author' => $customer_id,
            'post_status' => 'publish',
            'post_type' => 'project',
        ]);

        if (is_wp_error($project_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد پروژه.']);
        }
        
        // Link project to contract
        update_post_meta($project_id, '_contract_id', $contract_id);
        
        // Update consultation status
        wp_set_object_terms($consultation_id, 'converted', 'consultation_status');

        $edit_contract_url = add_query_arg(['view' => 'contracts', 'action' => 'edit', 'contract_id' => $contract_id], puzzling_get_dashboard_url());

        wp_send_json_success(['message' => 'مشاوره با موفقیت به پروژه تبدیل شد. در حال انتقال به صفحه قرارداد...', 'redirect_url' => $edit_contract_url]);
    }
}