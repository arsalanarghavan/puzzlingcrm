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
        
        // ثبت اکشن جدید برای حذف قرارداد
        add_action('wp_ajax_puzzling_delete_contract', [$this, 'ajax_delete_contract']);
    }

    // --- تابع کمکی برای پاکسازی بافر خروجی و ارسال خطا ---
    private function clean_buffer_and_send_error($code, $extra_data = []) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        PuzzlingCRM_Error_Codes::send_error($code, $extra_data);
    }
    // -----------------------------------------------------------------

    public function ajax_manage_project() {
        ob_start(); // Output Buffering دفاعی
        
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $project_title = sanitize_text_field($_POST['project_title']);
        $project_content = wp_kses_post($_POST['project_content']);
        $project_status_id = intval($_POST['project_status']);

        if (empty($project_title) || empty($contract_id)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_PROJECT_DATA);
        }

        $contract = get_post($contract_id);
        if (!$contract) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CONTRACT_INVALID);
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
            $extra_info = ['wp_error_message' => $result->get_error_message()];
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PROJECT_SAVE_FAILED, $extra_info);
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
        
        PuzzlingCRM_Logger::add('پروژه مدیریت شد', [
            'action' => $project_id > 0 ? 'به‌روزرسانی' : 'ایجاد',
            'project_id' => $the_project_id,
            'contract_id' => $contract_id,
        ], 'success');

        ob_end_clean(); // پاکسازی نهایی بافر
        wp_send_json_success(['message' => $message, 'reload' => true]);
    }

    public function ajax_delete_project() {
        ob_start();
        if (!current_user_can('delete_posts')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!isset($_POST['project_id']) || !isset($_POST['nonce'])) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_PROJECT_DATA);
        }
        
        $project_id = intval($_POST['project_id']);
        if (!wp_verify_nonce($_POST['nonce'], 'puzzling_delete_project_' . $project_id)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED, ['specific_nonce' => 'failed']);
        }
        
        $project = get_post($project_id);
        if (!$project || $project->post_type !== 'project') {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PROJECT_NOT_FOUND, ['project_id' => $project_id]);
        }
        
        if (wp_delete_post($project_id, true)) {
            PuzzlingCRM_Logger::add('پروژه حذف شد', ['content' => "پروژه '{$project->post_title}' توسط " . wp_get_current_user()->display_name . " حذف شد.", 'type' => 'log']);
            ob_end_clean(); 
            wp_send_json_success(['message' => 'پروژه با موفقیت حذف شد.']);
        } else {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PROJECT_DELETE_FAILED, ['project_id' => $project_id]);
        }
    }

    public function ajax_manage_contract() {
        ob_start(); // Output Buffering دفاعی

        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $start_date_jalali = isset($_POST['_project_start_date']) ? sanitize_text_field($_POST['_project_start_date']) : '';
        $contract_title = isset($_POST['contract_title']) && !empty($_POST['contract_title']) ? sanitize_text_field($_POST['contract_title']) : '';
        
        // **NEW FIX: دریافت مبلغ کل و تعداد اقساط از POST و ذخیره آنها**
        $total_amount = isset($_POST['total_amount']) ? sanitize_text_field($_POST['total_amount']) : '';
        $total_installments = isset($_POST['total_installments']) ? intval($_POST['total_installments']) : 1;


        if (empty($customer_id) || empty($start_date_jalali)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_CONTRACT_DATA);
        }

        $customer_data = get_userdata($customer_id);
        if (!$customer_data) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_INVALID_CUSTOMER, ['customer_id' => $customer_id]);
        }
        
        $customer_display_name = $customer_data->display_name ?? 'مشتری نامشخص';
        if (empty($contract_title)) {
            $contract_title = 'قرارداد برای ' . $customer_display_name;
        }

        $start_date_gregorian = puzzling_jalali_to_gregorian($start_date_jalali);
        $start_timestamp = strtotime($start_date_gregorian);

        if (empty($start_date_gregorian) || $start_timestamp === false) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_INVALID_START_DATE, ['input_date' => $start_date_jalali]);
        }
        
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
            $extra_info = ['wp_error_message' => $result->get_error_message()];
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CONTRACT_SAVE_FAILED, $extra_info);
        }

        $the_contract_id = is_int($result) ? $result : $contract_id;

        // **ذخیره مبلغ کل و تعداد اقساط**
        update_post_meta($the_contract_id, '_total_amount', preg_replace('/[^\d]/', '', $total_amount));
        update_post_meta($the_contract_id, '_total_installments', $total_installments);


        if ($contract_id == 0 && $result) {
            $contract_number = 'puz-' . jdate('ymd', $start_timestamp, '', 'en') . '-' . $customer_id;
            update_post_meta($the_contract_id, '_contract_number', $contract_number);
        }

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
                    $jalali_date = sanitize_text_field($_POST['payment_due_date'][$i]);
                    $due_date_gregorian = puzzling_jalali_to_gregorian($jalali_date);
                    if (empty($due_date_gregorian) || strtotime($due_date_gregorian) === false) {
                        $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_INVALID_INSTALLMENT_DATE, ['input_date' => $jalali_date, 'installment_index' => $i + 1]);
                    }
                    $installments[] = [
                        // **NEW FIX: حذف کاراکترهای غیر عددی از مبلغ**
                        'amount' => preg_replace('/[^\d]/', '', sanitize_text_field($_POST['payment_amount'][$i])),
                        'due_date' => $due_date_gregorian,
                        'status' => sanitize_key($_POST['payment_status'][$i]),
                    ];
                }
            }
        }
        update_post_meta($the_contract_id, '_installments', $installments);
        
        PuzzlingCRM_Logger::add('قرارداد مدیریت شد', [
            'action' => $contract_id > 0 ? 'به‌روزرسانی' : 'ایجاد',
            'contract_id' => $the_contract_id,
            'customer_id' => $customer_id,
        ], 'success');
        
        ob_end_clean(); // پاکسازی نهایی بافر
        wp_send_json_success(['message' => $message, 'reload' => true]);
    }
    
    /**
     * تابع جدید برای حذف کامل قرارداد و موارد مرتبط
     */
    public function ajax_delete_contract() {
        ob_start();
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!isset($_POST['contract_id']) || !isset($_POST['nonce'])) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_CONTRACT_DATA);
        }
        
        $contract_id = intval($_POST['contract_id']);
        if (!wp_verify_nonce($_POST['nonce'], 'puzzling_delete_contract_' . $contract_id)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED, ['specific_nonce' => 'failed']);
        }
        
        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CONTRACT_NOT_FOUND, ['contract_id' => $contract_id]);
        }
        
        $related_items_query = new WP_Query([
            'post_type'      => ['project', 'pro_invoice'], 
            'posts_per_page' => -1,
            'meta_key'       => '_contract_id',
            'meta_value'     => $contract_id
        ]);

        if ($related_items_query->have_posts()) {
            foreach ($related_items_query->posts as $related_post) {
                wp_delete_post($related_post->ID, true); 
            }
        }
        
        if (wp_delete_post($contract_id, true)) {
            PuzzlingCRM_Logger::add('قرارداد حذف شد', ['content' => "قرارداد '{$contract->post_title}' و تمام موارد مرتبط با آن توسط " . wp_get_current_user()->display_name . " حذف شد.", 'type' => 'log']);
            ob_end_clean();
            // **FIX: این پاسخ موفقیت آمیز است و خرابی را رفع می‌کند**
            wp_send_json_success(['message' => 'قرارداد و تمام داده‌های مرتبط با آن با موفقیت حذف شدند.', 'reload' => true]);
        } else {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CONTRACT_DELETE_FAILED, ['contract_id' => $contract_id]);
        }
    }

    public function ajax_cancel_contract() {
        ob_start();
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : 'دلیلی ذکر نشده است.';

        if ($contract_id > 0 && get_post($contract_id) && get_post($contract_id)->post_type === 'contract') {
            update_post_meta($contract_id, '_contract_status', 'cancelled');
            update_post_meta($contract_id, '_cancellation_reason', $reason);
            update_post_meta($contract_id, '_cancellation_date', current_time('mysql'));
            
            PuzzlingCRM_Logger::add('قرارداد لغو شد', [
                'contract_id' => $contract_id,
                'reason' => $reason,
            ], 'warning');
            
            ob_end_clean();
            wp_send_json_success(['message' => 'قرارداد با موفقیت لغو شد.', 'reload' => true]);
        } else {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CANCEL_CONTRACT_FAILED, ['contract_id' => $contract_id]);
        }
    }

    public function ajax_add_project_to_contract() {
        ob_start();
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $project_title = isset($_POST['project_title']) ? sanitize_text_field($_POST['project_title']) : '';
        
        if (empty($contract_id) || empty($project_title)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_PROJECT_DATA);
        }

        $contract = get_post($contract_id);
        if (!$contract || $contract->post_type !== 'contract') {
             $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_CONTRACT_INVALID, ['contract_id' => $contract_id]);
        }

        $project_id = wp_insert_post([
            'post_title' => $project_title,
            'post_author' => $contract->post_author,
            'post_status' => 'publish',
            'post_type' => 'project'
        ], true);

        if (is_wp_error($project_id)) {
            $extra_info = ['wp_error_message' => $project_id->get_error_message()];
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PROJECT_SAVE_FAILED, $extra_info);
        }

        update_post_meta($project_id, '_contract_id', $contract_id);
        
        $active_status = get_term_by('slug', 'active', 'project_status');
        if ($active_status) {
            wp_set_object_terms($project_id, $active_status->term_id, 'project_status');
        }

        PuzzlingCRM_Logger::add('پروژه به قرارداد اضافه شد', [
            'project_id' => $project_id,
            'contract_id' => $contract_id,
        ], 'success');
        
        ob_end_clean();
        wp_send_json_success(['message' => 'پروژه جدید با موفقیت به قرارداد اضافه شد.', 'reload' => true]);
    }
    
    public function ajax_add_services_from_product() {
        ob_start();
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
        }

        $current_user = wp_get_current_user();
        PuzzlingCRM_Logger::add('افزودن خدمات از محصول', [
            'content' => 'درخواست افزودن خدمات از محصول توسط کاربر ' . $current_user->display_name . ' آغاز شد.',
            'type' => 'log'
        ]);

        if (!function_exists('wc_get_product')) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PRODUCT_WOC_INACTIVE);
        }

        $contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (empty($contract_id) || empty($product_id)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_MISSING_PRODUCT_DATA, ['contract_id' => $contract_id, 'product_id' => $product_id]);
        }

        $contract = get_post($contract_id);
        $product = wc_get_product($product_id);

        if (!$contract || !$product) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PRODUCT_NOT_FOUND, ['contract_found' => (bool)$contract, 'product_found' => (bool)$product]);
        }

        $created_projects_count = 0;
        $child_products = [];
        
        if ($product->is_type('grouped') || $product->is_type('bundle')) {
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
            $success_msg = $created_projects_count . ' پروژه از محصول "' . $product->get_name() . '" برای قرارداد شماره ' . $contract_id . ' ایجاد شد.';
            PuzzlingCRM_Logger::add('موفقیت در افزودن خدمات', ['content' => $success_msg, 'type' => 'log']);
            ob_end_clean();
            wp_send_json_success(['message' => $created_projects_count . ' پروژه با موفقیت از محصول ایجاد و به این قرارداد متصل شد.', 'reload' => true]);
        } else {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_PRODUCT_PROJECT_FAILED, ['product_id' => $product_id, 'contract_id' => $contract_id]);
        }
    }

    public function ajax_get_projects_for_customer() {
        ob_start();
        if (!check_ajax_referer('puzzlingcrm-ajax-nonce', 'security', false)) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_NONCE_FAILED);
        }
        if (!current_user_can('manage_options') || !isset($_POST['customer_id'])) {
            $this->clean_buffer_and_send_error(PuzzlingCRM_Error_Codes::PRJ_ERR_ACCESS_DENIED);
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
        
        ob_end_clean();
        wp_send_json_success($projects_data);
    }
}