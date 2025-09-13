<?php
/**
 * PuzzlingCRM Frontend Dashboard Handler
 *
 * This class is responsible for rendering the main dashboard and its partials via shortcodes.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PuzzlingCRM_Frontend_Dashboard {

    /**
     * Helper function to render a specific dashboard partial.
     * It handles user login and role checks.
     *
     * @param string $template_name The name of the template file to load from the 'partials' directory.
     * @param array $allowed_roles The roles allowed to see this dashboard.
     * @param callable|null $render_function A callback function to render content directly if no template file is needed.
     * @return string The HTML content of the dashboard partial.
     */
    private static function render_partial($template_name = '', $allowed_roles = [], $render_function = null) {
        ob_start();

        if ( ! is_user_logged_in() ) {
            echo '<div class="puzzling-login-prompt">';
            echo '<p>برای دسترسی به این بخش، لطفاً ابتدا وارد حساب کاربری خود شوید.</p>';
            wp_login_form();
            echo '</div>';
        } else {
            $user = wp_get_current_user();
            $user_roles = (array) $user->roles;

            // Allow admins to see everything
            $allowed_roles[] = 'administrator';
            $allowed_roles = array_unique($allowed_roles);

            if ( count(array_intersect($allowed_roles, $user_roles)) === 0 ) {
                 echo '<p>شما دسترسی لازم برای مشاهده این صفحه را ندارید.</p>';
            } else {
                if ( is_callable($render_function) ) {
                    // If a render function is provided, call it
                    call_user_func($render_function);
                } elseif ( !empty($template_name) ) {
                    // Otherwise, include the template file
                    $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $template_name;

                    if ( file_exists( $template_path ) ) {
                        include $template_path;
                    } else {
                        echo '<p>خطای سیستمی: فایل قالب داشبورد یافت نشد.</p>';
                    }
                }
            }
        }

        return ob_get_clean();
    }

    /**
     * Renders the main dashboard wrapper and enqueues assets.
     * Shortcode: [puzzling_dashboard]
     */
    public static function render_dashboard() {
        // Enqueue assets only when this shortcode is called.
        PuzzlingCRM::enqueue_dashboard_assets();

        ob_start();
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/dashboard-wrapper.php';
        return ob_get_clean();
    }

    // --- System Manager Render Functions ---

    public static function render_sm_manage_projects() {
        return self::render_partial('manage-projects.php', ['system_manager', 'administrator']);
    }

    public static function render_sm_contracts() {
        return self::render_partial('common/contract-form.php', ['system_manager', 'administrator']);
    }

    public static function render_settings_payment() {
        return self::render_partial('settings-payment.php', ['system_manager', 'administrator']);
    }
    
    public static function render_settings_sms() {
        return self::render_partial('settings-sms.php', ['system_manager', 'administrator']);
    }
    
    // --- Finance Manager Render Functions ---

    public static function render_fm_dashboard() {
        return self::render_partial('dashboard-finance.php', ['finance_manager', 'administrator']);
    }

    // --- Team Member Render Functions ---

    public static function render_tm_tasks() {
        return self::render_partial('dashboard-team-member.php', ['team_member', 'system_manager', 'administrator']);
    }

    // --- Client Render Functions ---

    public static function render_client_dashboard() {
        return self::render_partial('dashboard-client.php', ['customer']);
    }

    public static function render_client_projects() {
        return self::render_partial('list-projects.php', ['customer']);
    }

    public static function render_client_payments() {
        return self::render_partial('common/payments-table.php', ['customer']);
    }
}