<?php
/**
 * PuzzlingCRM User Profile Handler
 *
 * This class adds custom fields to user profiles, like a dedicated phone number field,
 * removing the hard dependency on WooCommerce. It also handles phone number synchronization.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class PuzzlingCRM_User_Profile {

    /**
     * @var string[] List of phone number meta keys to sync.
     */
    private $phone_meta_keys = ['puzzling_phone_number', 'wpyarud_phone', 'user_phone_number', 'billing_phone'];

    /**
     * Constructor. Hooks into WordPress actions.
     */
    public function __construct() {
        // Add the custom field to user profile pages (for admins editing users)
        add_action( 'show_user_profile', [ $this, 'add_custom_user_profile_fields' ] );
        add_action( 'edit_user_profile', [ $this, 'add_custom_user_profile_fields' ] );

        // Save the custom field data and sync it
        add_action( 'personal_options_update', [ $this, 'save_and_sync_user_profile_fields' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save_and_sync_user_profile_fields' ] );
        
        // Sync on new user registration
        add_action( 'user_register', [ $this, 'sync_on_registration' ] );
        
        // One-time sync for all existing users
        add_action( 'admin_init', [ $this, 'run_one_time_sync_for_all_users' ] );
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
                    <p class="description"><?php esc_html_e( 'This phone number will be used for SMS notifications and will be synced across all phone fields.', 'puzzlingcrm' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the custom phone number field and sync it across all defined fields.
     *
     * @param int $user_id The ID of the user being saved.
     */
    public function save_and_sync_user_profile_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        $phone_number_to_sync = null;

        // Find the phone number from the submitted form data
        foreach ($this->phone_meta_keys as $key) {
            if ( isset( $_POST[$key] ) && ! empty( $_POST[$key] ) ) {
                $phone_number_to_sync = sanitize_text_field( $_POST[$key] );
                break;
            }
        }
        
        if ( $phone_number_to_sync !== null ) {
            $this->sync_phone_for_user( $user_id, $phone_number_to_sync );
        }
    }
    
    /**
     * Syncs phone number for newly registered users.
     *
     * @param int $user_id The ID of the newly registered user.
     */
    public function sync_on_registration( $user_id ) {
        $this->save_and_sync_user_profile_fields( $user_id );
    }

    /**
     * Central function to update all phone meta keys for a user.
     *
     * @param int $user_id The user's ID.
     * @param string $phone_number The phone number to save.
     */
    private function sync_phone_for_user( $user_id, $phone_number ) {
        foreach ( $this->phone_meta_keys as $key ) {
            update_user_meta( $user_id, $key, $phone_number );
        }
    }
    
    /**
     * Runs a one-time process to sync phone numbers for all existing users.
     * This will only run once.
     */
    public function run_one_time_sync_for_all_users() {
        if ( get_option('puzzling_phone_sync_completed') ) {
            return;
        }

        $users = get_users(['fields' => 'ID']);
        foreach ($users as $user_id) {
            $phone_to_sync = null;
            // Find the first available phone number for the user
            foreach ($this->phone_meta_keys as $key) {
                $phone = get_user_meta($user_id, $key, true);
                if (!empty($phone)) {
                    $phone_to_sync = $phone;
                    break;
                }
            }

            // If a phone number was found, sync it
            if ($phone_to_sync) {
                $this->sync_phone_for_user($user_id, $phone_to_sync);
            }
        }

        // Mark the sync as complete to prevent it from running again
        update_option('puzzling_phone_sync_completed', true);
    }
}