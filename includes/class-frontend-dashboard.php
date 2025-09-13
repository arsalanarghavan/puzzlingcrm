<?php
/**
 * PuzzlingCRM Frontend Dashboard Handler
 *
 * This class is responsible for rendering the main dashboard via a shortcode.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PuzzlingCRM_Frontend_Dashboard {

    /**
     * Renders the main dashboard by including the wrapper template.
     * This is the callback function for the [puzzling_dashboard] shortcode.
     *
     * @return string The HTML content of the dashboard.
     */
    public static function render_dashboard() {
        // Start output buffering to capture the HTML from the template file.
        ob_start();

        // Check if the user is logged in.
        if ( ! is_user_logged_in() ) {
            // You can either show a login form or a simple message.
            echo '<div class="puzzling-login-prompt">';
            echo '<p>برای دسترسی به داشبورد، لطفاً ابتدا وارد حساب کاربری خود شوید.</p>';
            // Optionally, include the default WordPress login form.
            wp_login_form();
            echo '</div>';
        } else {
            // If the user is logged in, load the main dashboard wrapper template.
            // The wrapper template itself contains the logic to load the correct partial
            // based on the user's role.
            $dashboard_template = PUZZLINGCRM_PLUGIN_DIR . 'templates/dashboard-wrapper.php';

            if ( file_exists( $dashboard_template ) ) {
                include $dashboard_template;
            } else {
                echo '<p>خطای سیستمی: فایل قالب اصلی داشبورد یافت نشد.</p>';
            }
        }

        // Return the buffered content.
        return ob_get_clean();
    }
}