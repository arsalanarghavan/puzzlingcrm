<?php
/**
 * PuzzlingCRM Frontend Dashboard Handler (Shortcode Renderer)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Frontend_Dashboard {

    /**
     * Helper function to render a partial template with role checking.
     */
    private static function render_partial($template_name, $allowed_roles = []) {
        ob_start();

        if ( ! is_user_logged_in() ) {
            echo '<div class="puzzling-login-prompt"><p>برای دسترسی به این بخش، لطفاً ابتدا وارد حساب کاربری خود شوید.</p>';
            wp_login_form();
            echo '</div>';
        } else {
            $user = wp_get_current_user();
            $user_roles = (array) $user->roles;
            $allowed_roles[] = 'administrator'; // Admins can see everything
            $allowed_roles = array_unique($allowed_roles);

            if ( count(array_intersect($allowed_roles, $user_roles)) > 0 ) {
                $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $template_name . '.php';
                if ( file_exists( $template_path ) ) {
                    include $template_path;
                } else {
                    echo '<div class="pzl-alert pzl-alert-error">خطای سیستمی: فایل قالب داشبورد یافت نشد. (' . esc_html($template_name) . ')</div>';
                }
            } else {
                 echo '<p>شما دسترسی لازم برای مشاهده این صفحه را ندارید.</p>';
            }
        }
        return ob_get_clean();
    }

    // --- Wrapper ---
    public static function render_dashboard_wrapper() {
        return self::render_partial('dashboard-wrapper', ['customer', 'team_member', 'finance_manager', 'system_manager']);
    }

    // --- System Manager Pages ---
    public static function render_page_customers() { return self::render_partial('page-customers', ['system_manager']); }
    public static function render_page_staff() { return self::render_partial('page-staff', ['system_manager']); }
    public static function render_page_projects() { return self::render_partial('page-projects', ['system_manager']); }
    public static function render_page_contracts() { return self::render_partial('page-contracts', ['system_manager']); }
    public static function render_page_tasks() { return self::render_partial('page-tasks', ['system_manager']); }
    public static function render_page_subscriptions() { return self::render_partial('page-subscriptions', ['system_manager']); }
    public static function render_page_appointments() { return self::render_partial('page-appointments', ['system_manager']); }
    public static function render_page_reports() { return self::render_partial('page-reports', ['system_manager', 'finance_manager']); }
    public static function render_page_settings() { return self::render_partial('page-settings', ['system_manager']); }
    public static function render_page_logs() { return self::render_partial('page-logs', ['system_manager']); }
    public static function render_page_tickets() { return self::render_partial('list-tickets', ['system_manager']); }
    public static function render_page_pro_invoices() { return self::render_partial('page-pro-invoices', ['system_manager']); }


    // --- Team Member Pages ---
    public static function render_team_tasks() {
        return self::render_partial('dashboard-team-member', ['team_member', 'system_manager']);
    }

    // --- Client Pages ---
    public static function render_client_dashboard() { return self::render_partial('dashboard-client', ['customer']); }
    public static function render_client_projects() { return self::render_partial('list-projects', ['customer']); }
    public static function render_client_invoices() { return self::render_partial('list-client-payments', ['customer']); }
    public static function render_client_tickets() { return self::render_partial('list-tickets', ['customer']); }
    public static function render_client_appointments() { return self::render_partial('page-client-appointments', ['customer']); }
    public static function render_client_contracts() { return self::render_partial('page-client-contracts', ['customer']); }
    public static function render_client_pro_invoices() { return self::render_partial('page-client-pro-invoices', ['customer']); }

}