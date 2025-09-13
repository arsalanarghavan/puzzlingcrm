<?php
class PuzzlingCRM_Roles_Manager {

    public function __construct() {
        add_action( 'admin_init', [ $this, 'block_dashboard_access' ] );
    }

    public function add_custom_roles() {
        // Remove existing roles to ensure capabilities are updated
        remove_role('finance_manager');
        remove_role('system_manager');
        remove_role('team_member');

        // Finance Manager
        add_role( 'finance_manager', 'مدیر مالی', [
            'read' => true,
            'edit_posts' => false,
            'view_puzzling_dashboard' => true,
        ] );

        // System Manager
        add_role( 'system_manager', 'مدیر سیستم', [
            'read' => true,
            'edit_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'manage_options' => true, // To access settings
            'edit_tasks' => true,
            'delete_tasks' => true,
            'assign_tasks' => true,
            'view_puzzling_dashboard' => true,
        ] );

        // Team Member: Added specific capabilities for tasks
        add_role( 'team_member', 'عضو تیم', [
            'read' => true,
            'edit_posts' => false,
            'edit_tasks' => true,
            'publish_tasks' => true,
            'edit_published_tasks' => true,
            'delete_tasks' => true,
            'delete_published_tasks' => true,
            'view_puzzling_dashboard' => true,
        ] );
    }

    public function block_dashboard_access() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        $user = wp_get_current_user();
        $roles_to_block = ['finance_manager', 'system_manager', 'team_member', 'customer'];

        $user_roles = (array) $user->roles;

        if ( ! empty( array_intersect( $roles_to_block, $user_roles ) ) ) {
            if ( is_admin() && ! current_user_can('manage_options') ) {
                $dashboard_page = get_page_by_title('PuzzlingCRM Dashboard');
                if ($dashboard_page) {
                    wp_redirect( get_permalink($dashboard_page->ID) );
                } else {
                    wp_redirect( home_url() );
                }
                exit;
            }
        }
    }
}