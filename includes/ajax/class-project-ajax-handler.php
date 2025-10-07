<?php
/**
 * PuzzlingCRM Project & Contract AJAX Handler
 *
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Project_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_manage_project', [$this, 'ajax_manage_project']);
        add_action('wp_ajax_puzzling_delete_project', [$this, 'ajax_delete_project']);
        add_action('wp_ajax_puzzling_manage_contract', [$this, 'ajax_manage_contract']);
        add_action('wp_ajax_puzzling_cancel_contract', [$this, 'ajax_cancel_contract']);
        add_action('wp_ajax_puzzling_add_project_to_contract', [$this, 'ajax_add_project_to_contract']);
        add_action('wp_ajax_puzzling_add_services_from_product', [$this, 'ajax_add_services_from_product']);
        add_action('wp_ajax_puzzling_get_projects_for_customer', [$this, 'ajax_get_projects_for_customer']);
    }

    public function ajax_manage_project() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $project_title = sanitize_text_field($_POST['project_title']);
        $project_content = wp_kses_post($_POST['project_content']);
        $project_status_id = intval($_POST['project_status']);

        if (empty($project_title) || empty($contract_id)) {
            wp_send_json_error(['message' => 'عنوان پروژه و انتخاب قرارداد الزامی است.']);
        }

        $contract = get_post($contract_id);
        if (!$contract) {
            wp_send_json_error(['message' => 'قرارداد انتخاب شده معتبر نیست.']);
        }
        $customer_id = $contract->post_author;

        $post_data = [
            'post_title' => $project_title,
            'post_content' => $project_content,
            'post_author' => $customer_id,
            'post_status' => 'publish',
            'post_type' => 'project',
        ];

        if ($project_id > 0) {
            $post_data['ID'] = $project_id;
            $result = wp_update_post($post_data, true);
            $message = 'پروژه با موفقیت به‌روزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data, true);
            $message = 'پروژه جدید با موفقیت ایجاد شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در پردازش پروژه.']);
        }

        $the_project_id = is_int($result) ? $result : $project_id;
        
        update_post_meta($the_project_id, '_contract_id', $contract_id);
        wp_set_object_terms($the_project_id, $project_status_id, 'project_status');
        
        if (isset($_FILES['project_logo']) && $_FILES['project_logo']['error'] == 0) {
            $attachment_id = media_handle_upload('project_logo', $the_project_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($the_project_id, $attachment_id);
            }
        }
        
        wp_send_json_success(['message' => $message, 'reload' => true]);
    }

    public function ajax_delete_project() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('delete_posts') || !isset($_POST['project_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        $project_id = intval($_POST['project_id']);
        if (!wp_verify_nonce($_POST['nonce'], 'puzzling_delete_project_' . $project_id)) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }
        
        $project = get_post($project_id);
        if (!$project || $project->post_type !== 'project') {
            wp_send_json_error(['message' => 'پروژه یافت نشد.']);
        }
        
        if (wp_delete_post($project_id, true)) {
            PuzzlingCRM_Logger::add('پروژه حذف شد', ['content' => "پروژه '{$project->post_title}' توسط " . wp_get_current_user()->display_name . " حذف شد.", 'type' => 'log']);
            wp_send_json_success(['message' => 'پروژه با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف پروژه.']);
        }
    }

    public function ajax_manage_contract() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم برای مدیریت قراردادها را ندارید.']);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $start_date_jalali = isset($_POST['_project_start_date']) ? sanitize_text_field($_POST['_project_start_date']) : '';
        $contract_title = isset($_POST['contract_title']) && !empty($_POST['contract_title']) ? sanitize_text_field($_POST['contract_title']) : '';

        if (empty($customer_id) || empty($start_date_jalali)) {
            wp_send_json_error(['message' => 'انتخاب مشتری و تاریخ شروع الزامی است.']);
        }

        $customer_data = get_userdata($customer_id);
        if (!$customer_data) {
            wp_send_json_error(['message' => 'مشتری انتخاب شده معتبر نیست.']);
            return; // Exit
        }

        if (empty($contract_title)) {
            $contract_title = 'قرارداد برای ' . $customer_data->display_name;
        }

        $start_date_gregorian = puzzling_jalali_to_gregorian($start_date_jalali);
        $start_timestamp = strtotime($start_date_gregorian);

        if (empty($start_date_gregorian) || $start_timestamp === false) {
            wp_send_json_error(['message' => 'فرمت تاریخ شروع قرارداد نامعتبر است. لطفاً از فرمت صحیح (مثال: 1403/05/10) استفاده کنید.']);
            return;
        }

        $contract_number = 'puz-' . jdate('ymd', $start_timestamp, '', 'en') . '-' . $customer_id;
        
        $post_data = [
            'post_title' => $contract_title,
            'post_author' => $customer_id,
            'post_status' => 'publish',
            'post_type' => 'contract',
        ];

        if ($contract_id > 0) {
            $post_data['ID'] = $contract_id;
            $result = wp_update_post($post_data, true);
            $message = 'قرارداد با موفقیت به‌روزرسانی شد.';
        } else {
            $result = wp_insert_post($post_data, true);
            $message = 'قرارداد جدید با موفقیت ایجاد شد.';
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در ذخیره‌سازی قرارداد.']);
        }

        $the_contract_id = is_int($result) ? $result : $contract_id;

        update_post_meta($the_contract_id, '_contract_number', $contract_number);
        update_post_meta($the_contract_id, '_project_start_date', $start_date_gregorian);
        
        $duration = isset($_POST['_project_contract_duration']) ? sanitize_key($_POST['_project_contract_duration']) : '1-month';
        update_post_meta($the_contract_id, '_project_contract_duration', $duration);
        $end_date = date('Y-m-d', strtotime($start_date_gregorian . ' +' . str_replace('-', ' ', $duration)));
        update_post_meta($the_contract_id, '_project_end_date', $end_date);
        update_post_meta($the_contract_id, '_project_subscription_model', sanitize_key($_POST['_project_subscription_model']));


        $installments = [];
        if (isset($_POST['payment_amount']) && is_array($_POST['payment_amount'])) {
            for ($i = 0; $i < count($_POST['payment_amount']); $i++) {
                if (!empty($_POST['payment_amount'][$i])) {
                    $due_date_gregorian = puzzling_jalali_to_gregorian(sanitize_text_field($_POST['payment_due_date'][$i]));
                    if (empty($due_date_gregorian) || strtotime($due_date_gregorian) === false) {
                        wp_send_json_error(['message' => 'فرمت تاریخ قسط شماره ' . ($i + 1) . ' نامعتبر است.']);
                        return;
                    }
                    $installments[] = [
                        'amount' => sanitize_text_field($_POST['payment_amount'][$i]),
                        'due_date' => $due_date_gregorian,
                        'status' => sanitize_key($_POST['payment_status'][$i]),
                    ];
                }
            }
        }
        update_post_meta($the_contract_id, '_installments', $installments);

        wp_send_json_success(['message' => $message, 'reload' => true]);
    }

    public function ajax_cancel_contract() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : 'دلیلی ذکر نشده است.';

        if ($contract_id > 0) {
            update_post_meta($contract_id, '_contract_status', 'cancelled');
            update_post_meta($contract_id, '_cancellation_reason', $reason);
            update_post_meta($contract_id, '_cancellation_date', current_time('mysql'));
            
            wp_send_json_success(['message' => 'قرارداد با موفقیت لغو شد.', 'reload' => true]);
        } else {
            wp_send_json_error(['message' => 'شناسه قرارداد نامعتبر است.']);
        }
    }

    public function ajax_add_project_to_contract() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $project_title = isset($_POST['project_title']) ? sanitize_text_field($_POST['project_title']) : '';
        
        if (empty($contract_id) || empty($project_title)) {
            wp_send_json_error(['message' => 'اطلاعات قرارداد یا عنوان پروژه ناقص است.']);
        }

        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
             wp_send_json_error(['message' => 'قرارداد معتبر نیست.']);
        }

        $project_id = wp_insert_post([
            'post_title' => $project_title,
            'post_author' => $contract->post_author,
            'post_status' => 'publish',
            'post_type' => 'project'
        ], true);

        if (is_wp_error($project_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد پروژه جدید.']);
        }

        update_post_meta($project_id, '_contract_id', $contract_id);
        
        $active_status = get_term_by('slug', 'active', 'project_status');
        if ($active_status) {
            wp_set_object_terms($project_id, $active_status->term_id, 'project_status');
        }

        wp_send_json_success(['message' => 'پروژه جدید با موفقیت به قرارداد اضافه شد.', 'reload' => true]);
    }
    
    public function ajax_add_services_from_product() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (empty($contract_id) || empty($product_id)) {
            wp_send_json_error(['message' => 'اطلاعات قرارداد یا محصول ناقص است.']);
        }

        $contract = get_post($contract_id);
        $product = wc_get_product($product_id);

        if (!$contract || !$product) {
            wp_send_json_error(['message' => 'قرارداد یا محصول یافت نشد.']);
        }

        $created_projects_count = 0;
        $child_products = [];
        
        if ($product->is_type('grouped')) {
            $child_products = $product->get_children();
        } else {
            $child_products[] = $product->get_id();
        }
        
        foreach($child_products as $child_product_id) {
            $child_product = wc_get_product($child_product_id);
            if (!$child_product) continue;
            
            $project_id = wp_insert_post([
                'post_title' => $child_product->get_name(),
                'post_author' => $contract->post_author,
                'post_status' => 'publish',
                'post_type' => 'project'
            ], true);
            
            if (!is_wp_error($project_id)) {
                update_post_meta($project_id, '_contract_id', $contract_id);
                $active_status = get_term_by('slug', 'active', 'project_status');
                if ($active_status) {
                    wp_set_object_terms($project_id, $active_status->term_id, 'project_status');
                }
                $created_projects_count++;
            }
        }

        if($created_projects_count > 0) {
            wp_send_json_success(['message' => $created_projects_count . ' پروژه با موفقیت از محصول ایجاد و به این قرارداد متصل شد.', 'reload' => true]);
        } else {
            wp_send_json_error(['message' => 'هیچ پروژه‌ای از محصول انتخاب شده ایجاد نشد. ممکن است محصول فاقد خدمات قابل تبدیل به پروژه باشد.']);
        }
    }

    public function ajax_get_projects_for_customer() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options') || !isset($_POST['customer_id'])) {
            wp_send_json_error();
        }

        $customer_id = intval($_POST['customer_id']);
        $projects_posts = get_posts(['post_type' => 'project', 'author' => $customer_id, 'posts_per_page' => -1]);
        
        $projects_data = [];
        if ($projects_posts) {
            foreach ($projects_posts as $project) {
                $projects_data[] = [
                    'id'    => $project->ID,
                    'title' => $project->post_title,
                ];
            }
        }
        
        wp_send_json_success($projects_data);
    }
}