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
     * Renders the main dashboard wrapper.
     * Shortcode: [puzzling_dashboard]
     */
    public static function render_dashboard() {
        return self::render_partial('dashboard-wrapper.php');
    }

    // --- System Manager Render Functions ---

    public static function render_sm_contracts() {
        return self::render_partial('form-new-contract.php', ['system_manager']);
    }

    public static function render_sm_settings() {
        return self::render_partial('dashboard-settings.php', ['system_manager']);
    }
    
    // --- Finance Manager Render Functions ---

    public static function render_fm_dashboard() {
        return self::render_partial('dashboard-finance.php', ['finance_manager']);
    }

    // --- Team Member Render Functions ---

    public static function render_tm_tasks() {
        return self::render_partial('dashboard-team-member.php', ['team_member']);
    }

    // --- Client Render Functions ---

    public static function render_client_dashboard() {
        return self::render_partial('dashboard-client.php', ['customer']);
    }

    public static function render_client_projects() {
        return self::render_partial('list-projects.php', ['customer']);
    }

    public static function render_client_payments() {
        $render_logic = function() {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/dashboard-client.php';
        };
        // This is a simplified approach. For true separation, the payment table logic
        // from 'dashboard-client.php' would be extracted into its own file or function.
        // For now, we render the whole client dashboard for this shortcode as well.
        // A better implementation would be to create a new partial just for payments.
        return self::render_partial('dashboard-client.php', ['customer']);
    }
}