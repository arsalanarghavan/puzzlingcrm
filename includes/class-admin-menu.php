<?php
/**
 * PuzzlingCRM Admin Menu Manager
 *
 * This class handles the creation of the admin menus in the WordPress dashboard.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Admin_Menu {

    /**
     * Constructor. Hooks into the 'admin_menu' action.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_menus' ] );
        add_action( 'admin_notices', [ $this, 'show_admin_notices' ] );
    }

    /**
     * Registers all the admin menu and submenu pages for the plugin.
     * This now includes a dedicated backend page for managing leads.
     */
    public function register_admin_menus() {
        // Main Menu Page
        add_menu_page(
            __( 'PuzzlingCRM', 'puzzlingcrm' ),
            __( 'PuzzlingCRM', 'puzzlingcrm' ),
            'manage_options',
            'puzzling-crm', // Main slug for the menu
            [ $this, 'render_leads_page' ], // Make Leads the default page
            'dashicons-businesswoman',
            25
        );

        // Submenu Page for Leads
        add_submenu_page(
            'puzzling-crm', // Parent slug
            __( 'مدیریت سرنخ‌ها', 'puzzlingcrm' ), // Page title
            __( 'مدیریت سرنخ‌ها', 'puzzlingcrm' ), // Menu title
            'manage_options', // Capability
            'puzzling-crm', // Slug (same as parent to be the default)
            [ $this, 'render_leads_page' ] // Callback function
        );

        // Submenu Page for Info/Shortcodes
        add_submenu_page(
            'puzzling-crm', // Parent slug
            __( 'راهنمای شورت‌کدها', 'puzzlingcrm' ), // Page title
            __( 'راهنمای شورت‌کدها', 'puzzlingcrm' ), // Menu title
            'manage_options', // Capability
            'puzzling-crm-info', // Unique slug for this page
            [ $this, 'render_info_page' ] // Callback function
        );
    }

    /**
     * Renders the Leads management page with a file existence check for debugging.
     */
    public function render_leads_page() {
        // Define the full path to the template file
        $file_path = PUZZLING_CRM_PATH . 'templates/partials/page-leads.php';

        // Check if the file actually exists at that path
        if ( file_exists( $file_path ) ) {
            // If it exists, load it.
            require_once $file_path;
        } else {
            // If it doesn't exist, show a clear error message.
            ?>
            <div class="wrap">
                <h1>خطای بارگذاری فایل</h1>
                <div class="notice notice-error">
                    <p><strong>خطا:</strong> فایل مورد نیاز برای نمایش صفحه سرنخ‌ها پیدا نشد.</p>
                    <p>لطفاً مطمئن شوید که یک فایل با نام <strong>page-leads.php</strong> دقیقاً در مسیر زیر وجود دارد:</p>
                    <code><?php echo esc_html( $file_path ); ?></code>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Renders the info page with a list of available shortcodes.
     * This function is preserved from your original file.
     */
    public function render_info_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'به PuzzlingCRM خوش آمدید', 'puzzlingcrm' ); ?></h1>
            <p><?php esc_html_e( 'تمام بخش‌های مدیریتی PuzzlingCRM از طریق شورت‌کدها در صفحات سایت شما قابل استفاده است.', 'puzzlingcrm' ); ?></p>
            <p><?php esc_html_e( 'می‌توانید با قرار دادن شورت‌کدهای زیر در هر برگه‌ای، داشبورد اختصاصی خود را بسازید.', 'puzzlingcrm' ); ?></p>

            <h2><?php esc_html_e( 'شورت‌کدهای موجود', 'puzzlingcrm' ); ?></h2>
            <ul style="list-style-type: disc; padding-right: 20px;">
                <li><code>[puzzling_projects]</code> - <?php esc_html_e('نمایش پروژه‌ها برای مدیران یا مشتریان.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_contracts]</code> - <?php esc_html_e('نمایش قراردادها برای مدیران یا مشتریان.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_invoices]</code> - <?php esc_html_e('نمایش فاکتورها و پرداخت‌ها برای مدیران یا مشتریان.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_pro_invoices]</code> - <?php esc_html_e('نمایش پیش‌فاکتورها برای مدیران یا مشتریان.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_appointments]</code> - <?php esc_html_e('نمایش قرار ملاقات‌ها برای مدیران یا مشتریان.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_tickets]</code> - <?php esc_html_e('نمایش تیکت‌های پشتیبانی.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_tasks]</code> - <?php esc_html_e('نمایش بخش مدیریت وظایف برای مدیران و اعضای تیم.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_customers]</code> - (<?php esc_html_e('فقط مدیر', 'puzzlingcrm'); ?>) <?php esc_html_e('صفحه مدیریت مشتریان.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_staff]</code> - (<?php esc_html_e('فقط مدیر', 'puzzlingcrm'); ?>) <?php esc_html_e('صفحه مدیریت کارمندان.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_subscriptions]</code> - (<?php esc_html_e('فقط مدیر', 'puzzlingcrm'); ?>) <?php esc_html_e('صفحه مدیریت اشتراک‌های ووکامرس.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_reports]</code> - (<?php esc_html_e('فقط مدیر', 'puzzlingcrm'); ?>) <?php esc_html_e('صفحه گزارش‌ها.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_settings]</code> - (<?php esc_html_e('فقط مدیر', 'puzzlingcrm'); ?>) <?php esc_html_e('صفحه تنظیمات.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_logs]</code> - (<?php esc_html_e('فقط مدیر', 'puzzlingcrm'); ?>) <?php esc_html_e('لاگ رویدادهای سیستم.', 'puzzlingcrm'); ?></li>
                <li><code>[puzzling_leads]</code> - (<?php esc_html_e('فقط مدیر', 'puzzlingcrm'); ?>) <?php esc_html_e('صفحه مدیریت لیدها.', 'puzzlingcrm'); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Displays admin notices, e.g., for configuration errors or missing extensions.
     * This function remains unchanged.
     */
    public function show_admin_notices() {
        if ( get_transient( 'puzzling_sms_not_configured' ) ) {
            $settings_url = add_query_arg(['page' => 'puzzling-crm-info']);
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php esc_html_e( 'PuzzlingCRM:', 'puzzlingcrm' ); ?></strong>
                    <?php
                    printf(
                        wp_kses_post( __( 'The SMS service for sending reminders is not configured correctly. Please <a href="%s">check your settings</a>.', 'puzzlingcrm' ) ),
                        esc_url( $settings_url )
                    );
                    ?>
                </p>
            </div>
            <?php
            delete_transient( 'puzzling_sms_not_configured' );
        }

        if ( get_transient( 'puzzling_soap_not_enabled' ) ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php esc_html_e( 'PuzzlingCRM:', 'puzzlingcrm' ); ?></strong>
                    <?php esc_html_e( 'The PHP SOAP extension is not enabled on your server. This is required for the ParsGreen SMS service to function. Please contact your hosting provider to enable it.', 'puzzlingcrm' ); ?>
                </p>
            </div>
            <?php
            delete_transient( 'puzzling_soap_not_enabled' );
        }
    }
}