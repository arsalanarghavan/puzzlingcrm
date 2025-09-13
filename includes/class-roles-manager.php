<?php
class CSM_Roles_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'add_custom_roles' ] );
        add_action( 'admin_init', [ $this, 'block_dashboard_access' ] );
    }

    public function add_custom_roles() {
        // Remove roles on plugin deactivation to keep the site clean
        add_role( 'system_manager', 'مدیر سیستم', ['read' => true] );
        add_role( 'finance_manager', 'مدیر مالی', ['read' => true] );
        add_role( 'website_designer', 'طراح سایت', ['read' => true] );
        add_role( 'social_manager', 'مدیر شبکه‌های اجتماعی', ['read' => true] );
    }

    public function block_dashboard_access() {
        $user = wp_get_current_user();
        $roles_to_block = ['system_manager', 'finance_manager', 'website_designer', 'social_manager'];
        
        // Check if user has one of the blocked roles
        if ( array_intersect( $roles_to_block, $user->roles ) ) {
            // Check if it's not an AJAX request and the user is in admin area
            if ( ! defined( 'DOING_AJAX' ) && is_admin() ) {
                wp_redirect( home_url('/profile') ); // Redirect them to their profile page
                exit;
            }
        }
    }
}

// new CSM_Roles_Manager();