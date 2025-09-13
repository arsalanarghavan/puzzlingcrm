<?php
class PuzzlingCRM_Form_Handler {

    public function __construct() {
        add_action( 'init', [ $this, 'handle_customer_info_form' ] );
        add_action( 'init', [ $this, 'handle_new_contract_form' ] );
        add_action('init', [$this, 'handle_settings_form']);
    }

    /**
     * Handles the submission of the customer info form displayed after a WooCommerce purchase.
     */
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
        // Ensure the logged-in user is the one who made the purchase
        if ( ! $user_id || get_current_user_id() !== $user_id ) {
             wp_die('You do not have permission to submit this form for this order.');
        }
        
        // Prevent duplicate submissions
        if ( get_user_meta( $user_id, 'puzzling_crm_form_submitted', true ) ) {
            return;
        }

        $business_name = sanitize_text_field( $_POST['business_name'] );
        $business_desc = sanitize_textarea_field( $_POST['business_desc'] );

        // Create a new project for this customer
        $project_id = wp_insert_post([
            'post_title'    => $business_name,
            'post_content'  => $business_desc,
            'post_status'   => 'publish',
            'post_author'   => $user_id,
            'post_type'     => 'project',
        ]);
        
        if ( $project_id && ! is_wp_error( $project_id ) ) {
            // Handle logo upload
            if ( ! empty( $_FILES['business_logo']['name'] ) ) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );

                $attachment_id = media_handle_upload( 'business_logo', $project_id );

                if ( ! is_wp_error( $attachment_id ) ) {
                    set_post_thumbnail( $project_id, $attachment_id );
                }
            }

            // Mark the form as submitted for this user
            update_user_meta( $user_id, 'puzzling_crm_form_submitted', true );
            update_post_meta($project_id, '_order_id', $order_id);
        }

        // Redirect to the dashboard
        $dashboard_page = get_page_by_title('PuzzlingCRM Dashboard');
        if ( $dashboard_page ) {
            wp_redirect( get_permalink( $dashboard_page->ID ) );
            exit;
        }
    }

    /**
     * Handles the creation of a new contract from the system manager dashboard.
     */
    public function handle_new_contract_form() {
        if ( ! isset( $_POST['submit_contract'] ) || ! isset( $_POST['_wpnonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'puzzling_create_contract' ) ) {
            wp_die('Security check failed.');
        }

        if ( ! current_user_can( 'manage_options' ) ) { // Or a more specific capability
            wp_die('You do not have permission to create contracts.');
        }

        $project_id = intval($_POST['project_id']);
        $payment_amounts = $_POST['payment_amount'] ?? [];
        $payment_due_dates = $_POST['payment_due_date'] ?? [];

        if ( empty($project_id) || empty($payment_amounts) || count($payment_amounts) !== count($payment_due_dates) ) {
            // Handle error: redirect back with an error message
            wp_redirect( add_query_arg('contract_error', 'data_invalid', wp_get_referer()) );
            exit;
        }
        
        $project = get_post($project_id);
        if(!$project || $project->post_type !== 'project'){
            wp_redirect( add_query_arg('contract_error', 'project_not_found', wp_get_referer()) );
            exit;
        }

        $installments = [];
        for ($i = 0; $i < count($payment_amounts); $i++) {
            if ( !empty($payment_amounts[$i]) && !empty($payment_due_dates[$i]) ) {
                 $installments[] = [
                    'amount'   => sanitize_text_field($payment_amounts[$i]),
                    'due_date' => sanitize_text_field($payment_due_dates[$i]),
                    'status'   => 'pending',
                ];
            }
        }
        
        if(empty($installments)){
             wp_redirect( add_query_arg('contract_error', 'no_installments', wp_get_referer()) );
            exit;
        }

        // Create the contract post
        $contract_id = wp_insert_post([
            'post_title'  => 'Contract for ' . get_the_title($project_id),
            'post_type'   => 'contract',
            'post_status' => 'publish',
            'post_author' => $project->post_author, // Assign contract to the project's author (the client)
        ]);

        if ( ! is_wp_error($contract_id) ) {
            // Save the project ID and installments as post meta
            update_post_meta($contract_id, '_project_id', $project_id);
            update_post_meta($contract_id, '_installments', $installments);

            // Redirect back to the dashboard with a success message
            $redirect_url = add_query_arg('contract_created', 'success', wp_get_referer());
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Handles saving the plugin settings from the system manager's dashboard.
     */
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

        // Redirect back with a success message
        $redirect_url = add_query_arg(['view' => 'settings', 'settings_saved' => 'success'], wp_get_referer());
        wp_redirect($redirect_url);
        exit;
    }
}