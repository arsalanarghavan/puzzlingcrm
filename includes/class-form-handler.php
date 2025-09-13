<?php
class PuzzlingCRM_Form_Handler {

    public function __construct() {
        add_action( 'init', [ $this, 'handle_customer_info_form' ] );
        add_action( 'init', [ $this, 'handle_new_contract_form' ] );
        add_action('init', [$this, 'handle_settings_form']);
        add_action('init', [$this, 'handle_payment_request']);
        add_action('template_redirect', [$this, 'handle_payment_verification']);
    }

    public function handle_customer_info_form() {
        if ( ! isset( $_POST['puzzling_submit_customer_info'] ) || ! isset( $_POST['puzzling_customer_info_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['puzzling_customer_info_nonce'], 'puzzling_save_customer_info' ) ) {
            wp_die('Security check failed.');
        }

        $order_id = intval( $_POST['puzzling_form_order_id'] );
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $user_id = $order->get_user_id();
        if ( ! $user_id || get_current_user_id() !== $user_id ) {
             wp_die('You do not have permission to submit this form for this order.');
        }
        
        if ( get_user_meta( $user_id, 'puzzling_crm_form_submitted', true ) ) {
            return;
        }

        $business_name = sanitize_text_field( $_POST['business_name'] );
        $business_desc = sanitize_textarea_field( $_POST['business_desc'] );

        $project_id = wp_insert_post([
            'post_title'    => $business_name,
            'post_content'  => $business_desc,
            'post_status'   => 'publish',
            'post_author'   => $user_id,
            'post_type'     => 'project',
        ]);
        
        if ( $project_id && ! is_wp_error( $project_id ) ) {
            if ( ! empty( $_FILES['business_logo']['name'] ) ) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );

                $attachment_id = media_handle_upload( 'business_logo', $project_id );

                if ( ! is_wp_error( $attachment_id ) ) {
                    set_post_thumbnail( $project_id, $attachment_id );
                }
            }

            update_user_meta( $user_id, 'puzzling_crm_form_submitted', true );
            update_post_meta($project_id, '_order_id', $order_id);
        }

        $dashboard_page = get_page_by_title('PuzzlingCRM Dashboard');
        if ( $dashboard_page ) {
            wp_redirect( get_permalink( $dashboard_page->ID ) );
            exit;
        }
    }

    public function handle_new_contract_form() {
        if ( ! isset( $_POST['submit_contract'] ) || ! isset( $_POST['_wpnonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'puzzling_create_contract' ) ) {
            wp_die('Security check failed.');
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die('You do not have permission to create contracts.');
        }

        $project_id = intval($_POST['project_id']);
        $payment_amounts = $_POST['payment_amount'] ?? [];
        $payment_due_dates = $_POST['payment_due_date'] ?? [];

        if ( empty($project_id) || empty($payment_amounts) || count($payment_amounts) !== count($payment_due_dates) ) {
            wp_redirect( add_query_arg('puzzling_notice', 'contract_error_data_invalid', wp_get_referer()) );
            exit;
        }
        
        $project = get_post($project_id);
        if(!$project || $project->post_type !== 'project'){
            wp_redirect( add_query_arg('puzzling_notice', 'contract_error_project_not_found', wp_get_referer()) );
            exit;
        }

        $installments = [];
        for ($i = 0; $i < count($payment_amounts); $i++) {
            if ( !empty($payment_amounts[$i]) && !empty($payment_due_dates[$i]) ) {
                 $installments[] = [
                    'amount'   => sanitize_text_field($payment_amounts[$i]),
                    'due_date' => sanitize_text_field($payment_due_dates[$i]),
                    'status'   => 'pending',
                    'ref_id'   => '', // Add ref_id for tracking
                ];
            }
        }
        
        if(empty($installments)){
             wp_redirect( add_query_arg('puzzling_notice', 'contract_error_no_installments', wp_get_referer()) );
            exit;
        }

        $contract_id = wp_insert_post([
            'post_title'  => 'قرارداد پروژه: ' . get_the_title($project_id),
            'post_type'   => 'contract',
            'post_status' => 'publish',
            'post_author' => $project->post_author,
        ]);

        if ( ! is_wp_error($contract_id) ) {
            update_post_meta($contract_id, '_project_id', $project_id);
            update_post_meta($contract_id, '_installments', $installments);
            wp_redirect(add_query_arg('puzzling_notice', 'contract_created_success', wp_get_referer()));
            exit;
        }
    }
    
    public function handle_settings_form() {
        if ( ! isset($_POST['puzzling_action']) || $_POST['puzzling_action'] !== 'save_settings' ) {
            return;
        }

        if ( ! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'puzzling_save_settings') ) {
            wp_die('Security check failed.');
        }

        if ( ! current_user_can('manage_options') ) {
            wp_die('You do not have permission to save settings.');
        }

        if ( isset($_POST['puzzling_settings']) && is_array($_POST['puzzling_settings']) ) {
            $settings_data = $_POST['puzzling_settings'];
            $sanitized_settings = [];
            foreach ($settings_data as $key => $value) {
                $sanitized_settings[sanitize_key($key)] = sanitize_text_field($value);
            }
            PuzzlingCRM_Settings_Handler::update_settings($sanitized_settings);
        }

        wp_redirect( add_query_arg(['view' => 'settings', 'puzzling_notice' => 'settings_saved'], wp_get_referer()) );
        exit;
    }
    
    public function handle_payment_request() {
        if ( ! isset($_GET['puzzling_action']) || $_GET['puzzling_action'] !== 'pay_installment' ) {
            return;
        }

        if ( ! isset($_GET['contract_id']) || ! isset($_GET['installment_index']) || ! isset($_GET['_wpnonce']) ) {
            return;
        }

        if ( ! wp_verify_nonce($_GET['_wpnonce'], 'pay_installment_' . $_GET['contract_id'] . '_' . $_GET['installment_index']) ) {
            wp_die('لینک پرداخت معتبر نیست.');
        }

        $contract_id = intval($_GET['contract_id']);
        $installment_index = intval($_GET['installment_index']);

        $contract = get_post($contract_id);
        $installments = get_post_meta($contract_id, '_installments', true);

        if ( !$contract || !is_array($installments) || !isset($installments[$installment_index]) ) {
            wp_die('اطلاعات قسط مورد نظر یافت نشد.');
        }

        $installment = $installments[$installment_index];
        $amount = $installment['amount'];
        
        $merchant_id = PuzzlingCRM_Settings_Handler::get_setting('zarinpal_merchant_id');
        if (empty($merchant_id)) {
            wp_die('درگاه پرداخت پیکربندی نشده است. لطفاً با مدیر سیستم تماس بگیرید.');
        }

        $zarinpal = new CSM_Zarinpal_Handler($merchant_id);
        $dashboard_page = get_page_by_title('PuzzlingCRM Dashboard');
        $callback_url = add_query_arg([
            'puzzling_action' => 'verify_payment',
            'contract_id' => $contract_id,
            'installment_index' => $installment_index
        ], get_permalink($dashboard_page->ID));
        
        $project_id = get_post_meta($contract_id, '_project_id', true);
        $description = 'پرداخت قسط شماره ' . ($installment_index + 1) . ' برای پروژه ' . get_the_title($project_id);

        $payment_link = $zarinpal->create_payment_link($amount, $description, $callback_url);

        if ($payment_link) {
            wp_redirect($payment_link);
            exit;
        } else {
            wp_redirect(add_query_arg('puzzling_notice', 'payment_failed', get_permalink($dashboard_page->ID)));
            exit;
        }
    }

    public function handle_payment_verification() {
        if ( ! isset($_GET['puzzling_action']) || $_GET['puzzling_action'] !== 'verify_payment' ) {
            return;
        }
        
        $dashboard_page = get_page_by_title('PuzzlingCRM Dashboard');
        $dashboard_url = get_permalink($dashboard_page->ID);

        if ( ! isset($_GET['contract_id'], $_GET['installment_index'], $_GET['Authority'], $_GET['Status']) ) {
             wp_redirect(add_query_arg('puzzling_notice', 'payment_failed', $dashboard_url));
             exit;
        }

        $contract_id = intval($_GET['contract_id']);
        $installment_index = intval($_GET['installment_index']);
        $authority = sanitize_text_field($_GET['Authority']);
        $status = sanitize_text_field($_GET['Status']);

        if ( $status !== 'OK' ) {
            wp_redirect(add_query_arg('puzzling_notice', 'payment_cancelled', $dashboard_url));
            exit;
        }

        $installments = get_post_meta($contract_id, '_installments', true);
        if ( ! is_array($installments) || ! isset($installments[$installment_index]) ) {
             wp_redirect(add_query_arg('puzzling_notice', 'payment_failed', $dashboard_url));
             exit;
        }
        
        $amount = $installments[$installment_index]['amount'];
        $merchant_id = PuzzlingCRM_Settings_Handler::get_setting('zarinpal_merchant_id');

        $zarinpal = new CSM_Zarinpal_Handler($merchant_id);
        $verification = $zarinpal->verify_payment($amount, $authority);

        if ( $verification && $verification['status'] === 'success' ) {
            $installments[$installment_index]['status'] = 'paid';
            $installments[$installment_index]['ref_id'] = $verification['ref_id'];
            update_post_meta($contract_id, '_installments', $installments);
            wp_redirect(add_query_arg('puzzling_notice', 'payment_success', $dashboard_url));
            exit;
        } else {
            wp_redirect(add_query_arg('puzzling_notice', 'payment_failed_verification', $dashboard_url));
            exit;
        }
    }
}