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
     * The main purpose of this menu is now to provide a clear entry point
     * to the frontend dashboard for administrators.
     */
    public function register_admin_menus() {
        // Main Menu Page that links to the frontend dashboard
        add_menu_page(
            __( 'PuzzlingCRM', 'puzzlingcrm' ),
            __( 'PuzzlingCRM', 'puzzlingcrm' ),
            'manage_options',
            'puzzling-crm-info', // Slug changed to avoid conflict
            [ $this, 'render_info_page' ], // Callback function changed
            'dashicons-businesswoman',
            25
        );
    }

    /**
     * HIGHLIGHT: Renders a simple info page instead of redirecting.
     * This ensures even admins use the intended interface.
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
            </ul>
        </div>
        <?php
    }
    
    /**
     * Displays admin notices, e.g., for configuration errors or missing extensions.
     */
    public function show_admin_notices() {
        // This function remains unchanged and will show important server/config notices to the admin.
        if ( get_transient( 'puzzling_sms_not_configured' ) ) {
            $settings_url = add_query_arg(['page' => 'puzzling-crm-info']); // Point to our new info page
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