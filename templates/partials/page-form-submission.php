<?php
/**
 * Template for customer to fill out the form after purchase.
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Get data from URL
$form_id = intval( get_query_var( 'puzzling_form_id' ) );
$token = sanitize_key( get_query_var( 'token' ) );
$form = get_post( $form_id );

// Security check: Validate the token
$token_data = get_post_meta( $form_id, '_automation_token_' . $token, true );

if ( ! $form || $form->post_type !== 'pzl_form' || empty( $token_data ) ) {
    wp_die( __( 'لینک نامعتبر است یا منقضی شده.', 'puzzlingcrm' ) );
}

// Security: Ensure only the correct customer can view this form
if ( ! is_user_logged_in() || get_current_user_id() != $token_data['customer_id'] ) {
     wp_die( __( 'برای دسترسی به این صفحه باید وارد حساب کاربری خود شوید.', 'puzzlingcrm' ) );
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="puzzling-form-wrapper" style="max-width: 800px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
            
            <h1 class="form-title"><?php echo esc_html( $form->post_title ); ?></h1>
            <div class="form-description" style="margin-bottom: 20px;">
                <?php echo wp_kses_post( wpautop( $form->post_content ) ); ?>
            </div>

            <form id="puzzling-automation-form" method="post" class="pzl-form">
                <?php
                $form_fields = get_post_meta( $form_id, '_form_fields', true );
                if ( ! empty( $form_fields ) && is_array( $form_fields ) ) {
                    foreach ( $form_fields as $field ) {
                        $field_id = 'pzl_field_' . esc_attr( sanitize_key( $field['label'] ) );
                        echo '<div class="form-group">';
                        echo '<label for="' . $field_id . '">' . esc_html( $field['label'] ) . '</label>';
                        echo '<input type="text" id="' . $field_id . '" name="form_fields[' . esc_attr( $field['label'] ) . ']" class="pzl-input" ' . ( $field['required'] ? 'required' : '' ) . '>';
                        echo '</div>';
                    }
                }
                ?>
                
                <input type="hidden" name="puzzling_action" value="submit_automation_form">
                <input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">
                <input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">
                <?php wp_nonce_field( 'puzzling_submit_automation_form_' . $token, '_wpnonce' ); ?>

                <div class="form-submit">
                    <button type="submit" class="pzl-button"><?php _e( 'ارسال و ایجاد پروژه', 'puzzlingcrm' ); ?></button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php
get_footer();