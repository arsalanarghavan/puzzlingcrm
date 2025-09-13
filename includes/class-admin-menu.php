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
    }

    /**
     * Registers all the admin menu and submenu pages for the plugin.
     */
    public function register_admin_menus() {
        // Main Menu Page
        add_menu_page(
            __( 'PuzzlingCRM', 'puzzlingcrm' ),
            __( 'PuzzlingCRM', 'puzzlingcrm' ),
            'manage_options',
            'puzzling-dashboard',
            [ $this, 'render_dashboard_page' ],
            'dashicons-businesswoman',
            25
        );

        // Submenu Pages
        add_submenu_page( 'puzzling-dashboard', __( 'Dashboard', 'puzzlingcrm' ), __( 'Dashboard', 'puzzlingcrm' ), 'manage_options', 'puzzling-dashboard', [ $this, 'render_dashboard_page' ] );
        add_submenu_page( 'puzzling-dashboard', __( 'Projects', 'puzzlingcrm' ), __( 'Projects', 'puzzlingcrm' ), 'manage_options', 'edit.php?post_type=project' );
        add_submenu_page( 'puzzling-dashboard', __( 'Tasks', 'puzzlingcrm' ), __( 'Tasks', 'puzzlingcrm' ), 'manage_options', 'edit.php?post_type=task' );
        add_submenu_page( 'puzzling-dashboard', __( 'Contracts', 'puzzlingcrm' ), __( 'Contracts', 'puzzlingcrm' ), 'manage_options', 'edit.php?post_type=contract' );
        add_submenu_page( 'puzzling-dashboard', __( 'Customers', 'puzzlingcrm' ), __( 'Customers', 'puzzlingcrm' ), 'manage_options', 'users.php?role=customer' );
        add_submenu_page( 'puzzling-dashboard', __( 'Staff', 'puzzlingcrm' ), __( 'Staff', 'puzzlingcrm' ), 'manage_options', 'users.php?role__in[]=system_manager&role__in[]=finance_manager&role__in[]=team_member' );
        add_submenu_page( 'puzzling-dashboard', __( 'Settings', 'puzzlingcrm' ), __( 'Settings', 'puzzlingcrm' ), 'manage_options', 'puzzling-settings', [ $this, 'render_settings_page' ] );
    }

    /**
     * Renders the main dashboard page for the admin area.
     */
    public function render_dashboard_page() {
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/admin/page-dashboard.php';
    }

    /**
     * Renders the settings page for the admin area.
     */
    public function render_settings_page() {
        include PUZZlingCRM_PLUGIN_DIR . 'templates/admin/page-settings.php';
    }
}