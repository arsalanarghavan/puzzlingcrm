<?php
/**
 * PuzzlingCRM Installer
 *
 * This class handles all the tasks that need to be run when the plugin is activated or deactivated.
 *
 * @package PuzzlingCRM
 */

class PuzzlingCRM_Installer {

    /**
     * Activation hook callback.
     */
    public static function activate() {
        // Call role creation
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-roles-manager.php';
        $roles_manager = new PuzzlingCRM_Roles_Manager();
        $roles_manager->add_custom_roles();

        // Call CPT registration to make them available
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cpt-manager.php';
        $cpt_manager = new PuzzlingCRM_CPT_Manager();
        $cpt_manager->register_post_types();
        $cpt_manager->register_taxonomies();
        
        // Create default terms for taxonomies
        PuzzlingCRM_CPT_Manager::create_default_terms();

        // Flush rewrite rules to make CPT URLs work correctly
        flush_rewrite_rules();

        // HIGHLIGHT: The creation of the dashboard page is now disabled.
        // self::create_dashboard_page();

        // ایجاد تنظیمات پیش‌فرض استایل
        self::create_default_style_settings();
    }

    /**
     * ایجاد تنظیمات پیش‌فرض استایل در زمان نصب
     */
    private static function create_default_style_settings() {
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-settings-handler.php';
        
        $existing_settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        
        // اگر تنظیمات استایل وجود نداشت، ایجاد کن
        if (!isset($existing_settings['style'])) {
            $default_style = [
                'logo_desktop' => PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/desktop-logo.png',
                'logo_mobile' => PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/toggle-logo.png',
                'logo_dark' => PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/desktop-dark.png',
                'logo_favicon' => PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/favicon.ico',
                'primary_color' => '#6366f1',
                'secondary_color' => '#6c757d',
                'success_color' => '#10b981',
                'danger_color' => '#ef4444',
                'warning_color' => '#f59e0b',
                'info_color' => '#3b82f6',
                'menu_bg_color' => '#1e293b',
                'header_bg_color' => '#ffffff',
                'body_font' => 'Vazirmatn',
                'heading_font' => 'Vazirmatn',
                'font_size_base' => 14,
                'theme_mode' => 'light',
                'menu_style' => 'dark',
                'header_style' => 'light',
                'sidebar_layout' => 'default',
            ];
            
            $existing_settings['style'] = $default_style;
            PuzzlingCRM_Settings_Handler::update_settings($existing_settings);
        }
    }
    
    /**
     * Deactivation hook callback.
     * Cleans up roles created by the plugin.
     */
    public static function deactivate() {
        // Call role removal
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-roles-manager.php';
        $roles_manager = new PuzzlingCRM_Roles_Manager();
        $roles_manager->remove_custom_roles();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Creates the frontend dashboard page if it doesn't exist
     * and stores its ID in the options table.
     * HIGHLIGHT: This function is no longer called on activation.
     */
    private static function create_dashboard_page() {
        $dashboard_page_id = get_option('puzzling_dashboard_page_id', 0);

        // Check if the page exists and is published
        if ( $dashboard_page_id && get_post_status($dashboard_page_id) === 'publish' ) {
            return;
        }

        // Create the page
        $page_id = wp_insert_post([
            'post_title'    => 'PuzzlingCRM Dashboard',
            'post_content'  => '[puzzling_dashboard]',
            'post_status'   => 'publish',
            'post_author'   => 1, // Assign to the main admin
            'post_type'     => 'page',
        ]);

        if ($page_id > 0 && !is_wp_error($page_id)) {
            // Store the page ID so we can retrieve it later reliably
            update_option('puzzling_dashboard_page_id', $page_id);
        }
    }
}