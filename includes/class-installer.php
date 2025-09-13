<?php
/**
 * PuzzlingCRM Installer
 *
 * This class handles all the tasks that need to be run when the plugin is activated.
 *
 * @package PuzzlingCRM
 */

class PuzzlingCRM_Installer {

    /**
     * Activation hook callback.
     */
    public static function activate() {
        // Call role creation
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-roles-manager.php';
        $roles_manager = new PuzzlingCRM_Roles_Manager();
        $roles_manager->add_custom_roles();

        // Call CPT registration to make them available
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cpt-manager.php';
        $cpt_manager = new PuzzlingCRM_CPT_Manager();
        $cpt_manager->register_post_types();
        $cpt_manager->register_taxonomies();
        
        // Create default terms for taxonomies
        PuzzlingCRM_CPT_Manager::create_default_terms();

        // Flush rewrite rules to make CPT URLs work correctly
        flush_rewrite_rules();

        // Create the frontend dashboard page and store its ID
        self::create_dashboard_page();
    }

    /**
     * Creates the frontend dashboard page if it doesn't exist
     * and stores its ID in the options table.
     */
    private static function create_dashboard_page() {
        $dashboard_page_id = get_option('puzzling_dashboard_page_id', 0);

        // Check if the page exists and is published
        if ( $dashboard_page_id && get_post_status($dashboard_page_id) === 'publish' ) {
            return;
        }

        // Create the page
        $page_id = wp_insert_post([
            'post_title'    => 'PuzzlingCRM Dashboard',
            'post_content'  => '[puzzling_dashboard]',
            'post_status'   => 'publish',
            'post_author'   => 1, // Assign to the main admin
            'post_type'     => 'page',
        ]);

        if ($page_id > 0 && !is_wp_error($page_id)) {
            // Store the page ID so we can retrieve it later reliably
            update_option('puzzling_dashboard_page_id', $page_id);
        }
    }
}