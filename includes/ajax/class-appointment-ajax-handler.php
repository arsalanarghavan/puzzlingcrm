<?php
/**
 * Appointment AJAX Handler
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

class PuzzlingCRM_Appointment_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_manage_appointment', [$this, 'manage_appointment']);
        add_action('wp_ajax_puzzling_get_appointments_calendar', [$this, 'get_appointments_calendar']);
        add_action('wp_ajax_puzzling_quick_create_appointment', [$this, 'quick_create_appointment']);
        add_action('wp_ajax_puzzling_delete_appointment', [$this, 'delete_appointment']);
        add_action('wp_ajax_puzzling_update_appointment_date', [$this, 'update_appointment_date']);
        add_action('wp_ajax_puzzling_get_customers_list', [$this, 'get_customers_list']);
        add_action('wp_ajax_puzzling_client_request_appointment', [$this, 'client_request_appointment']);
    }

    /**
     * Manage Appointment (existing - keeping compatibility)
     */
    public function manage_appointment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : 'pending';
        $notes = isset($_POST['notes']) ? wp_kses_post($_POST['notes']) : '';

        if (!$customer_id || !$title) {
            wp_send_json_error(['message' => 'لطفاً تمام فیلدهای ضروری را پر کنید.']);
        }

        // Convert Jalali to Gregorian if needed
        if (function_exists('jmktime')) {
            $date_parts = explode('/', $date);
            if (count($date_parts) === 3) {
                $timestamp = jmktime(0, 0, 0, $date_parts[1], $date_parts[2], $date_parts[0]);
                $date = date('Y-m-d', $timestamp);
            }
        }

        $datetime = $date . ' ' . $time;

        $post_data = [
            'post_type' => 'appointment',
            'post_title' => $title,
            'post_content' => $notes,
            'post_author' => $customer_id,
            'post_status' => 'publish'
        ];

        if ($appointment_id > 0) {
            $post_data['ID'] = $appointment_id;
            $result = wp_update_post($post_data);
            $message = 'قرار ملاقات بروزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data);
            $message = 'قرار ملاقات جدید ایجاد شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در ذخیره قرار ملاقات.']);
        }

        update_post_meta($result, '_appointment_datetime', $datetime);
        wp_set_post_terms($result, [$status], 'appointment_status');

        wp_send_json_success([
            'message' => $message,
            'reload' => true
        ]);
    }

    /**
     * Get Appointments for Calendar
     */
    public function get_appointments_calendar() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '';
        $end = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : '';
        
        $appointments = get_posts([
            'post_type' => 'appointment',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $events = [];
        
        foreach ($appointments as $appointment) {
            $datetime = get_post_meta($appointment->ID, '_appointment_datetime', true);
            $customer_id = $appointment->post_author;
            $customer = get_user_by('id', $customer_id);
            
            $status_terms = wp_get_post_terms($appointment->ID, 'appointment_status');
            $status = !empty($status_terms) ? $status_terms[0]->slug : 'pending';
            
            $colors = [
                'pending' => '#ffc107',
                'confirmed' => '#28a745',
                'cancelled' => '#dc3545',
                'completed' => '#17a2b8'
            ];
            
            $events[] = [
                'id' => $appointment->ID,
                'title' => $appointment->post_title,
                'start' => $datetime,
                'backgroundColor' => $colors[$status] ?? '#845adf',
                'borderColor' => $colors[$status] ?? '#845adf',
                'extendedProps' => [
                    'customer' => $customer ? $customer->display_name : 'نامشخص',
                    'status' => $status
                ]
            ];
        }
        
        wp_send_json_success(['events' => $events]);
    }

    /**
     * Quick Create Appointment
     */
    public function quick_create_appointment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '10:00';

        if (!$customer_id || !$title || !$date) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        $datetime = $date . ' ' . $time;

        $post_id = wp_insert_post([
            'post_type' => 'appointment',
            'post_title' => $title,
            'post_author' => $customer_id,
            'post_status' => 'publish'
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد قرار ملاقات.']);
        }

        update_post_meta($post_id, '_appointment_datetime', $datetime);
        wp_set_post_terms($post_id, ['pending'], 'appointment_status');

        wp_send_json_success([
            'message' => 'قرار ملاقات ایجاد شد.',
            'appointment_id' => $post_id
        ]);
    }

    /**
     * Delete Appointment
     */
    public function delete_appointment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        
        if ($appointment_id && wp_delete_post($appointment_id, true)) {
            wp_send_json_success(['message' => 'قرار ملاقات حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف.']);
        }
    }

    /**
     * Update Appointment Date
     */
    public function update_appointment_date() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        $new_date = isset($_POST['new_date']) ? sanitize_text_field($_POST['new_date']) : '';
        
        if ($appointment_id && $new_date) {
            update_post_meta($appointment_id, '_appointment_datetime', $new_date);
            wp_send_json_success(['message' => 'تاریخ به‌روزرسانی شد.']);
        } else {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }
    }

    /**
     * Get Customers List
     */
    public function get_customers_list() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $customers = get_users(['role__in' => ['customer', 'subscriber', 'client']]);
        
        $customer_list = [];
        foreach ($customers as $customer) {
            $customer_list[] = [
                'id' => $customer->ID,
                'name' => $customer->display_name
            ];
        }
        
        wp_send_json_success(['customers' => $customer_list]);
    }

    /**
     * Client Request Appointment
     */
    public function client_request_appointment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'لطفاً وارد شوید.']);
        }

        $current_user = wp_get_current_user();
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';
        $notes = isset($_POST['notes']) ? wp_kses_post($_POST['notes']) : '';

        if (!$title || !$date || !$time) {
            wp_send_json_error(['message' => 'لطفاً تمام فیلدها را پر کنید.']);
        }

        $datetime = $date . ' ' . $time;

        $post_id = wp_insert_post([
            'post_type' => 'appointment',
            'post_title' => $title,
            'post_content' => $notes,
            'post_author' => $current_user->ID,
            'post_status' => 'publish'
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => 'خطا در ثبت درخواست.']);
        }

        update_post_meta($post_id, '_appointment_datetime', $datetime);
        wp_set_post_terms($post_id, ['pending'], 'appointment_status');

        wp_send_json_success([
            'message' => 'درخواست شما ثبت شد و در اسرع وقت بررسی خواهد شد.',
            'reload' => true
        ]);
    }
}

