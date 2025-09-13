<?php
/**
 * PuzzlingCRM User Profile Handler
 *
 * This class adds custom fields to user profiles, like a dedicated phone number field,
 * removing the hard dependency on WooCommerce.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class PuzzlingCRM_User_Profile {

    /**
     * Constructor. Hooks into WordPress actions.
     */
    public function __construct() {
        // Add the custom field to user profile pages (for admins editing users)
        add_action( 'show_user_profile', [ $this, 'add_custom_user_profile_fields' ] );
        add_action( 'edit_user_profile', [ $this, 'add_custom_user_profile_fields' ] );

        // Save the custom field data
        add_action( 'personal_options_update', [ $this, 'save_custom_user_profile_fields' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save_custom_user_profile_fields' ] );
    }

    /**
     * Add the "Phone Number" field to the user profile page.
     *
     * @param WP_User $user The user object being edited.
     */
    public function add_custom_user_profile_fields( $user ) {
        ?>
        <h3><?php esc_html_e( 'PuzzlingCRM Information', 'puzzlingcrm' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="puzzling_phone_number"><?php esc_html_e( 'Phone Number', 'puzzlingcrm' ); ?></label></th>
                <td>
                    <input type="text" name="puzzling_phone_number" id="puzzling_phone_number" value="<?php echo esc_attr( get_user_meta( $user->ID, 'puzzling_phone_number', true ) ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'This phone number will be used for SMS notifications from the CRM.', 'puzzlingcrm' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the custom phone number field.
     *
     * @param int $user_id The ID of the user being saved.
     */
    public function save_custom_user_profile_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        if ( isset( $_POST['puzzling_phone_number'] ) ) {
            $phone_number = sanitize_text_field( $_POST['puzzling_phone_number'] );
            update_user_meta( $user_id, 'puzzling_phone_number', $phone_number );
        }
    }
}