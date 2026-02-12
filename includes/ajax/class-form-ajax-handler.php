<?php
/**
 * PuzzlingCRM Form, Invoice & Appointment AJAX Handler
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Form_Ajax_Handler {

    public function __construct() {
        // --- Form & Invoice Actions ---
        add_action('wp_ajax_puzzling_manage_pro_invoice', [$this, 'ajax_manage_pro_invoice']);
        add_action('wp_ajax_puzzling_generate_pro_invoice_pdf', [$this, 'ajax_generate_pro_invoice_pdf']);
        add_action('wp_ajax_puzzlingcrm_get_invoices', [$this, 'ajax_get_invoices']);
        add_action('wp_ajax_puzzlingcrm_get_invoice', [$this, 'ajax_get_invoice']);

        // --- Appointment Actions ---
        add_action('wp_ajax_puzzling_manage_appointment', [$this, 'ajax_manage_appointment']);
        add_action('wp_ajax_puzzling_delete_appointment', [$this, 'ajax_delete_appointment']);
        add_action('wp_ajax_puzzling_client_request_appointment', [$this, 'ajax_client_request_appointment']);
    }

    private function notify_all_admins($title, $args) {
        $admins = get_users(['role__in' => ['administrator', 'system_manager'], 'fields' => 'ID']);
        foreach ($admins as $admin_id) {
            $notification_args = array_merge($args, ['user_id' => $admin_id]);
            PuzzlingCRM_Logger::add($title, $notification_args);
        }
    }

    public function ajax_manage_pro_invoice() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }
        
        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
        $customer_id = intval($_POST['customer_id']);
        $project_id = intval($_POST['project_id']);
        $issue_date_jalali = sanitize_text_field($_POST['issue_date']);
        $payment_method = sanitize_textarea_field($_POST['payment_method']);
        $notes = wp_kses_post($_POST['notes']);

        if (empty($customer_id) || empty($project_id) || empty($issue_date_jalali)) {
            wp_send_json_error(['message' => 'انتخاب مشتری، پروژه و تاریخ صدور الزامی است.']);
        }

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', trim($issue_date_jalali))) {
            $issue_date_gregorian = trim($issue_date_jalali);
        } else {
            $issue_date_gregorian = puzzling_jalali_to_gregorian($issue_date_jalali);
        }
        $invoice_number = 'puz-' . jdate('ymd', strtotime($issue_date_gregorian), '', 'en') . '-' . $project_id;
        
        $items = [];
        $subtotal = 0;
        $total_discount = 0;
        if (isset($_POST['item_title']) && is_array($_POST['item_title'])) {
            for ($i = 0; $i < count($_POST['item_title']); $i++) {
                if (!empty($_POST['item_title'][$i])) {
                    $price = (float) str_replace(',', '', $_POST['item_price'][$i]);
                    $discount = (float) str_replace(',', '', $_POST['item_discount'][$i]);
                    $items[] = [
                        'title' => sanitize_text_field($_POST['item_title'][$i]),
                        'desc' => sanitize_text_field($_POST['item_desc'][$i]),
                        'price' => $price,
                        'discount' => $discount,
                    ];
                    $subtotal += $price;
                    $total_discount += $discount;
                }
            }
        }
        $final_total = $subtotal - $total_discount;

        $post_data = [
            'post_title'    => 'پیش‌فاکتور ' . $invoice_number,
            'post_content'  => $notes,
            'post_author'   => $customer_id,
            'post_status'   => 'publish',
            'post_type'     => 'pzl_pro_invoice',
        ];

        if ($invoice_id > 0) {
            $post_data['ID'] = $invoice_id;
            $result = wp_update_post($post_data, true);
            $message = 'پیش‌فاکتور با موفقیت به‌روزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data, true);
            $message = 'پیش‌فاکتور با موفقیت ایجاد شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در ذخیره پیش‌فاکتور.']);
        }

        $the_invoice_id = is_int($result) ? $result : $invoice_id;

        update_post_meta($the_invoice_id, '_pro_invoice_number', $invoice_number);
        update_post_meta($the_invoice_id, '_project_id', $project_id);
        update_post_meta($the_invoice_id, '_issue_date', $issue_date_gregorian);
        update_post_meta($the_invoice_id, '_invoice_items', $items);
        update_post_meta($the_invoice_id, '_subtotal', $subtotal);
        update_post_meta($the_invoice_id, '_total_discount', $total_discount);
        update_post_meta($the_invoice_id, '_final_total', $final_total);
        update_post_meta($the_invoice_id, '_payment_method', $payment_method);
        
        wp_send_json_success(['message' => $message, 'reload' => true]);
    }
    
    public function ajax_get_invoices() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;
        $query = new WP_Query([
            'post_type' => 'pzl_pro_invoice',
            'posts_per_page' => 20,
            'paged' => $paged,
        ]);
        $items = [];
        foreach ($query->posts as $post) {
            $inv_id = $post->ID;
            $customer = get_userdata((int) $post->post_author);
            $project_id = get_post_meta($inv_id, '_project_id', true);
            $items[] = [
                'id' => $inv_id,
                'invoice_number' => get_post_meta($inv_id, '_pro_invoice_number', true),
                'customer_id' => (int) $post->post_author,
                'customer_name' => $customer && $customer->exists() ? $customer->display_name : '---',
                'project_id' => (int) $project_id,
                'project_title' => $project_id ? get_the_title($project_id) : '---',
                'final_total' => (float) get_post_meta($inv_id, '_final_total', true),
                'issue_date' => get_post_meta($inv_id, '_issue_date', true),
                'date_display' => get_the_date('Y/m/d', $post),
            ];
        }
        $customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
        $customers_list = array_map(function ($c) {
            return ['id' => $c->ID, 'display_name' => $c->display_name];
        }, $customers);
        wp_send_json_success([
            'invoices' => $items,
            'customers' => $customers_list,
            'total_pages' => $query->max_num_pages,
            'current_page' => $paged,
        ]);
    }

    public function ajax_get_invoice() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
        $post = get_post($invoice_id);
        if (!$post || $post->post_type !== 'pzl_pro_invoice') {
            wp_send_json_error(['message' => 'پیش‌فاکتور یافت نشد.']);
        }
        $issue_date = get_post_meta($invoice_id, '_issue_date', true);
        $issue_jalali = $issue_date && function_exists('puzzling_gregorian_to_jalali')
            ? puzzling_gregorian_to_jalali($issue_date) : $issue_date;
        $items = get_post_meta($invoice_id, '_invoice_items', true);
        if (!is_array($items)) $items = [];
        $customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
        $customers_list = array_map(function ($c) {
            return ['id' => $c->ID, 'display_name' => $c->display_name];
        }, $customers);
        $projects = get_posts(['post_type' => 'project', 'author' => $post->post_author, 'posts_per_page' => -1]);
        $projects_list = array_map(function ($p) {
            return ['id' => $p->ID, 'title' => $p->post_title];
        }, $projects);
        wp_send_json_success([
            'invoice' => [
                'id' => $post->ID,
                'customer_id' => (int) $post->post_author,
                'project_id' => (int) get_post_meta($invoice_id, '_project_id', true),
                'invoice_number' => get_post_meta($invoice_id, '_pro_invoice_number', true),
                'issue_date' => $issue_date,
                'issue_date_jalali' => $issue_jalali,
                'items' => $items,
                'payment_method' => get_post_meta($invoice_id, '_payment_method', true),
                'notes' => $post->post_content,
            ],
            'customers' => $customers_list,
            'projects' => $projects_list,
        ]);
    }

    public function ajax_generate_pro_invoice_pdf() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }
        
        wp_send_json_error(['message' => 'قابلیت تولید PDF هنوز پیاده‌سازی نشده است.']);
    }
    
    public function ajax_manage_appointment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        $customer_id = intval($_POST['customer_id']);
        $title = sanitize_text_field($_POST['title']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $notes = wp_kses_post($_POST['notes']);
        $status_slug = sanitize_key($_POST['status']);
        
        if (empty($customer_id) || empty($title) || empty($date) || empty($time)) {
            wp_send_json_error(['message' => 'تمام فیلدهای ستاره‌دار الزامی هستند.']);
        }

        $datetime = puzzling_jalali_to_gregorian($date) . ' ' . $time;

        $post_data = [
            'post_title' => $title,
            'post_content' => $notes,
            'post_author' => $customer_id,
            'post_status' => 'publish',
            'post_type' => 'pzl_appointment',
        ];

        if ($appointment_id > 0) {
            $post_data['ID'] = $appointment_id;
            $result = wp_update_post($post_data, true);
            $message = 'قرار ملاقات با موفقیت به‌روزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data, true);
            $message = 'قرار ملاقات جدید با موفقیت ثبت شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در پردازش قرار ملاقات.']);
        }

        $the_appointment_id = is_int($result) ? $result : $appointment_id;
        
        update_post_meta($the_appointment_id, '_appointment_datetime', $datetime);
        wp_set_object_terms($the_appointment_id, $status_slug, 'appointment_status');

        wp_send_json_success(['message' => $message, 'reload' => true]);
    }

    public function ajax_delete_appointment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options') || !isset($_POST['appointment_id'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $appointment_id = intval($_POST['appointment_id']);
        if (wp_delete_post($appointment_id, true)) {
            wp_send_json_success(['message' => 'قرار ملاقات با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف قرار ملاقات.']);
        }
    }
    
    public function ajax_client_request_appointment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'برای ثبت درخواست باید وارد شوید.']);
        }

        $title = sanitize_text_field($_POST['title']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $notes = wp_kses_post($_POST['notes']);

        if (empty($title) || empty($date) || empty($time)) {
            wp_send_json_error(['message' => 'موضوع، تاریخ و ساعت الزامی هستند.']);
        }

        $datetime = puzzling_jalali_to_gregorian($date) . ' ' . $time;
        
        $post_data = [
            'post_title'   => $title,
            'post_content' => $notes,
            'post_author'  => get_current_user_id(),
            'post_status'  => 'publish',
            'post_type'    => 'pzl_appointment',
        ];

        $appointment_id = wp_insert_post($post_data, true);

        if (is_wp_error($appointment_id)) {
            wp_send_json_error(['message' => 'خطا در ثبت درخواست شما.']);
        }

        update_post_meta($appointment_id, '_appointment_datetime', $datetime);
        wp_set_object_terms($appointment_id, 'pending', 'appointment_status');
        
        $this->notify_all_admins('درخواست قرار ملاقات جدید', [
            'content' => sprintf('یک درخواست قرار ملاقات جدید با موضوع "%s" توسط مشتری ثبت شد.', $title),
            'type' => 'notification',
            'object_id' => $appointment_id,
        ]);

        wp_send_json_success(['message' => 'درخواست شما با موفقیت ثبت شد. نتیجه از طریق داشبورد به شما اطلاع داده خواهد شد.', 'reload' => true]);
    }
}