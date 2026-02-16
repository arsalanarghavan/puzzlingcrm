<?php
/**
 * Visitor Statistics Settings
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
$enable_visitor_statistics = isset( $settings['enable_visitor_statistics'] ) ? $settings['enable_visitor_statistics'] : '0';
?>
<div class="pzl-form-container">
    <h4><i class="ri-line-chart-line"></i> آمار بازدیدکنندگان</h4>
    <form method="post" class="pzl-form" style="margin-top: 20px;">
        <?php wp_nonce_field( 'puzzling_save_settings_nonce', 'security' ); ?>
        <input type="hidden" name="puzzling_action" value="save_puzzling_settings" />

        <div class="pzl-settings-section">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="puzzling_settings[enable_visitor_statistics]" value="1" <?php checked( $enable_visitor_statistics, '1' ); ?>>
                    <?php esc_html_e( 'فعال‌سازی ردیابی آمار بازدیدکنندگان', 'puzzlingcrm' ); ?>
                </label>
                <p class="description"><?php esc_html_e( 'در صورت فعال بودن، بازدید صفحات، مرورگر، دستگاه و ارجاع‌دهنده ذخیره و در بخش «آمار بازدید» نمایش داده می‌شود.', 'puzzlingcrm' ); ?></p>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="btn btn-primary"><?php esc_html_e( 'ذخیره تنظیمات', 'puzzlingcrm' ); ?></button>
        </p>
    </form>
</div>
