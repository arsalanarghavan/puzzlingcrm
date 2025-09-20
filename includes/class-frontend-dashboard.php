<?php
/**
 * PuzzlingCRM Frontend Dashboard Handler (Shortcode Renderer)
 * This class uses role-aware methods to render the correct templates.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Frontend_Dashboard {

    /**
     * Helper to get current user's primary CRM role.
     * @return string The user's role.
     */
    private static function get_user_role() {
        if ( ! is_user_logged_in() ) {
            return 'guest';
        }
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        
        if ( in_array('administrator', $roles) || in_array('system_manager', $roles) ) {
            return 'system_manager';
        }
        if ( in_array('finance_manager', $roles) ) {
            return 'finance_manager';
        }
        if ( in_array('team_member', $roles) ) {
            return 'team_member';
        }
        if ( in_array('customer', $roles) ) {
            return 'customer';
        }
        return 'guest';
    }

    /**
     * Helper function to render a partial template.
     */
    private static function render_partial($template_name) {
        ob_start();
        $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $template_name . '.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="pzl-alert pzl-alert-error">خطای سیستمی: فایل قالب یافت نشد: ' . esc_html($template_name) . '</div>';
        }
        return ob_get_clean();
    }

    /**
     * Renders the main dashboard based on user role.
     * Shortcode: [puzzling_dashboard]
     */
    public static function render_dashboard() {
        if ( ! is_user_logged_in() ) {
            return self::render_partial('common/login-prompt');
        }

        // Handle my_profile view for all users
        if (isset($_GET['view']) && $_GET['view'] === 'my_profile') {
            return self::render_partial('page-my-profile');
        }

        switch ( self::get_user_role() ) {
            case 'system_manager':
                return self::render_partial('dashboard-system-manager');
            case 'finance_manager':
                return self::render_partial('dashboard-finance');
            case 'team_member':
                return self::render_partial('dashboard-team-member');
            case 'customer':
                // For customers, the main dashboard shortcode renders the full tabbed client interface.
                return self::render_partial('dashboard-client');
            default:
                return '<p>' . __('You do not have a defined role to view a dashboard.', 'puzzlingcrm') . '</p>';
        }
    }

    /**
     * Renders projects page based on user role.
     * Shortcode: [puzzling_projects]
     */
    public static function render_projects() {
        if ( ! is_user_logged_in() ) return self::render_partial('common/login-prompt');
        $role = self::get_user_role();

        if ( $role === 'system_manager' ) {
            return self::render_partial('page-projects');
        } elseif ( $role === 'customer' ) {
            return self::render_partial('list-projects');
        } elseif ( $role === 'team_member' ) {
            return self::render_partial('list-team-member-projects');
        } else {
            return '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>';
        }
    }

    /**
     * Renders contracts page based on user role.
     * Shortcode: [puzzling_contracts]
     */
    public static function render_contracts() {
        if ( ! is_user_logged_in() ) return self::render_partial('common/login-prompt');
        $role = self::get_user_role();

        if ( $role === 'system_manager' || $role === 'finance_manager' ) {
            return self::render_partial('page-contracts');
        } elseif ( $role === 'customer' ) {
            return self::render_partial('page-client-contracts');
        } else {
            return '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>';
        }
    }

    /**
     * Renders invoices/payments page based on user role.
     * Shortcode: [puzzling_invoices]
     */
    public static function render_invoices() {
        if ( ! is_user_logged_in() ) return self::render_partial('common/login-prompt');
        $role = self::get_user_role();

        if ( $role === 'system_manager' || $role === 'finance_manager' ) {
            return self::render_partial('common/payments-table');
        } elseif ( $role === 'customer' ) {
            return self::render_partial('list-client-payments');
        } else {
            return '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>';
        }
    }

    /**
     * Renders pro-forma invoices page based on user role.
     * Shortcode: [puzzling_pro_invoices]
     */
    public static function render_pro_invoices() {
        if ( ! is_user_logged_in() ) return self::render_partial('common/login-prompt');
        $role = self::get_user_role();

        if ( $role === 'system_manager' || $role === 'finance_manager' ) {
            return self::render_partial('page-pro-invoices');
        } elseif ( $role === 'customer' ) {
            return self::render_partial('page-client-pro-invoices');
        } else {
            return '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>';
        }
    }

    /**
     * Renders appointments page based on user role.
     * Shortcode: [puzzling_appointments]
     */
    public static function render_appointments() {
        if ( ! is_user_logged_in() ) return self::render_partial('common/login-prompt');
        $role = self::get_user_role();

        if ( $role === 'system_manager' ) {
            return self::render_partial('page-appointments');
        } elseif ( $role === 'customer' ) {
            return self::render_partial('page-client-appointments');
        } else {
            return '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>';
        }
    }

    /**
     * Renders tickets page based on user role.
     * Shortcode: [puzzling_tickets]
     */
    public static function render_tickets() {
        if ( ! is_user_logged_in() ) return self::render_partial('common/login-prompt');
        $role = self::get_user_role();

        if ( $role === 'system_manager' || $role === 'customer' ) {
            return self::render_partial('list-tickets');
        } else {
            return '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>';
        }
    }

    /**
     * Renders tasks page based on user role.
     * Shortcode: [puzzling_tasks]
     * **FIX**: Team members now see the full task page, just like admins.
     */
    public static function render_tasks() {
        if ( ! is_user_logged_in() ) return self::render_partial('common/login-prompt');
        $role = self::get_user_role();

        if ( $role === 'system_manager' || $role === 'team_member' ) {
            return self::render_partial('page-tasks');
        } else {
            return '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>';
        }
    }

    // --- Manager-only Shortcode Renderers ---
    public static function render_page_customers() { return self::get_user_role() === 'system_manager' ? self::render_partial('page-customers') : '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>'; }
    public static function render_page_staff() { return self::get_user_role() === 'system_manager' ? self::render_partial('page-staff') : '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>'; }
    public static function render_page_subscriptions() { return self::get_user_role() === 'system_manager' ? self::render_partial('page-subscriptions') : '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>'; }
    public static function render_page_reports() { $role = self::get_user_role(); return ($role === 'system_manager' || $role === 'finance_manager') ? self::render_partial('page-reports') : '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>'; }
    public static function render_page_settings() { return self::get_user_role() === 'system_manager' ? self::render_partial('page-settings') : '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>'; }
    public static function render_page_logs() { return self::get_user_role() === 'system_manager' ? self::render_partial('page-logs') : '<p>' . __('You do not have permission to view this page.', 'puzzlingcrm') . '</p>'; }
}