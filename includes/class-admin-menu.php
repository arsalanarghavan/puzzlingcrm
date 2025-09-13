<?php
/**
 * PuzzlingCRM Admin Menu Manager
 *
 * This class handles the creation of the admin menus in the WordPress dashboard.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Admin_Menu {

    /**
     * Constructor. Hooks into the 'admin_menu' action.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_menus' ] );
        add_action( 'admin_notices', [ $this, 'show_admin_notices' ] );
    }

    /**
     * Registers all the admin menu and submenu pages for the plugin.
     * The main purpose of this menu is now to provide a clear entry point
     * to the frontend dashboard for administrators.
     */
    public function register_admin_menus() {
        // Main Menu Page that links to the frontend dashboard
        add_menu_page(
            __( 'PuzzlingCRM', 'puzzlingcrm' ),
            __( 'PuzzlingCRM', 'puzzlingcrm' ),
            'manage_options',
            'puzzling-dashboard', // Slug
            [ $this, 'render_redirect_page' ], // Callback function
            'dashicons-businesswoman',
            25
        );
    }

    /**
     * Renders a simple page for the admin that redirects them to the frontend dashboard.
     * This ensures even admins use the intended interface.
     */
    public function render_redirect_page() {
        $dashboard_url = puzzling_get_dashboard_url();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Redirecting to PuzzlingCRM Dashboard', 'puzzlingcrm' ); ?></h1>
            <p><?php esc_html_e( 'All management for PuzzlingCRM is handled through the frontend dashboard for a unified experience.', 'puzzlingcrm' ); ?></p>
            <p>
                <a href="<?php echo esc_url( $dashboard_url ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Go to Frontend Dashboard', 'puzzlingcrm' ); ?>
                </a>
            </p>
            <script type="text/javascript">
                window.location.href = '<?php echo esc_url_raw( $dashboard_url ); ?>';
            </script>
        </div>
        <?php
    }
    
    /**
     * Displays admin notices, e.g., for configuration errors or missing extensions.
     */
    public function show_admin_notices() {
        // This function remains unchanged and will show important server/config notices to the admin.
        if ( get_transient( 'puzzling_sms_not_configured' ) ) {
            $settings_url = add_query_arg(['view' => 'settings'], puzzling_get_dashboard_url());
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php esc_html_e( 'PuzzlingCRM:', 'puzzlingcrm' ); ?></strong>
                    <?php
                    printf(
                        wp_kses_post( __( 'The SMS service for sending reminders is not configured correctly. Please <a href="%s">check your settings</a>.', 'puzzlingcrm' ) ),
                        esc_url( $settings_url )
                    );
                    ?>
                </p>
            </div>
            <?php
            delete_transient( 'puzzling_sms_not_configured' );
        }

        if ( get_transient( 'puzzling_soap_not_enabled' ) ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php esc_html_e( 'PuzzlingCRM:', 'puzzlingcrm' ); ?></strong>
                    <?php esc_html_e( 'The PHP SOAP extension is not enabled on your server. This is required for the ParsGreen SMS service to function. Please contact your hosting provider to enable it.', 'puzzlingcrm' ); ?>
                </p>
            </div>
            <?php
            delete_transient( 'puzzling_soap_not_enabled' );
        }
    }
}