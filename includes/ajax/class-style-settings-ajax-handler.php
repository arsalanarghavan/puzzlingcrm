<?php
/**
 * AJAX Handler برای تنظیمات استایل
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Style_Settings_Ajax_Handler {

    public function __construct() {
        add_action( 'wp_ajax_puzzling_save_style_settings', [ $this, 'save_style_settings' ] );
        add_action( 'wp_ajax_puzzling_reset_style_settings', [ $this, 'reset_style_settings' ] );
    }

    /**
     * ذخیره تنظیمات استایل
     */
    public function save_style_settings() {
        // بررسی nonce
        if ( ! isset( $_POST['puzzling_style_nonce'] ) || ! wp_verify_nonce( $_POST['puzzling_style_nonce'], 'puzzling_save_style_settings' ) ) {
            wp_send_json_error( [ 'message' => 'خطای امنیتی: درخواست معتبر نیست.' ] );
        }

        // بررسی دسترسی
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'شما دسترسی لازم برای این عملیات را ندارید.' ] );
        }

        // دریافت و sanitize کردن داده‌ها
        $style_settings = [
            'logo_desktop' => isset( $_POST['logo_desktop'] ) ? esc_url_raw( $_POST['logo_desktop'] ) : '',
            'logo_mobile' => isset( $_POST['logo_mobile'] ) ? esc_url_raw( $_POST['logo_mobile'] ) : '',
            'logo_dark' => isset( $_POST['logo_dark'] ) ? esc_url_raw( $_POST['logo_dark'] ) : '',
            'logo_favicon' => isset( $_POST['logo_favicon'] ) ? esc_url_raw( $_POST['logo_favicon'] ) : '',
            'primary_color' => isset( $_POST['primary_color'] ) ? sanitize_hex_color( $_POST['primary_color'] ) : '#6366f1',
            'secondary_color' => isset( $_POST['secondary_color'] ) ? sanitize_hex_color( $_POST['secondary_color'] ) : '#6c757d',
            'success_color' => isset( $_POST['success_color'] ) ? sanitize_hex_color( $_POST['success_color'] ) : '#10b981',
            'danger_color' => isset( $_POST['danger_color'] ) ? sanitize_hex_color( $_POST['danger_color'] ) : '#ef4444',
            'warning_color' => isset( $_POST['warning_color'] ) ? sanitize_hex_color( $_POST['warning_color'] ) : '#f59e0b',
            'info_color' => isset( $_POST['info_color'] ) ? sanitize_hex_color( $_POST['info_color'] ) : '#3b82f6',
            'menu_bg_color' => isset( $_POST['menu_bg_color'] ) ? sanitize_hex_color( $_POST['menu_bg_color'] ) : '#1e293b',
            'header_bg_color' => isset( $_POST['header_bg_color'] ) ? sanitize_hex_color( $_POST['header_bg_color'] ) : '#ffffff',
            'body_font' => isset( $_POST['body_font'] ) ? sanitize_text_field( $_POST['body_font'] ) : 'Vazirmatn',
            'heading_font' => isset( $_POST['heading_font'] ) ? sanitize_text_field( $_POST['heading_font'] ) : 'Vazirmatn',
            'font_size_base' => isset( $_POST['font_size_base'] ) ? intval( $_POST['font_size_base'] ) : 14,
            'theme_mode' => isset( $_POST['theme_mode'] ) ? sanitize_text_field( $_POST['theme_mode'] ) : 'light',
            'menu_style' => isset( $_POST['menu_style'] ) ? sanitize_text_field( $_POST['menu_style'] ) : 'dark',
            'header_style' => isset( $_POST['header_style'] ) ? sanitize_text_field( $_POST['header_style'] ) : 'light',
            'sidebar_layout' => isset( $_POST['sidebar_layout'] ) ? sanitize_text_field( $_POST['sidebar_layout'] ) : 'default',
        ];

        // دریافت تمام تنظیمات فعلی
        $all_settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        
        // بروزرسانی بخش style
        $all_settings['style'] = $style_settings;

        // ذخیره در دیتابیس
        $result = PuzzlingCRM_Settings_Handler::update_settings( $all_settings );

        if ( $result ) {
            // ساخت CSS دینامیک
            $this->generate_dynamic_css( $style_settings );

            wp_send_json_success( [ 
                'message' => 'تنظیمات استایل با موفقیت ذخیره شد.',
                'settings' => $style_settings
            ] );
        } else {
            wp_send_json_error( [ 'message' => 'خطا در ذخیره‌سازی تنظیمات در دیتابیس.' ] );
        }
    }

    /**
     * بازگشت به تنظیمات پیش‌فرض
     */
    public function reset_style_settings() {
        // بررسی nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'puzzling_reset_style_settings' ) ) {
            wp_send_json_error( [ 'message' => 'خطای امنیتی: درخواست معتبر نیست.' ] );
        }

        // بررسی دسترسی
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'شما دسترسی لازم برای این عملیات را ندارید.' ] );
        }

        // مقادیر پیش‌فرض
        $default_settings = [
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

        // دریافت تمام تنظیمات
        $all_settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        
        // بازنشانی بخش style
        $all_settings['style'] = $default_settings;

        // ذخیره در دیتابیس
        $result = PuzzlingCRM_Settings_Handler::update_settings( $all_settings );

        if ( $result ) {
            // حذف CSS دینامیک
            $upload_dir = wp_upload_dir();
            $css_file = $upload_dir['basedir'] . '/puzzlingcrm-custom-style.css';
            if ( file_exists( $css_file ) ) {
                @unlink( $css_file );
            }

            wp_send_json_success( [ 
                'message' => 'تنظیمات به حالت پیش‌فرض بازگشت.',
                'settings' => $default_settings
            ] );
        } else {
            wp_send_json_error( [ 'message' => 'خطا در بازنشانی تنظیمات.' ] );
        }
    }

    /**
     * تولید CSS دینامیک بر اساس تنظیمات
     */
    private function generate_dynamic_css( $settings ) {
        $css = "/* PuzzlingCRM Dynamic Styles - Generated: " . date('Y-m-d H:i:s') . " */\n\n";

        // متغیرهای CSS
        $css .= ":root {\n";
        $css .= "    --primary-color: {$settings['primary_color']};\n";
        $css .= "    --secondary-color: {$settings['secondary_color']};\n";
        $css .= "    --success-color: {$settings['success_color']};\n";
        $css .= "    --danger-color: {$settings['danger_color']};\n";
        $css .= "    --warning-color: {$settings['warning_color']};\n";
        $css .= "    --info-color: {$settings['info_color']};\n";
        $css .= "    --menu-bg-color: {$settings['menu_bg_color']};\n";
        $css .= "    --header-bg-color: {$settings['header_bg_color']};\n";
        $css .= "    --body-font: '{$settings['body_font']}';\n";
        $css .= "    --heading-font: '{$settings['heading_font']}';\n";
        $css .= "    --font-size-base: {$settings['font_size_base']}px;\n";
        $css .= "}\n\n";

        // استایل‌های اصلی
        $css .= "body {\n";
        $css .= "    font-family: var(--body-font), sans-serif;\n";
        $css .= "    font-size: var(--font-size-base);\n";
        $css .= "}\n\n";

        $css .= "h1, h2, h3, h4, h5, h6 {\n";
        $css .= "    font-family: var(--heading-font), sans-serif;\n";
        $css .= "}\n\n";

        // رنگ‌های دکمه‌ها
        $css .= ".btn-primary {\n";
        $css .= "    background-color: var(--primary-color) !important;\n";
        $css .= "    border-color: var(--primary-color) !important;\n";
        $css .= "}\n\n";

        $css .= ".btn-primary:hover {\n";
        $css .= "    filter: brightness(90%);\n";
        $css .= "}\n\n";

        // سایدبار
        $css .= ".app-sidebar {\n";
        $css .= "    background-color: var(--menu-bg-color) !important;\n";
        $css .= "}\n\n";

        // هدر
        $css .= ".app-header {\n";
        $css .= "    background-color: var(--header-bg-color) !important;\n";
        $css .= "}\n\n";

        // ذخیره در فایل
        $upload_dir = wp_upload_dir();
        $css_file = $upload_dir['basedir'] . '/puzzlingcrm-custom-style.css';
        
        file_put_contents( $css_file, $css );

        // ثبت URL فایل در options
        update_option( 'puzzlingcrm_custom_css_url', $upload_dir['baseurl'] . '/puzzlingcrm-custom-style.css' );
        update_option( 'puzzlingcrm_custom_css_version', time() );
    }
}

// Initialize
new PuzzlingCRM_Style_Settings_Ajax_Handler();
