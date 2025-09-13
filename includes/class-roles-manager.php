<?php
class PuzzlingCRM_Roles_Manager {

    public function __construct() {
        // The add_custom_roles is called from the installer on activation
        add_action( 'admin_init', [ $this, 'block_dashboard_access' ] );
    }

    /**
     * Adds custom user roles with specific capabilities.
     */
    public function add_custom_roles() {
        // Helper function to remove roles if needed (e.g., on deactivation)
        // remove_role('finance_manager');

        // Finance Manager: Can read projects but primarily manages contracts
        add_role( 'finance_manager', 'مدیر مالی', [
            'read' => true,
            'edit_posts' => false,
            // Custom capabilities can be added for more granular control
        ] );
        
        // System Manager: Manages projects and tasks
        add_role( 'system_manager', 'مدیر سیستم', [
            'read' => true,
            'edit_posts' => true, // Can edit their own projects/tasks
        ] );
        
        // Team Member: Can only view assigned content and edit their tasks
        add_role( 'team_member', 'عضو تیم', [
            'read' => true,
            'edit_posts' => false,
        ] );
    }
    
    /**
     * Blocks access to the WordPress admin dashboard for custom roles.
     */
    public function block_dashboard_access() {
        // Don't block for AJAX requests
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        $user = wp_get_current_user();
        $roles_to_block = ['finance_manager', 'system_manager', 'team_member', 'customer'];

        $user_roles = (array) $user->roles;
        
        // Check if the user has any of the blocked roles
        if ( ! empty( array_intersect( $roles_to_block, $user_roles ) ) ) {
            // Check if the user is trying to access any admin page
            if ( is_admin() ) {
                $dashboard_page = get_page_by_title('PuzzlingCRM Dashboard');
                if ($dashboard_page) {
                    wp_redirect( get_permalink($dashboard_page->ID) );
                } else {
                    wp_redirect( home_url() ); // Fallback to homepage
                }
                exit;
            }
        }
    }
}