<?php
class PuzzlingCRM_Form_Handler {

    public function __construct() {
        // Deactivated automatic project creation from WooCommerce
        // add_action( 'init', [ $this, 'handle_customer_info_form' ] ); 
        
        add_action( 'init', [ $this, 'handle_manage_project_form' ] );
        add_action( 'init', [ $this, 'handle_new_contract_form' ] );
        add_action( 'init', [$this, 'handle_settings_form']);
        add_action( 'init', [$this, 'handle_payment_request']);
        add_action( 'template_redirect', [$this, 'handle_payment_verification']);
        add_action( 'init', [ $this, 'handle_new_ticket_form' ] );
        add_action( 'admin_post_puzzling_ticket_reply', [ $this, 'handle_ticket_reply_form' ] );
    }

    private function redirect_with_notice($notice_key, $base_url = '') {
        $url = empty($base_url) ? wp_get_referer() : $base_url;
        $url = remove_query_arg(['puzzling_action', '_wpnonce', 'action', 'project_id'], $url);
        wp_redirect( add_query_arg('puzzling_notice', $notice_key, $url) );
        exit;
    }

    public function handle_manage_project_form() {
        if ( ! isset( $_POST['puzzling_action'] ) || $_POST['puzzling_action'] !== 'manage_project' ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'puzzling_manage_project' ) ) {
            $this->redirect_with_notice('security_failed');
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            $this->redirect_with_notice('permission_denied');
        }

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

    public function handle_new_contract_form() {
        if ( ! isset( $_POST['submit_contract'] ) || ! isset( $_POST['_wpnonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'puzzling_create_contract' ) ) $this->redirect_with_notice('security_failed');
        if ( ! current_user_can( 'manage_options' ) ) $this->redirect_with_notice('permission_denied');
        
        $project_id = intval($_POST['project_id']);
        $payment_amounts = $_POST['payment_amount'] ?? [];
        $payment_due_dates = $_POST['payment_due_date'] ?? [];

        if ( empty($project_id) || empty($payment_amounts) || count($payment_amounts) !== count($payment_due_dates) ) $this->redirect_with_notice('contract_error_data_invalid');
        
        $project = get_post($project_id);
        if(!$project || $project->post_type !== 'project') $this->redirect_with_notice('contract_error_project_not_found');

        $installments = [];
        for ($i = 0; $i < count($payment_amounts); $i++) {
            if ( !empty($payment_amounts[$i]) && !empty($payment_due_dates[$i]) ) {
                 $installments[] = [ 
                     'amount'   => sanitize_text_field(str_replace(',', '', $payment_amounts[$i])), // Remove commas
                     'due_date' => sanitize_text_field($payment_due_dates[$i]), 
                     'status'   => 'pending', 
                     'ref_id'   => '' 
                    ];
            }
        }
        
        if(empty($installments)) $this->redirect_with_notice('contract_error_no_installments');

        $contract_id = wp_insert_post(['post_title' => 'قرارداد پروژه: ' . get_the_title($project_id), 'post_type' => 'contract', 'post_status' => 'publish', 'post_author' => $project->post_author]);

        if ( ! is_wp_error($contract_id) ) {
            update_post_meta($contract_id, '_project_id', $project_id);
            update_post_meta($contract_id, '_installments', $installments);
            
            $project_title = get_the_title($project_id);
            PuzzlingCRM_Logger::add( 'قرارداد جدید ایجاد شد', ['content' => "یک قرارداد جدید برای پروژه '{$project_title}' ایجاد گردید.", 'type' => 'log', 'object_id' => $contract_id]);

            $this->redirect_with_notice('contract_created_success');
        } else {
            $this->redirect_with_notice('contract_error_creation_failed');
        }
    }
    
    public function handle_settings_form() {
        if ( ! isset($_POST['puzzling_action']) || ! in_array($_POST['puzzling_action'], ['save_payment_settings', 'save_sms_settings']) ) return;
        if ( ! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'puzzling_save_settings') ) $this->redirect_with_notice('security_failed');
        if ( ! current_user_can('manage_options') ) $this->redirect_with_notice('permission_denied');

        if ( isset($_POST['puzzling_settings']) && is_array($_POST['puzzling_settings']) ) {
            $current_settings = PuzzlingCRM_Settings_Handler::get_all_settings();
            $new_settings = array_map('sanitize_text_field', $_POST['puzzling_settings']);
            $updated_settings = array_merge($current_settings, $new_settings);
            PuzzlingCRM_Settings_Handler::update_settings($updated_settings);
        }
        $this->redirect_with_notice('settings_saved');
    }
    
    public function handle_payment_request() {
        if ( ! isset($_GET['puzzling_action']) || $_GET['puzzling_action'] !== 'pay_installment' ) return;
        if ( ! isset($_GET['contract_id'], $_GET['installment_index'], $_GET['_wpnonce']) ) return;
        if ( ! wp_verify_nonce($_GET['_wpnonce'], 'pay_installment_' . $_GET['contract_id'] . '_' . $_GET['installment_index']) ) $this->redirect_with_notice('invalid_payment_link');

        $contract_id = intval($_GET['contract_id']);
        $installment_index = intval($_GET['installment_index']);
        $contract = get_post($contract_id);
        $installments = get_post_meta($contract_id, '_installments', true);

        if ( !$contract || !is_array($installments) || !isset($installments[$installment_index]) ) $this->redirect_with_notice('installment_not_found');

        $amount = $installments[$installment_index]['amount'];
        $merchant_id = PuzzlingCRM_Settings_Handler::get_setting('zarinpal_merchant_id');
        if (empty($merchant_id)) $this->redirect_with_notice('gateway_not_configured');

        $zarinpal = new CSM_Zarinpal_Handler($merchant_id);
        $dashboard_page = get_page_by_title('PuzzlingCRM Dashboard');
        $callback_url = add_query_arg(['puzzling_action' => 'verify_payment', 'contract_id' => $contract_id, 'installment_index' => $installment_index], get_permalink($dashboard_page->ID));
        $project_id = get_post_meta($contract_id, '_project_id', true);
        $description = 'پرداخت قسط شماره ' . ($installment_index + 1) . ' برای پروژه ' . get_the_title($project_id);
        $payment_link = $zarinpal->create_payment_link($amount, $description, $callback_url);

        if ($payment_link) {
            wp_redirect($payment_link);
            exit;
        } else {
            $this->redirect_with_notice('payment_failed', get_permalink($dashboard_page->ID));
        }
    }

    public function handle_payment_verification() {
        if ( ! isset($_GET['puzzling_action']) || $_GET['puzzling_action'] !== 'verify_payment' ) return;
        
        $dashboard_page = get_page_by_title('PuzzlingCRM Dashboard');
        $dashboard_url = get_permalink($dashboard_page->ID);

        if ( ! isset($_GET['contract_id'], $_GET['installment_index'], $_GET['Authority'], $_GET['Status']) ) $this->redirect_with_notice('payment_failed', $dashboard_url);

        $contract_id = intval($_GET['contract_id']);
        $installment_index = intval($_GET['installment_index']);
        $authority = sanitize_text_field($_GET['Authority']);
        $status = sanitize_text_field($_GET['Status']);
        $contract = get_post($contract_id);

        if ( $status !== 'OK' ) $this->redirect_with_notice('payment_cancelled', $dashboard_url);

        $installments = get_post_meta($contract_id, '_installments', true);
        if ( ! is_array($installments) || ! isset($installments[$installment_index]) ) $this->redirect_with_notice('payment_failed', $dashboard_url);
        
        $amount = $installments[$installment_index]['amount'];
        $merchant_id = PuzzlingCRM_Settings_Handler::get_setting('zarinpal_merchant_id');
        $zarinpal = new CSM_Zarinpal_Handler($merchant_id);
        $verification = $zarinpal->verify_payment($amount, $authority);

        if ( $verification && $verification['status'] === 'success' ) {
            $installments[$installment_index]['status'] = 'paid';
            $installments[$installment_index]['ref_id'] = $verification['ref_id'];
            update_post_meta($contract_id, '_installments', $installments);
            
            $project_title = get_the_title(get_post_meta($contract_id, '_project_id', true));
            $customer = get_userdata($contract->post_author);
            PuzzlingCRM_Logger::add('پرداخت قسط موفق', ['content' => "مشتری '{$customer->display_name}' قسطی برای پروژه '{$project_title}' پرداخت کرد.", 'type' => 'notification', 'user_id' => 1, 'object_id' => $contract_id]);
            PuzzlingCRM_Logger::add('قسط با موفقیت پرداخت شد', ['content' => "پرداخت شما برای یکی از اقساط پروژه '{$project_title}' با موفقیت ثبت شد.", 'type' => 'log', 'user_id' => $customer->ID, 'object_id' => $contract_id]);

            $this->redirect_with_notice('payment_success', $dashboard_url);
        } else {
            $this->redirect_with_notice('payment_failed_verification', $dashboard_url);
        }
    }

    public function handle_new_ticket_form() {
        if ( ! isset( $_POST['puzzling_action'] ) || $_POST['puzzling_action'] !== 'new_ticket' ) return;
        if ( ! is_user_logged_in() || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'puzzling_new_ticket_nonce' ) ) $this->redirect_with_notice('security_failed');

        $title = sanitize_text_field( $_POST['ticket_title'] );
        $content = wp_kses_post( $_POST['ticket_content'] );

        if ( empty($title) || empty($content) ) $this->redirect_with_notice('ticket_error_empty');

        $ticket_id = wp_insert_post(['post_title' => $title, 'post_content' => $content, 'post_status' => 'publish', 'post_author' => get_current_user_id(), 'post_type' => 'ticket']);

        if ( ! is_wp_error( $ticket_id ) ) {
            wp_set_object_terms( $ticket_id, 'open', 'ticket_status' );
            
            PuzzlingCRM_Logger::add('تیکت پشتیبانی جدید', ['content' => "یک تیکت جدید با موضوع '{$title}' توسط کاربر " . wp_get_current_user()->display_name . " ارسال شد.", 'type' => 'notification', 'user_id' => 1, 'object_id' => $ticket_id]);
            
            $redirect_url = add_query_arg(['view' => 'tickets', 'ticket_id' => $ticket_id, 'puzzling_notice' => 'ticket_created_success'], wp_get_referer());
            wp_redirect(remove_query_arg(['puzzling_action'], $redirect_url));
            exit;
        } else {
            $this->redirect_with_notice('ticket_error_failed');
        }
    }

    public function handle_ticket_reply_form() {
        if ( ! is_user_logged_in() || ! isset( $_POST['_wpnonce_ticket_reply'] ) || ! wp_verify_nonce( $_POST['_wpnonce_ticket_reply'], 'puzzling_ticket_reply_nonce' ) ) wp_die('بررسی امنیتی ناموفق بود.');

        $ticket_id = intval($_POST['ticket_id']);
        $comment_content = wp_kses_post($_POST['comment']);
        $ticket = get_post($ticket_id);
        $current_user = wp_get_current_user();
        $is_manager = current_user_can('manage_options');

        if ( !$ticket || empty($comment_content) ) wp_die('تیکت یافت نشد یا متن پاسخ خالی است.');
        if ( !$is_manager && $ticket->post_author != $current_user->ID ) wp_die('شما اجازه پاسخ به این تیکت را ندارید.');

        $comment_id = wp_insert_comment(['comment_post_ID' => $ticket_id, 'comment_author' => $current_user->display_name, 'comment_author_email' => $current_user->user_email, 'comment_content' => $comment_content, 'user_id' => $current_user->ID, 'comment_approved' => 1]);

        if ( $comment_id ) {
            if ( $is_manager ) {
                $new_status = sanitize_key($_POST['ticket_status']);
                wp_set_object_terms( $ticket_id, $new_status, 'ticket_status' );
                PuzzlingCRM_Logger::add('پاسخ به تیکت شما', ['content' => "پشتیبانی به تیکت '{$ticket->post_title}' پاسخ داد.", 'type' => 'notification', 'user_id' => $ticket->post_author, 'object_id' => $ticket_id]);
            } else {
                wp_set_object_terms( $ticket_id, 'in-progress', 'ticket_status' );
                PuzzlingCRM_Logger::add('پاسخ مشتری به تیکت', ['content' => "مشتری به تیکت '{$ticket->post_title}' پاسخ داد.", 'type' => 'notification', 'user_id' => 1, 'object_id' => $ticket_id]);
            }
        }
        
        wp_safe_redirect( $_POST['redirect_to'] ?? wp_get_referer() );
        exit;
    }
}