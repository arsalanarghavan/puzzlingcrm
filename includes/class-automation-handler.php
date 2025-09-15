<?php
/**
 * PuzzlingCRM Automation Handler
 *
 * Handles the automation workflow from WooCommerce purchase to project creation.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Automation_Handler {

    public function __construct() {
        // Add meta box to product page to link a form
        add_action( 'add_meta_boxes', [ $this, 'add_form_link_meta_box' ] );
        add_action( 'save_post_product', [ $this, 'save_form_link_meta_box' ] );

        // Hook into WooCommerce order completion
        add_action( 'woocommerce_order_status_completed', [ $this, 'trigger_automation_on_purchase' ], 10, 1 );

        // Add a query variable to detect our form submission page
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
        add_action( 'template_redirect', [ $this, 'catch_form_submission_page' ] );
    }

    /**
     * Adds a meta box to the WooCommerce product edit screen.
     */
    public function add_form_link_meta_box() {
        add_meta_box(
            'puzzlingcrm_form_link',
            __( 'PuzzlingCRM Automation', 'puzzlingcrm' ),
            [ $this, 'render_form_link_meta_box' ],
            'product',
            'side',
            'default'
        );
    }

    /**
     * Renders the content of the form link meta box.
     */
    public function render_form_link_meta_box( $post ) {
        wp_nonce_field( 'puzzling_save_form_link', 'puzzling_form_link_nonce' );
        $linked_form_id = get_post_meta( $post->ID, '_puzzling_linked_form_id', true );

        $forms = get_posts([
            'post_type' => 'pzl_form',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        echo '<p>' . __( 'Select a form to display to the customer after purchasing this product.', 'puzzlingcrm' ) . '</p>';
        echo '<select name="puzzling_linked_form_id" style="width:100%;">';
        echo '<option value="">' . __( '-- No Form --', 'puzzlingcrm' ) . '</option>';

        if ( ! empty( $forms ) ) {
            foreach ( $forms as $form ) {
                echo '<option value="' . esc_attr( $form->ID ) . '" ' . selected( $linked_form_id, $form->ID, false ) . '>' . esc_html( $form->post_title ) . '</option>';
            }
        }
        echo '</select>';
    }

    /**
     * Saves the linked form ID when the product is saved.
     */
    public function save_form_link_meta_box( $post_id ) {
        if ( ! isset( $_POST['puzzling_form_link_nonce'] ) || ! wp_verify_nonce( $_POST['puzzling_form_link_nonce'], 'puzzling_save_form_link' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['puzzling_linked_form_id'] ) ) {
            update_post_meta( $post_id, '_puzzling_linked_form_id', intval( $_POST['puzzling_linked_form_id'] ) );
        }
    }

    /**
     * Main automation trigger after a successful payment.
     */
    public function trigger_automation_on_purchase( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $customer_id = $order->get_customer_id();
        if ( ! $customer_id ) return; // Only for registered users

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $linked_form_id = get_post_meta( $product_id, '_puzzling_linked_form_id', true );

            if ( $linked_form_id ) {
                // Generate a unique token for this specific purchase
                $token = wp_generate_password( 32, false );
                
                // Store the necessary info for when the customer fills out the form
                update_post_meta( $linked_form_id, '_automation_token_' . $token, [
                    'order_id' => $order_id,
                    'product_id' => $product_id,
                    'customer_id' => $customer_id,
                    'timestamp' => time(),
                ]);

                // Create the unique URL
                $submission_url = add_query_arg([
                    'puzzling_form_id' => $linked_form_id,
                    'token' => $token
                ], home_url('/'));

                // Send an email to the customer with the link to the form
                $this->send_form_submission_email( $customer_id, $submission_url, $product_id );
            }
        }
    }

    /**
     * Sends an email to the customer with the link to the form.
     */
    private function send_form_submission_email( $customer_id, $url, $product_id ) {
        $user = get_userdata( $customer_id );
        $product = wc_get_product( $product_id );
        if ( ! $user || ! $product ) return;

        $to = $user->user_email;
        $subject = sprintf( __( 'اطلاعات تکمیلی برای محصول: %s', 'puzzlingcrm' ), $product->get_name() );
        $body = '<p>' . sprintf( __( 'سلام %s،', 'puzzlingcrm' ), $user->display_name ) . '</p>';
        $body .= '<p>' . sprintf( __( 'از خرید شما برای محصول "%s" سپاسگزاریم. برای تکمیل فرآیند و تعریف پروژه، لطفاً فرم زیر را تکمیل کنید:', 'puzzlingcrm' ), $product->get_name() ) . '</p>';
        $body .= '<p><a href="' . esc_url( $url ) . '" style="padding:10px 20px; background-color:#F0192A; color:#fff; text-decoration:none; border-radius:5px;">' . __( 'تکمیل فرم', 'puzzlingcrm' ) . '</a></p>';
        $body .= '<p>' . __( 'با تشکر،', 'puzzlingcrm' ) . '<br>' . get_bloginfo( 'name' ) . '</p>';

        wp_mail( $to, $subject, $body, ['Content-Type: text/html; charset=UTF-8'] );
    }

    /**
     * Adds custom query variables.
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'puzzling_form_id';
        $vars[] = 'token';
        return $vars;
    }

    /**
     * Catches the special URL for our form and displays the form template.
     */
    public function catch_form_submission_page() {
        if ( get_query_var( 'puzzling_form_id' ) && get_query_var( 'token' ) ) {
            $template = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/page-form-submission.php';
            if ( file_exists( $template ) ) {
                include( $template );
                exit;
            }
        }
    }
}