<?php
/**
 * SMS Settings Page Template for System Manager
 * Supports multiple SMS providers with user-defined templates.
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

// Get saved settings to populate the form fields
$settings = PuzzlingCRM_Settings_Handler::get_all_settings();

// Active service
$active_service = $settings['sms_service'] ?? 'melipayamak';

// Melipayamak settings
$melipayamak_api_key = $settings['melipayamak_api_key'] ?? '';
$melipayamak_api_secret = $settings['melipayamak_api_secret'] ?? '';
$melipayamak_sender_number = $settings['melipayamak_sender_number'] ?? '';
$pattern_3_days = $settings['pattern_3_days'] ?? '';
$pattern_1_day = $settings['pattern_1_day'] ?? '';
$pattern_due_today = $settings['pattern_due_today'] ?? '';

// ParsGreen settings
$parsgreen_signature = $settings['parsgreen_signature'] ?? '';
$parsgreen_sender_number = $settings['parsgreen_sender_number'] ?? '';
$parsgreen_msg_3_days = $settings['parsgreen_msg_3_days'] ?? '';
$parsgreen_msg_1_day = $settings['parsgreen_msg_1_day'] ?? '';
$parsgreen_msg_due_today = $settings['parsgreen_msg_due_today'] ?? '';
?>

<div class="pzl-form-container">
    <h3><span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span> تنظیمات سامانه پیامک</h3>
    <form id="puzzling-sms-settings-form" method="post">
        <?php wp_nonce_field('puzzling_save_settings'); ?>
        <input type="hidden" name="puzzling_action" value="save_settings">

        <div class="form-group">
            <label for="sms_service">سرویس پیامک فعال:</label>
            <select id="sms_service" name="puzzling_settings[sms_service]">
                <option value="melipayamak" <?php selected($active_service, 'melipayamak'); ?>>ملی‌پیامک</option>
                <option value="parsgreen" <?php selected($active_service, 'parsgreen'); ?>>پارس گرین</option>
            </select>
            <p class="description">سرویس پیامکی که برای ارسال‌های خودکار استفاده خواهد شد را انتخاب کنید.</p>
        </div>

        <hr>

        <div id="melipayamak-settings" class="sms-provider-settings">
            <h4>تنظیمات پنل پیامک ملی‌پیامک</h4>
            <p class="description">اطلاعات حساب کاربری خود در ملی‌پیامک را برای ارسال خودکار پیامک‌های یادآوری اقساط وارد کنید.</p>
            <div class="form-group">
                <label for="melipayamak_api_key">کلید API:</label>
                <input type="text" id="melipayamak_api_key" name="puzzling_settings[melipayamak_api_key]" value="<?php echo esc_attr($melipayamak_api_key); ?>" class="ltr-input">
            </div>
            <div class="form-group">
                <label for="melipayamak_api_secret">کلید Secret:</label>
                <input type="text" id="melipayamak_api_secret" name="puzzling_settings[melipayamak_api_secret]" value="<?php echo esc_attr($melipayamak_api_secret); ?>" class="ltr-input">
            </div>
            <div class="form-group">
                <label for="melipayamak_sender_number">شماره خط فرستنده:</label>
                <input type="text" id="melipayamak_sender_number" name="puzzling_settings[melipayamak_sender_number]" value="<?php echo esc_attr($melipayamak_sender_number); ?>" class="ltr-input" placeholder="مثال: 3000...">
            </div>
            <h5>کدهای پترن (الگوی) پیامک</h5>
            <p class="description">کد الگوهای ساخته شده در پنل ملی‌پیامک را برای هر سناریو وارد کنید. متغیر مورد استفاده در الگوها باید <code>%amount%</code> باشد.</p>
            <div class="form-group"><label for="pattern_3_days">الگوی یادآوری ۳ روز قبل:</label><input type="text" id="pattern_3_days" name="puzzling_settings[pattern_3_days]" value="<?php echo esc_attr($pattern_3_days); ?>" class="ltr-input" placeholder="مثال: 98ab76c"></div>
            <div class="form-group"><label for="pattern_1_day">الگوی یادآوری ۱ روز قبل:</label><input type="text" id="pattern_1_day" name="puzzling_settings[pattern_1_day]" value="<?php echo esc_attr($pattern_1_day); ?>" class="ltr-input"></div>
            <div class="form-group"><label for="pattern_due_today">الگوی یادآوری روز سررسید:</label><input type="text" id="pattern_due_today" name="puzzling_settings[pattern_due_today]" value="<?php echo esc_attr($pattern_due_today); ?>" class="ltr-input"></div>
        </div>

        <div id="parsgreen-settings" class="sms-provider-settings">
            <h4>تنظیمات پنل پیامک پارس گرین</h4>
            <p class="description">اطلاعات حساب کاربری خود در پارس گرین را وارد کنید.</p>
            <div class="form-group"><label for="parsgreen_signature">کد Signature (امضا):</label><input type="text" id="parsgreen_signature" name="puzzling_settings[parsgreen_signature]" value="<?php echo esc_attr($parsgreen_signature); ?>" class="ltr-input"></div>
            <div class="form-group"><label for="parsgreen_sender_number">شماره خط فرستنده:</label><input type="text" id="parsgreen_sender_number" name="puzzling_settings[parsgreen_sender_number]" value="<?php echo esc_attr($parsgreen_sender_number); ?>" class="ltr-input" placeholder="مثال: 02100021000"></div>
            <h5>متن پیامک‌های یادآوری</h5>
            <p class="description">متن کامل پیامک برای هر سناریو را وارد کنید. برای نمایش مبلغ قسط از <code>{amount}</code> استفاده کنید.</p>
            <div class="form-group"><label for="parsgreen_msg_3_days">متن پیامک یادآوری ۳ روز قبل:</label><textarea id="parsgreen_msg_3_days" name="puzzling_settings[parsgreen_msg_3_days]" rows="3" style="width:100%"><?php echo esc_textarea($parsgreen_msg_3_days); ?></textarea></div>
            <div class="form-group"><label for="parsgreen_msg_1_day">متن پیامک یادآوری ۱ روز قبل:</label><textarea id="parsgreen_msg_1_day" name="puzzling_settings[parsgreen_msg_1_day]" rows="3" style="width:100%"><?php echo esc_textarea($parsgreen_msg_1_day); ?></textarea></div>
            <div class="form-group"><label for="parsgreen_msg_due_today">متن پیامک یادآوری روز سررسید:</label><textarea id="parsgreen_msg_due_today" name="puzzling_settings[parsgreen_msg_due_today]" rows="3" style="width:100%"><?php echo esc_textarea($parsgreen_msg_due_today); ?></textarea></div>
        </div>
        
        <br>
        <button type="submit" class="pzl-button pzl-button-primary">ذخیره تنظیمات</button>
    </form>
</div>

<style>
.pzl-form-container .form-group { margin-bottom: 15px; }
.pzl-form-container label { display: block; margin-bottom: 5px; font-weight: bold; }
.pzl-form-container input[type="text"], .pzl-form-container select, .pzl-form-container textarea { width: 100%; max-width: 500px; padding: 8px; }
.ltr-input { direction: ltr; text-align: left; }
.sms-provider-settings { border: 1px solid #e0e0e0; padding: 20px; border-radius: 5px; margin-top: 20px; }
</style>

<script>
jQuery(document).ready(function($) {
    function toggleProviderSettings() {
        var selectedProvider = $('#sms_service').val();
        $('.sms-provider-settings').hide();
        $('#' + selectedProvider + '-settings').show();
    }
    toggleProviderSettings();
    $('#sms_service').on('change', toggleProviderSettings);
});
</script>