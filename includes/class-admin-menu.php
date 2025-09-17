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
            'puzzling-crm-info', // Slug changed to avoid conflict
            [ $this, 'render_info_page' ], // Callback function changed
            'dashicons-businesswoman',
            25
        );
    }

    /**
     * HIGHLIGHT: Renders a simple info page instead of redirecting.
     * This ensures even admins use the intended interface.
     */
    public function render_info_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Welcome to PuzzlingCRM', 'puzzlingcrm' ); ?></h1>
            <p><?php esc_html_e( 'All management for PuzzlingCRM is handled through shortcodes on the front-end of your site.', 'puzzlingcrm' ); ?></p>
            <p><?php esc_html_e( 'You can place the shortcodes on any page you like to build your own dashboard.', 'puzzlingcrm' ); ?></p>
            
            <h2><?php esc_html_e( 'Available Shortcodes', 'puzzlingcrm' ); ?></h2>
            <ul>
                <li><code>[puzzling_projects]</code> - Displays projects for managers or clients.</li>
                <li><code>[puzzling_contracts]</code> - Displays contracts for managers or clients.</li>
                <li><code>[puzzling_invoices]</code> - Displays invoices/payments for managers or clients.</li>
                <li><code>[puzzling_pro_invoices]</code> - Displays pro-forma invoices for managers or clients.</li>
                <li><code>[puzzling_appointments]</code> - Displays appointments for managers or clients.</li>
                <li><code>[puzzling_tickets]</code> - Displays support tickets.</li>
                <li><code>[puzzling_tasks]</code> - Displays the task manager for managers and team members.</li>
                <li><code>[puzzling_customers]</code> - (Manager Only) Customer management page.</li>
                <li><code>[puzzling_staff]</code> - (Manager Only) Staff management page.</li>
                <li><code>[puzzling_subscriptions]</code> - (Manager Only) WooCommerce Subscriptions page.</li>
                <li><code>[puzzling_reports]</code> - (Manager Only) Reports page.</li>
                <li><code>[puzzling_settings]</code> - (Manager Only) Settings page.</li>
                <li><code>[puzzling_logs]</code> - (Manager Only) System logs page.</li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Displays admin notices, e.g., for configuration errors or missing extensions.
     */
    public function show_admin_notices() {
        // This function remains unchanged and will show important server/config notices to the admin.
        if ( get_transient( 'puzzling_sms_not_configured' ) ) {
            $settings_url = add_query_arg(['page' => 'puzzling-crm-info']); // Point to our new info page
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