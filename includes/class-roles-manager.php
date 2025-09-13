<?php
class PuzzlingCRM_Roles_Manager {

    public function __construct() {
        // This hook runs earlier and is more reliable for blocking
        add_action( 'init', [ $this, 'block_dashboard_access' ] );
    }

    public function add_custom_roles() {
        // Remove existing roles to ensure capabilities are updated
        $this->remove_custom_roles();

        // Finance Manager
        add_role( 'finance_manager', 'مدیر مالی', [
            'read' => true,
            'edit_posts' => true, // <-- دسترسی ویرایش قراردادها و سایر پست‌ها
            'publish_posts' => true, // <-- دسترسی انتشار
            'delete_posts' => true, // <-- دسترسی حذف
        ] );

        // System Manager (Can do almost everything)
        add_role( 'system_manager', 'مدیر سیستم', [
            'read' => true,
            'edit_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'manage_options' => true, // Needed for many core functions
            'edit_tasks' => true,
            'delete_tasks' => true,
            'assign_tasks' => true,
        ] );

        // Team Member
        add_role( 'team_member', 'عضو تیم', [
            'read' => true,
            'edit_tasks' => true,
        ] );
    }

    /**
     * Removes the custom roles.
     * This is called on deactivation.
     */
    public function remove_custom_roles() {
        remove_role('finance_manager');
        remove_role('system_manager');
        remove_role('team_member');
    }

    public function block_dashboard_access() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        // Redirect any user with these roles if they try to access wp-admin
        if ( is_admin() ) {
            $user = wp_get_current_user();
            $roles_to_block = ['finance_manager', 'system_manager', 'team_member', 'customer'];
            $user_roles = (array) $user->roles;

            // Check if the user has any of the blocked roles
            $has_blocked_role = !empty( array_intersect( $roles_to_block, $user_roles ) );

            // The ONLY exception is for the main administrator (super admin)
            $is_super_admin = in_array('administrator', $user_roles);

            if ( $has_blocked_role && !$is_super_admin ) {
                $dashboard_page_id = get_option('puzzling_dashboard_page_id');
                if ($dashboard_page_id) {
                    wp_redirect( get_permalink($dashboard_page_id) );
                } else {
                    // Fallback to home URL if dashboard page doesn't exist
                    wp_redirect( home_url() );
                }
                exit;
            }
        }
    }
}