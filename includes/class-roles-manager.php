<?php
/**
 * PuzzlingCRM Roles and Capabilities Manager
 *
 * This class handles the creation and assignment of custom roles and capabilities.
 *
 * @package PuzzlingCRM
 */

class PuzzlingCRM_Roles_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'block_dashboard_access' ] );
    }

    /**
     * Adds custom roles and capabilities.
     * **FIXED**: Now correctly adds custom capabilities to the 'administrator' role.
     * **MODIFIED**: Grants comprehensive capabilities to 'system_manager'.
     * **FIXED**: Added 'edit_posts' capability to 'team_member' to allow task creation.
     * **FIXED**: Removed the call to remove_custom_roles() to prevent data loss on update.
     */
    public function add_custom_roles() {
        // The following line was causing the issue and has been removed.
        // $this->remove_custom_roles();

        // --- Add Custom Capabilities to Administrator ---
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('edit_tasks');
            $admin_role->add_cap('delete_tasks');
            $admin_role->add_cap('assign_tasks');
        }
        
        // --- Define Custom Roles ---

        // Finance Manager
        add_role( 'finance_manager', 'مدیر مالی', [
            'read' => true,
            'edit_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
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
            'edit_users' => true,
            'create_users' => true,
            'delete_users' => true,
            'list_users' => true,
        ] );

        // Team Member
        add_role( 'team_member', 'عضو تیم', [
            'read' => true,
            'edit_posts' => true, // **FIX**: Added capability to allow creating/editing tasks CPT.
            'edit_tasks' => true,
        ] );
    }

    /**
     * Removes the custom roles and capabilities.
     * This is called on deactivation.
     */
    public function remove_custom_roles() {
        // Remove capabilities from the administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('edit_tasks');
            $admin_role->remove_cap('delete_tasks');
            $admin_role->remove_cap('assign_tasks');
        }

        // Remove the custom roles
        remove_role('finance_manager');
        remove_role('system_manager');
        remove_role('team_member');
    }

    /**
     * Blocks direct access to the WordPress admin area for custom roles.
     * **MODIFIED**: This function is now activated and correctly redirects users.
     */
    public function block_dashboard_access() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        if ( is_admin() ) {
            $user = wp_get_current_user();
            $roles_to_block = ['finance_manager', 'system_manager', 'team_member', 'customer'];
            $user_roles = (array) $user->roles;

            $has_blocked_role = !empty( array_intersect( $roles_to_block, $user_roles ) );
            $is_super_admin = in_array('administrator', $user_roles);

            if ( $has_blocked_role && !$is_super_admin ) {
                $dashboard_page_id = get_option('puzzling_dashboard_page_id');
                $dashboard_url = $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url('/');
                
                wp_redirect( $dashboard_url );
                exit;
            }
        }
    }
}