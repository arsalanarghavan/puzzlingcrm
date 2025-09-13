<?php
class PuzzlingCRM_Form_Handler {

    public function __construct() {
        // A single hook to handle all POST and GET actions for the plugin
        add_action( 'init', [ $this, 'router' ] );
        // A dedicated hook for handling admin-post actions like ticket replies
        add_action( 'admin_post_puzzling_ticket_reply', [ $this, 'handle_ticket_reply_form' ] );
    }
    
    /**
     * Main router to direct form submissions to the correct handler.
     * Handles both POST for submissions and GET for actions like payment requests.
     */
    public function router() {
        // Handle POST requests
        if (isset($_POST['puzzling_action']) && isset($_POST['_wpnonce'])) {
            $action = sanitize_key($_POST['puzzling_action']);
            $nonce_valid = wp_verify_nonce($_POST['_wpnonce'], 'puzzling_' . $action);

            if (!$nonce_valid) {
                $this->redirect_with_notice('security_failed');
            }
            
            // User must be logged in for most actions
            if (!is_user_logged_in()) {
                $this->redirect_with_notice('permission_denied');
            }

            // Route based on action
            switch ($action) {
                case 'manage_user':
                case 'manage_project':
                case 'create_contract':
                case 'save_settings':
                case 'manage_subscription_plan':
                case 'assign_subscription':
                case 'manage_appointment':
                    // These are manager-only actions
                    if (current_user_can('manage_options')) {
                        $handler_method = 'handle_' . $action;
                        if (method_exists($this, $handler_method)) {
                            $this->$handler_method();
                        }
                    } else {
                        $this->redirect_with_notice('permission_denied');
                    }
                    break;
                
                case 'new_ticket':
                    $this->handle_new_ticket_form();
                    break;
            }
        }

        // Handle GET requests (like payment links)
        if (isset($_GET['puzzling_action'])) {
             $action = sanitize_key($_GET['puzzling_action']);
             switch ($action) {
                 case 'pay_installment':
                    $this->handle_payment_request();
                    break;
                 case 'verify_payment':
                    $this->handle_payment_verification();
                    break;
             }
        }
    }

    /**
     * A centralized redirect function to avoid code repetition.
     */
    private function redirect_with_notice($notice_key, $base_url = '') {
        $url = empty($base_url) ? wp_get_referer() : $base_url;
        // Clean up URL from any action parameters
        $url = remove_query_arg(['puzzling_action', '_wpnonce', 'action', 'user_id', 'plan_id', 'sub_id', 'appt_id', 'project_id', 'puzzling_notice'], $url);
        wp_redirect( add_query_arg('puzzling_notice', $notice_key, $url) );
        exit;
    }

    /**
     * Handles creation and updates for users (customers and staff).
     */
    private function handle_manage_user_form() {
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

    /**
     * Handles creation and updates for projects.
     */
    private function handle_manage_project_form() {
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
            $result = wp_update_post($post_data);
            $notice = 'project_updated_success';
        } else {
            $result = wp_insert_post($post_data);
            $notice = 'project_created_success';
        }

        if (is_wp_error($result)) {
            $this->redirect_with_notice('project_error_failed');
        } else {
            $this->redirect_with_notice($notice);
        }
    }
    
    /**
     * Handles the intelligent contract creation form.
     */
    private function handle_create_contract() {
        $project_id = intval($_POST['project_id']);
        $payment_amounts = $_POST['payment_amount'] ?? [];
        $payment_due_dates = $_POST['payment_due_date'] ?? [];

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
            'post_title' => 'قرارداد پروژه: ' . get_the_title($project_id),
            'post_type' => 'contract',
            'post_status' => 'publish',
            'post_author' => $project->post_author
        ]);

        if ( ! is_wp_error($contract_id) ) {
            update_post_meta($contract_id, '_project_id', $project_id);
            update_post_meta($contract_id, '_installments', $installments);
            PuzzlingCRM_Logger::add( 'قرارداد جدید ایجاد شد', ['content' => "یک قرارداد جدید برای پروژه '".get_the_title($project_id)."' ایجاد گردید.", 'type' => 'log', 'object_id' => $contract_id]);
            $this->redirect_with_notice('contract_created_success');
        } else {
            $this->redirect_with_notice('contract_error_creation_failed');
        }
    }

    /**
     * Handles saving settings from payment or SMS tabs.
     */
    private function handle_save_settings() {
        if ( isset($_POST['puzzling_settings']) && is_array($_POST['puzzling_settings']) ) {
            $current_settings = PuzzlingCRM_Settings_Handler::get_all_settings();
            $new_settings = array_map('sanitize_text_field', $_POST['puzzling_settings']);
            $updated_settings = array_merge($current_settings, $new_settings);
            PuzzlingCRM_Settings_Handler::update_settings($updated_settings);
        }
        $this->redirect_with_notice('settings_saved');
    }

    /**
     * Creates or updates a subscription plan.
     */
    private function handle_manage_subscription_plan() {
        $plan_name = sanitize_text_field($_POST['plan_name']);
        $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $interval = sanitize_key($_POST['interval']);

        if (empty($plan_name) || !is_numeric($price) || empty($interval)) {
            $this->redirect_with_notice('plan_error_data_invalid');
        }

        $term = term_exists($plan_name, 'subscription_plan');
        if ($term && isset($term['term_id'])) {
            wp_update_term($term['term_id'], 'subscription_plan', ['name' => $plan_name]);
            update_term_meta($term['term_id'], 'price', $price);
            update_term_meta($term['term_id'], 'interval', $interval);
        } else {
            $new_term = wp_insert_term($plan_name, 'subscription_plan');
            if (!is_wp_error($new_term)) {
                add_term_meta($new_term['term_id'], 'price', $price);
                add_term_meta($new_term['term_id'], 'interval', $interval);
            }
        }
        $this->redirect_with_notice('plan_saved_success');
    }

    /**
     * Assigns a subscription plan to a customer.
     */
    private function handle_assign_subscription() {
        $customer_id = intval($_POST['customer_id']);
        $plan_id = intval($_POST['plan_id']);
        $start_date = sanitize_text_field($_POST['start_date']);

        if (empty($customer_id) || empty($plan_id) || empty($start_date)) {
            $this->redirect_with_notice('sub_error_data_invalid');
        }
        
        $plan = get_term($plan_id, 'subscription_plan');
        $customer = get_user_by('ID', $customer_id);
        $title = "اشتراک " . $plan->name . " برای " . $customer->display_name;

        $sub_id = wp_insert_post([
            'post_title' => $title, 'post_type' => 'pzl_subscription', 'post_status' => 'publish', 'post_author' => $customer_id,
        ]);

        if (!is_wp_error($sub_id)) {
            $interval_value = get_term_meta($plan_id, 'interval', true);
            $next_date = date('Y-m-d', strtotime($start_date . ' +1 ' . $interval_value));
            
            update_post_meta($sub_id, '_plan_id', $plan_id);
            update_post_meta($sub_id, '_start_date', $start_date);
            update_post_meta($sub_id, '_next_payment_date', $next_date);
            wp_set_object_terms($sub_id, 'active', 'subscription_status');
            $this->redirect_with_notice('sub_assigned_success');
        } else {
            $this->redirect_with_notice('sub_error_failed');
        }
    }

    /**
     * Creates a new appointment.
     */
    private function handle_manage_appointment() {
        $customer_id = intval($_POST['customer_id']);
        $title = sanitize_text_field($_POST['title']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        if (empty($customer_id) || empty($title) || empty($date) || empty($time)) {
             $this->redirect_with_notice('appt_error_data_invalid');
        }

        $full_datetime = $date . ' ' . $time;
        $post_id = wp_insert_post([
            'post_title' => $title, 'post_content' => $notes, 'post_type' => 'pzl_appointment', 'post_status' => 'publish', 'post_author' => $customer_id,
        ]);
        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, '_appointment_datetime', $full_datetime);
            $this->redirect_with_notice('appt_created_success');
        } else {
            $this->redirect_with_notice('appt_error_failed');
        }
    }

    /**
     * Handles new ticket submission from any logged-in user.
     */
    private function handle_new_ticket_form() {
        $title = sanitize_text_field( $_POST['ticket_title'] );
        $content = wp_kses_post( $_POST['ticket_content'] );

        if ( empty($title) || empty($content) ) $this->redirect_with_notice('ticket_error_empty');

        $ticket_id = wp_insert_post(['post_title' => $title, 'post_content' => $content, 'post_status' => 'publish', 'post_author' => get_current_user_id(), 'post_type' => 'ticket']);

        if ( ! is_wp_error( $ticket_id ) ) {
            wp_set_object_terms( $ticket_id, 'open', 'ticket_status' );
            PuzzlingCRM_Logger::add('تیکت پشتیبانی جدید', ['content' => "یک تیکت جدید با موضوع '{$title}' توسط کاربر " . wp_get_current_user()->display_name . " ارسال شد.", 'type' => 'notification', 'user_id' => 1, 'object_id' => $ticket_id]);
            $this->redirect_with_notice('ticket_created_success');
        } else {
            $this->redirect_with_notice('ticket_error_failed');
        }
    }

    /**
     * Handles ticket replies from the admin-post hook.
     */
    public function handle_ticket_reply_form() {
        if ( ! is_user_logged_in() || ! isset( $_POST['_wpnonce_ticket_reply'] ) || ! wp_verify_nonce( $_POST['_wpnonce_ticket_reply'], 'puzzling_ticket_reply_nonce' ) ) {
            wp_die('بررسی امنیتی ناموفق بود.');
        }

        $ticket_id = intval($_POST['ticket_id']);
        $comment_content = wp_kses_post($_POST['comment']);
        $ticket = get_post($ticket_id);
        $current_user = wp_get_current_user();
        $is_manager = current_user_can('manage_options');

        if ( !$ticket || empty($comment_content) ) wp_die('تیکت یافت نشد یا متن پاسخ خالی است.');
        if ( !$is_manager && $ticket->post_author != $current_user->ID ) wp_die('شما اجازه پاسخ به این تیکت را ندارید.');

        wp_insert_comment(['comment_post_ID' => $ticket_id, 'comment_author' => $current_user->display_name, 'comment_author_email' => $current_user->user_email, 'comment_content' => $comment_content, 'user_id' => $current_user->ID, 'comment_approved' => 1]);

        if ( $is_manager ) {
            wp_set_object_terms( $ticket_id, sanitize_key($_POST['ticket_status']), 'ticket_status' );
            PuzzlingCRM_Logger::add('پاسخ به تیکت شما', ['content' => "پشتیبانی به تیکت '{$ticket->post_title}' پاسخ داد.", 'type' => 'notification', 'user_id' => $ticket->post_author, 'object_id' => $ticket_id]);
        } else {
            wp_set_object_terms( $ticket_id, 'in-progress', 'ticket_status' );
            PuzzlingCRM_Logger::add('پاسخ مشتری به تیکت', ['content' => "مشتری به تیکت '{$ticket->post_title}' پاسخ داد.", 'type' => 'notification', 'user_id' => 1, 'object_id' => $ticket_id]);
        }
        
        wp_safe_redirect( $_POST['redirect_to'] ?? wp_get_referer() );
        exit;
    }
    
    /**
     * Initiates a payment request with the gateway.
     */
    private function handle_payment_request() {
        if (!isset($_GET['contract_id'], $_GET['installment_index'], $_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'pay_installment_' . $_GET['contract_id'] . '_' . $_GET['installment_index'])) {
            $this->redirect_with_notice('invalid_payment_link');
        }

        $contract_id = intval($_GET['contract_id']);
        $installment_index = intval($_GET['installment_index']);
        $installments = get_post_meta($contract_id, '_installments', true);

        if (!is_array($installments) || !isset($installments[$installment_index])) {
            $this->redirect_with_notice('installment_not_found');
        }

        $amount = $installments[$installment_index]['amount'];
        $merchant_id = PuzzlingCRM_Settings_Handler::get_setting('zarinpal_merchant_id');
        if (empty($merchant_id)) $this->redirect_with_notice('gateway_not_configured');

        $zarinpal = new CSM_Zarinpal_Handler($merchant_id);
        $callback_url = add_query_arg(['puzzling_action' => 'verify_payment', 'contract_id' => $contract_id, 'installment_index' => $installment_index], get_permalink(get_page_by_title('PuzzlingCRM Dashboard')->ID));
        $description = 'پرداخت قسط شماره ' . ($installment_index + 1) . ' برای پروژه ' . get_the_title(get_post_meta($contract_id, '_project_id', true));
        
        $payment_link = $zarinpal->create_payment_link($amount, $description, $callback_url);

        if ($payment_link) {
            wp_redirect($payment_link);
            exit;
        } else {
            $this->redirect_with_notice('payment_failed');
        }
    }

    /**
     * Verifies the payment after returning from the gateway.
     */
    private function handle_payment_verification() {
        $dashboard_url = get_permalink(get_page_by_title('PuzzlingCRM Dashboard')->ID);
        if (!isset($_GET['contract_id'], $_GET['installment_index'], $_GET['Authority'], $_GET['Status'])) {
            $this->redirect_with_notice('payment_failed', $dashboard_url);
        }

        $contract_id = intval($_GET['contract_id']);
        $installment_index = intval($_GET['installment_index']);
        $authority = sanitize_text_field($_GET['Authority']);
        $status = sanitize_text_field($_GET['Status']);
        
        if ( $status !== 'OK' ) $this->redirect_with_notice('payment_cancelled', $dashboard_url);

        $installments = get_post_meta($contract_id, '_installments', true);
        if (!is_array($installments) || !isset($installments[$installment_index])) {
            $this->redirect_with_notice('payment_failed', $dashboard_url);
        }
        
        $amount = $installments[$installment_index]['amount'];
        $merchant_id = PuzzlingCRM_Settings_Handler::get_setting('zarinpal_merchant_id');
        $zarinpal = new CSM_Zarinpal_Handler($merchant_id);
        $verification = $zarinpal->verify_payment($amount, $authority);

        if ( $verification && $verification['status'] === 'success' ) {
            $installments[$installment_index]['status'] = 'paid';
            $installments[$installment_index]['ref_id'] = $verification['ref_id'];
            update_post_meta($contract_id, '_installments', $installments);
            
            // Logging
            $contract = get_post($contract_id);
            $project_title = get_the_title(get_post_meta($contract_id, '_project_id', true));
            $customer = get_userdata($contract->post_author);
            PuzzlingCRM_Logger::add('پرداخت قسط موفق', ['content' => "مشتری '{$customer->display_name}' قسطی برای پروژه '{$project_title}' پرداخت کرد.", 'type' => 'notification', 'user_id' => 1, 'object_id' => $contract_id]);
            PuzzlingCRM_Logger::add('قسط با موفقیت پرداخت شد', ['content' => "پرداخت شما برای یکی از اقساط پروژه '{$project_title}' با موفقیت ثبت شد.", 'type' => 'log', 'user_id' => $customer->ID, 'object_id' => $contract_id]);

            $this->redirect_with_notice('payment_success', $dashboard_url);
        } else {
            $this->redirect_with_notice('payment_failed_verification', $dashboard_url);
        }
    }
}