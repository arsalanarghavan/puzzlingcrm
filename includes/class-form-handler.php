<?php
/**
 * PuzzlingCRM Form Handler (SECURED & FULLY IMPLEMENTED)
 *
 * This class is the central router for handling all GET and POST requests
 * for the plugin, including form submissions, payment processing, and deletions.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Form_Handler {

    /**
     * Constructor. Hooks the main router and the ticket reply handler.
     */
    public function __construct() {
        add_action( 'init', [ $this, 'router' ] );
        add_action( 'admin_post_puzzling_ticket_reply', [ $this, 'handle_ticket_reply_form' ] );
    }
    
    /**
     * Helper function to send a notification to all system administrators.
     */
    private function notify_all_admins($title, $args) {
        $admins = get_users([
            'role__in' => ['administrator', 'system_manager'],
            'fields' => 'ID',
        ]);

        foreach ($admins as $admin_id) {
            // Ensure the 'user_id' in args is set to the admin's ID for each notification
            $notification_args = array_merge($args, ['user_id' => $admin_id]);
            PuzzlingCRM_Logger::add($title, $notification_args);
        }
    }

    /**
     * Main router to direct form submissions and GET actions to the correct handler.
     */
    public function router() {
        if (isset($_GET['puzzling_action'])) {
             $action = sanitize_key($_GET['puzzling_action']);
             switch ($action) {
                 case 'pay_installment':
                    $this->handle_payment_request();
                    return;
                 case 'verify_payment':
                    $this->handle_payment_verification();
                    return;
             }
        }

        if ( ! isset($_POST['puzzling_action']) || ! isset($_POST['_wpnonce']) ) {
            return;
        }

        $action = sanitize_key($_POST['puzzling_action']);
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        
        $nonce_action = 'puzzling_' . $action;
        if (in_array($action, ['edit_contract', 'delete_appointment', 'delete_project', 'manage_appointment'])) {
             $nonce_action .= '_' . $item_id;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], $nonce_action)) {
            $this->redirect_with_notice('security_failed');
        }

        if (!is_user_logged_in()) {
            $this->redirect_with_notice('permission_denied');
        }

        $manager_actions = [
            'manage_user', 'manage_project', 'delete_project', 'create_contract', 'edit_contract',
            'save_settings', 'manage_appointment', 'delete_appointment'
        ];

        if (in_array($action, $manager_actions)) {
            if (current_user_can('manage_options')) {
                $handler_method = 'handle_' . $action;
                if (method_exists($this, $handler_method)) {
                    $this->$handler_method();
                }
            } else {
                $this->redirect_with_notice('permission_denied');
            }
        } elseif ($action === 'new_ticket') {
            $this->handle_new_ticket_form();
        }
    }

    private function redirect_with_notice($notice_key, $base_url = '') {
        $url = empty($base_url) ? wp_get_referer() : $base_url;
        if (!$url) {
            $url = puzzling_get_dashboard_url();
        }
        $url = remove_query_arg(['puzzling_action', '_wpnonce', 'action', 'user_id', 'plan_id', 'sub_id', 'appt_id', 'project_id', 'contract_id', 'puzzling_notice', 'item_id'], $url);
        wp_redirect( add_query_arg('puzzling_notice', $notice_key, $url) );
        exit;
    }

    private function handle_manage_user() {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $email = sanitize_email($_POST['email']);
        $display_name = sanitize_text_field($_POST['display_name']);
        $password = $_POST['password'];
        $role = sanitize_key($_POST['role']);

        if (!is_email($email) || empty($display_name) || empty($role)) {
            $this->redirect_with_notice('user_error_data_invalid');
        }
        if ($user_id === 0 && empty($password)) {
            $this->redirect_with_notice('user_error_password_required');
        }

        $user_data = ['user_email' => $email, 'display_name' => $display_name, 'role' => $role];

        if (!empty($password)) {
            $user_data['user_pass'] = $password;
        }

        if ($user_id > 0) {
            $user_data['ID'] = $user_id;
            $result = wp_update_user($user_data);
            $notice = 'user_updated_success';
        } else {
            if (email_exists($email)) {
                $this->redirect_with_notice('user_error_email_exists');
            }
            $result = wp_insert_user($user_data);
            $notice = 'user_created_success';
        }

        if (is_wp_error($result)) {
            $this->redirect_with_notice('user_error_failed');
        } else {
            $this->redirect_with_notice($notice);
        }
    }

    private function handle_manage_project() {
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $project_title = sanitize_text_field($_POST['project_title']);
        $project_content = wp_kses_post($_POST['project_content']);
        $customer_id = intval($_POST['customer_id']);

        if(empty($project_title) || empty($customer_id)) {
            $this->redirect_with_notice('project_error_data_invalid');
        }

        $post_data = [
            'post_title'    => $project_title,
            'post_content'  => $project_content,
            'post_author'   => $customer_id,
            'post_status'   => 'publish',
            'post_type'     => 'project',
        ];

        if ($project_id > 0) {
            $post_data['ID'] = $project_id;
            $result = wp_update_post($post_data, true);
            $notice = 'project_updated_success';
        } else {
            $result = wp_insert_post($post_data, true);
            $notice = 'project_created_success';
            if (!is_wp_error($result)) {
                 PuzzlingCRM_Logger::add(
                    sprintf(__('پروژه جدید "%s" برای شما ثبت شد', 'puzzlingcrm'), $project_title),
                    [
                        'content' => __('برای مشاهده جزئیات به پنل کاربری خود مراجعه کنید.', 'puzzlingcrm'),
                        'type' => 'notification',
                        'user_id' => $customer_id,
                        'object_id' => $result
                    ]
                );
            }
        }

        if (is_wp_error($result)) {
            $this->redirect_with_notice('project_error_failed');
        } else {
            $this->redirect_with_notice($notice);
        }
    }

    private function handle_delete_project() {
        $project_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if ($project_id > 0) {
            $result = wp_delete_post($project_id, true);
            if ($result) {
                $this->redirect_with_notice('project_deleted_success');
            }
        }
        $this->redirect_with_notice('project_error_failed');
    }

    private function handle_create_contract() {
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $payment_amounts = isset($_POST['payment_amount']) ? (array) $_POST['payment_amount'] : [];
        $payment_due_dates = isset($_POST['payment_due_date']) ? (array) $_POST['payment_due_date'] : [];

        if ( empty($project_id) || empty($payment_amounts) || count($payment_amounts) !== count($payment_due_dates) ) {
            $this->redirect_with_notice('contract_error_data_invalid');
        }
        
        $project = get_post($project_id);
        if(!$project || $project->post_type !== 'project') {
            $this->redirect_with_notice('contract_error_project_not_found');
        }

        $installments = [];
        for ($i = 0; $i < count($payment_amounts); $i++) {
            if ( !empty($payment_amounts[$i]) && !empty($payment_due_dates[$i]) ) {
                 $installments[] = [ 
                     'amount'   => sanitize_text_field(str_replace(',', '', $payment_amounts[$i])),
                     'due_date' => sanitize_text_field($payment_due_dates[$i]), 
                     'status'   => 'pending', 
                     'ref_id'   => '' 
                    ];
            }
        }
        
        if(empty($installments)) {
            $this->redirect_with_notice('contract_error_no_installments');
        }

        $contract_id = wp_insert_post([
            'post_title' => sprintf(__('قرارداد پروژه: %s', 'puzzlingcrm'), get_the_title($project_id)),
            'post_type' => 'contract',
            'post_status' => 'publish',
            'post_author' => $project->post_author
        ]);

        if ( ! is_wp_error($contract_id) ) {
            update_post_meta($contract_id, '_project_id', $project_id);
            update_post_meta($contract_id, '_installments', $installments);
            PuzzlingCRM_Logger::add( __('قرارداد جدید ثبت شد', 'puzzlingcrm'), ['content' => sprintf(__("یک قرارداد جدید برای پروژه '%s' ایجاد شد.", 'puzzlingcrm'), get_the_title($project_id)), 'type' => 'log', 'object_id' => $contract_id]);
            $this->redirect_with_notice('contract_created_success');
        } else {
            $this->redirect_with_notice('contract_error_creation_failed');
        }
    }

    private function handle_edit_contract() {
        $contract_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $payment_amounts = isset($_POST['payment_amount']) ? (array) $_POST['payment_amount'] : [];
        $payment_due_dates = isset($_POST['payment_due_date']) ? (array) $_POST['payment_due_date'] : [];
        $payment_statuses = isset($_POST['payment_status']) ? (array) $_POST['payment_status'] : [];

        if ( empty($contract_id) || empty($payment_amounts) || count($payment_amounts) !== count($payment_due_dates) ) {
            $this->redirect_with_notice('contract_error_data_invalid');
        }
        
        $old_installments = get_post_meta($contract_id, '_installments', true);

        $installments = [];
        for ($i = 0; $i < count($payment_amounts); $i++) {
            if ( !empty($payment_amounts[$i]) && !empty($payment_due_dates[$i]) ) {
                 $installments[] = [
                    'amount'   => sanitize_text_field(str_replace(',', '', $payment_amounts[$i])),
                    'due_date' => sanitize_text_field($payment_due_dates[$i]),
                    'status'   => sanitize_key($payment_statuses[$i] ?? 'pending'),
                    'ref_id'   => $old_installments[$i]['ref_id'] ?? ''
                ];
            }
        }
        
        if(empty($installments)){
             $this->redirect_with_notice('contract_error_no_installments');
        }
        
        update_post_meta($contract_id, '_installments', $installments);
        
        PuzzlingCRM_Logger::add(__('قرارداد به‌روزرسانی شد', 'puzzlingcrm'), ['content' => sprintf(__("قرارداد با شناسه %d به‌روزرسانی شد.", 'puzzlingcrm'), $contract_id), 'type' => 'log', 'object_id' => $contract_id]);
        $this->redirect_with_notice('contract_updated_success');
    }

    private function handle_save_settings() {
        if ( isset($_POST['puzzling_settings']) && is_array($_POST['puzzling_settings']) ) {
            $current_settings = PuzzlingCRM_Settings_Handler::get_all_settings();
            $new_settings = $_POST['puzzling_settings'];
            
            $sanitized_settings = [];
            foreach ($new_settings as $key => $value) {
                $key = sanitize_key($key);
                if (is_array($value)) {
                     $sanitized_settings[$key] = array_map('sanitize_text_field', $value);
                } else if (strpos($key, 'msg') !== false) {
                    $sanitized_settings[$key] = sanitize_textarea_field($value);
                }
                else {
                    $sanitized_settings[$key] = sanitize_text_field($value);
                }
            }

            $updated_settings = array_merge($current_settings, $sanitized_settings);
            PuzzlingCRM_Settings_Handler::update_settings($updated_settings);
        }
        $this->redirect_with_notice('settings_saved');
    }

    private function handle_manage_appointment() {
        $appt_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $customer_id = intval($_POST['customer_id']);
        $title = sanitize_text_field($_POST['title']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        if (empty($customer_id) || empty($title) || empty($date) || empty($time)) {
             $this->redirect_with_notice('appt_error_data_invalid');
        }

        $full_datetime = $date . ' ' . $time;
        $post_data = [
            'post_title' => $title, 
            'post_content' => $notes, 
            'post_type' => 'pzl_appointment', 
            'post_status' => 'publish', 
            'post_author' => $customer_id,
        ];

        if ($appt_id > 0) {
            $post_data['ID'] = $appt_id;
            $result = wp_update_post($post_data, true);
            $notice = 'appt_updated_success';
        } else {
            $result = wp_insert_post($post_data, true);
            $notice = 'appt_created_success';
        }

        if (!is_wp_error($result)) {
            $post_id = is_int($result) ? $result : $appt_id;
            update_post_meta($post_id, '_appointment_datetime', $full_datetime);
            $this->redirect_with_notice($notice);
        } else {
            $this->redirect_with_notice('appt_error_failed');
        }
    }

    private function handle_delete_appointment() {
        $appt_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        if ($appt_id > 0) {
            wp_delete_post($appt_id, true);
            $this->redirect_with_notice('appt_deleted_success');
        }
        $this->redirect_with_notice('appt_error_failed');
    }
    
    private function handle_new_ticket_form() {
        $title = sanitize_text_field( $_POST['ticket_title'] );
        $content = wp_kses_post( $_POST['ticket_content'] );

        if ( empty($title) || empty($content) ) {
            $this->redirect_with_notice('ticket_error_empty');
        }

        $ticket_id = wp_insert_post([
            'post_title' => $title, 
            'post_content' => $content, 
            'post_status' => 'publish', 
            'post_author' => get_current_user_id(), 
            'post_type' => 'ticket'
        ]);

        if ( ! is_wp_error( $ticket_id ) ) {
            wp_set_object_terms( $ticket_id, 'open', 'ticket_status' );
            $this->notify_all_admins(
                __('تیکت پشتیبانی جدید', 'puzzlingcrm'), 
                ['content' => sprintf(__("یک تیکت جدید با موضوع '%s' توسط کاربر %s ارسال شد.", 'puzzlingcrm'), $title, wp_get_current_user()->display_name), 'type' => 'notification', 'object_id' => $ticket_id]
            );
            $this->redirect_with_notice('ticket_created_success');
        } else {
            $this->redirect_with_notice('ticket_error_failed');
        }
    }

    public function handle_ticket_reply_form() {
        if ( ! is_user_logged_in() || ! isset( $_POST['_wpnonce_ticket_reply'] ) || ! wp_verify_nonce( $_POST['_wpnonce_ticket_reply'], 'puzzling_ticket_reply_nonce' ) ) {
            wp_die(__('بررسی امنیتی ناموفق بود.', 'puzzlingcrm'));
        }

        $ticket_id = intval($_POST['ticket_id']);
        $comment_content = wp_kses_post($_POST['comment']);
        $ticket = get_post($ticket_id);
        $current_user = wp_get_current_user();
        $is_manager = current_user_can('manage_options');

        if ( !$ticket || empty($comment_content) ) {
            wp_die(__('تیکت یافت نشد یا متن پاسخ خالی است.', 'puzzlingcrm'));
        }
        
        if ( !$is_manager && $ticket->post_author != $current_user->ID ) {
            wp_die(__("شما اجازه پاسخ به این تیکت را ندارید.", 'puzzlingcrm'));
        }

        wp_insert_comment([
            'comment_post_ID' => $ticket_id, 
            'comment_author' => $current_user->display_name, 
            'comment_author_email' => $current_user->user_email, 
            'comment_content' => $comment_content, 
            'user_id' => $current_user->ID, 
            'comment_approved' => 1
        ]);

        if ( $is_manager ) {
            if (isset($_POST['ticket_status'])) {
                wp_set_object_terms( $ticket_id, sanitize_key($_POST['ticket_status']), 'ticket_status' );
            }
            PuzzlingCRM_Logger::add(__('پاسخ به تیکت شما', 'puzzlingcrm'), ['content' => sprintf(__("پشتیبانی به تیکت شما با موضوع '%s' پاسخ داد.", 'puzzlingcrm'), $ticket->post_title), 'type' => 'notification', 'user_id' => $ticket->post_author, 'object_id' => $ticket_id]);
        } else {
            wp_set_object_terms( $ticket_id, 'in-progress', 'ticket_status' );
            $this->notify_all_admins(
                __('پاسخ مشتری به تیکت', 'puzzlingcrm'), 
                ['content' => sprintf(__("مشتری به تیکت '%s' پاسخ داد.", 'puzzlingcrm'), $ticket->post_title), 'type' => 'notification', 'object_id' => $ticket_id]
            );
        }
        
        $redirect_url = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : wp_get_referer();
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    private function handle_payment_request() {
        $contract_id = isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0;
        $installment_index = isset($_GET['installment_index']) ? intval($_GET['installment_index']) : 0;

        if (!$contract_id || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'pay_installment_' . $contract_id . '_' . $installment_index)) {
            $this->redirect_with_notice('security_failed');
        }

        $installments = get_post_meta($contract_id, '_installments', true);

        if (!is_array($installments) || !isset($installments[$installment_index])) {
            $this->redirect_with_notice('installment_not_found');
        }

        $amount_toman = (int) $installments[$installment_index]['amount'];
        $merchant_id = PuzzlingCRM_Settings_Handler::get_setting('zarinpal_merchant_id');

        if (empty($merchant_id)) {
            $this->redirect_with_notice('gateway_not_configured');
        }
        if ($amount_toman < 100) {
            $this->redirect_with_notice('invalid_amount');
        }

        $zarinpal = new CSM_Zarinpal_Handler($merchant_id);
        $callback_url = add_query_arg(['puzzling_action' => 'verify_payment', 'contract_id' => $contract_id, 'installment_index' => $installment_index], puzzling_get_dashboard_url());
        $project_title = get_the_title(get_post_meta($contract_id, '_project_id', true));
        $description = sprintf(__('پرداخت قسط شماره %d پروژه %s', 'puzzlingcrm'), ($installment_index + 1), $project_title);
        
        $payment_link = $zarinpal->create_payment_link($amount_toman, $description, $callback_url);

        if ($payment_link) {
            if (!headers_sent()) {
                wp_redirect($payment_link);
                exit;
            }
            echo "<script>window.location.href = '" . esc_url_raw($payment_link) . "';</script>";
            exit;
        } else {
            $this->redirect_with_notice('payment_failed');
        }
    }

    private function handle_payment_verification() {
        $dashboard_url = puzzling_get_dashboard_url();
        $contract_id = isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0;
        $installment_index = isset($_GET['installment_index']) ? intval($_GET['installment_index']) : 0;
        $authority = isset($_GET['Authority']) ? sanitize_text_field($_GET['Authority']) : '';
        $status = isset($_GET['Status']) ? sanitize_text_field($_GET['Status']) : '';

        if (empty($contract_id) || empty($authority) || empty($status)) {
            $this->redirect_with_notice('payment_failed_verification', $dashboard_url);
        }
        
        if ( $status !== 'OK' ) {
            $this->redirect_with_notice('payment_cancelled', $dashboard_url);
        }

        $installments = get_post_meta($contract_id, '_installments', true);
        if (!is_array($installments) || !isset($installments[$installment_index])) {
            $this->redirect_with_notice('payment_failed_verification', $dashboard_url);
        }
        
        $amount_toman = (int) $installments[$installment_index]['amount'];
        $merchant_id = PuzzlingCRM_Settings_Handler::get_setting('zarinpal_merchant_id');
        $zarinpal = new CSM_Zarinpal_Handler($merchant_id);
        
        $verification = $zarinpal->verify_payment($amount_toman, $authority);

        if ( $verification && $verification['status'] === 'success' ) {
            $installments[$installment_index]['status'] = 'paid';
            $installments[$installment_index]['ref_id'] = $verification['ref_id'];
            update_post_meta($contract_id, '_installments', $installments);
            
            $contract = get_post($contract_id);
            $project_title = get_the_title(get_post_meta($contract_id, '_project_id', true));
            $customer = get_userdata($contract->post_author);
            
            $this->notify_all_admins(
                __('پرداخت موفق قسط', 'puzzlingcrm'), 
                ['content' => sprintf(__("مشتری '%s' یک قسط برای پروژه '%s' پرداخت کرد.", 'puzzlingcrm'), $customer->display_name, $project_title), 'type' => 'notification', 'object_id' => $contract_id]
            );
            PuzzlingCRM_Logger::add(__('قسط با موفقیت پرداخت شد', 'puzzlingcrm'), ['content' => sprintf(__("پرداخت شما برای قسط پروژه '%s' موفقیت آمیز بود. کد رهگیری: %s", 'puzzlingcrm'), $project_title, $verification['ref_id']), 'type' => 'log', 'user_id' => $customer->ID, 'object_id' => $contract_id]);

            $this->redirect_with_notice('payment_success', $dashboard_url);
        } else {
            $this->redirect_with_notice('payment_failed_verification', $dashboard_url);
        }
    }
}