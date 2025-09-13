<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Admin_Menu {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
    }

    public function register_menus() {
        // Main Menu Page
        add_menu_page(
            'PuzzlingCRM',
            'PuzzlingCRM',
            'manage_options',
            'puzzling-crm',
            [ $this, 'render_dashboard_page' ],
            'dashicons-businessperson',
            25
        );

        // Sub-Menus
        add_submenu_page('puzzling-crm', 'داشبورد', 'داشبورد', 'manage_options', 'puzzling-crm', [ $this, 'render_dashboard_page' ]);
        add_submenu_page('puzzling-crm', 'مشتریان', 'مشتریان', 'manage_options', 'pzl-customers', [ $this, 'render_customers_page' ]);
        add_submenu_page('puzzling-crm', 'کارکنان', 'کارکنان', 'manage_options', 'pzl-staff', [ $this, 'render_staff_page' ]);
        add_submenu_page('puzzling-crm', 'پروژه‌ها', 'پروژه‌ها', 'manage_options', 'pzl-projects', [ $this, 'render_projects_page' ]);
        add_submenu_page('puzzling-crm', 'قراردادها', 'قراردادها', 'manage_options', 'pzl-contracts', [ $this, 'render_contracts_page' ]);
        add_submenu_page('puzzling-crm', 'وظایف', 'وظایف', 'manage_options', 'pzl-tasks', [ $this, 'render_tasks_page' ]);
        add_submenu_page('puzzling-crm', 'اشتراک‌ها', 'اشتراک‌ها', 'manage_options', 'pzl-subscriptions', [ $this, 'render_subscriptions_page' ]);
        add_submenu_page('puzzling-crm', 'قرار ملاقات', 'قرار ملاقات', 'manage_options', 'pzl-appointments', [ $this, 'render_appointments_page' ]);
        add_submenu_page('puzzling-crm', 'گزارش‌ها', 'گزارش‌ها', 'manage_options', 'pzl-reports', [ $this, 'render_reports_page' ]);
        add_submenu_page('puzzling-crm', 'تنظیمات', 'تنظیمات', 'manage_options', 'pzl-settings', [ $this, 'render_settings_page' ]);
    }

    private function render_page($template_name) {
        $file_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/admin/' . $template_name . '.php';
        if (file_exists($file_path)) {
            echo '<div class="wrap">';
            include $file_path;
            echo '</div>';
        } else {
            echo '<div class="wrap"><h2>خطا</h2><p>فایل قالب یافت نشد.</p></div>';
        }
    }

    public function render_dashboard_page() { $this->render_page('page-dashboard'); }
    public function render_customers_page() { $this->render_page('page-customers'); }
    public function render_staff_page() { $this->render_page('page-staff'); }
    public function render_projects_page() { $this->render_page('page-projects'); }
    public function render_contracts_page() { $this->render_page('page-contracts'); }
    public function render_tasks_page() { $this->render_page('page-tasks'); }
    public function render_subscriptions_page() { $this->render_page('page-subscriptions'); }
    public function render_appointments_page() { $this->render_page('page-appointments'); }
    public function render_reports_page() { $this->render_page('page-reports'); }
    public function render_settings_page() { $this->render_page('page-settings'); }
}