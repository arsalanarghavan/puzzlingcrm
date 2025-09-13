<?php
class PuzzlingCRM_Installer {

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
        
        // ** ADDED THIS LINE **
        // Create default terms for taxonomies
        PuzzlingCRM_CPT_Manager::create_default_terms();

        // Flush rewrite rules to make CPT URLs work correctly
        flush_rewrite_rules();

        // Create necessary pages (e.g., Dashboard page)
        if ( null == get_page_by_title( 'PuzzlingCRM Dashboard' ) ) {
            $page_id = wp_insert_post([
                'post_title'    => 'PuzzlingCRM Dashboard',
                'post_content'  => '[puzzling_dashboard]',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'page',
            ]);
        }
    }
}