<?php
/**
 * SMS Settings Page Template for System Manager
 * Supports multiple SMS providers with user-defined templates.
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
$active_service = $settings['sms_service'] ?? 'melipayamak';
?>

<div class="pzl-form-container">
    <h4><i class="fas fa-cogs"></i> تنظیمات سامانه پیامک</h4>
    <form id="puzzling-sms-settings-form" method="post" class="pzl-form" style="margin-top: 20px;">
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

        <div id="melipayamak-settings" class="sms-provider-settings" style="display: <?php echo $active_service === 'melipayamak' ? 'block' : 'none'; ?>;">
            <h5>تنظیمات پنل ملی‌پیامک</h5>
            <div class="form-group">
                <label for="melipayamak_api_key">کلید API:</label>
                <input type="text" id="melipayamak_api_key" name="puzzling_settings[melipayamak_api_key]" value="<?php echo esc_attr($settings['melipayamak_api_key'] ?? ''); ?>" class="ltr-input">
            </div>
            <div class="form-group">
                <label for="melipayamak_sender_number">شماره خط فرستنده:</label>
                <input type="text" id="melipayamak_sender_number" name="puzzling_settings[melipayamak_sender_number]" value="<?php echo esc_attr($settings['melipayamak_sender_number'] ?? ''); ?>" class="ltr-input" placeholder="مثال: 3000...">
            </div>
            <h6>کدهای پترن پیامک</h6>
            <p class="description">کد الگوهای ساخته شده در پنل را وارد کنید. متغیر مورد استفاده باید <code>%amount%</code> باشد.</p>
            <div class="form-group"><label for="pattern_3_days">الگوی یادآوری ۳ روز قبل:</label><input type="text" id="pattern_3_days" name="puzzling_settings[pattern_3_days]" value="<?php echo esc_attr($settings['pattern_3_days'] ?? ''); ?>" class="ltr-input" placeholder="مثال: 98ab76c"></div>
            <div class="form-group"><label for="pattern_1_day">الگوی یادآوری ۱ روز قبل:</label><input type="text" id="pattern_1_day" name="puzzling_settings[pattern_1_day]" value="<?php echo esc_attr($settings['pattern_1_day'] ?? ''); ?>" class="ltr-input"></div>
            <div class="form-group"><label for="pattern_due_today">الگوی یادآوری روز سررسید:</label><input type="text" id="pattern_due_today" name="puzzling_settings[pattern_due_today]" value="<?php echo esc_attr($settings['pattern_due_today'] ?? ''); ?>" class="ltr-input"></div>
        </div>

        <div id="parsgreen-settings" class="sms-provider-settings" style="display: <?php echo $active_service === 'parsgreen' ? 'block' : 'none'; ?>;">
            <h5>تنظیمات پنل پارس گرین</h5>
            <div class="form-group"><label for="parsgreen_signature">کد Signature (امضا):</label><input type="text" id="parsgreen_signature" name="puzzling_settings[parsgreen_signature]" value="<?php echo esc_attr($settings['parsgreen_signature'] ?? ''); ?>" class="ltr-input"></div>
            <div class="form-group"><label for="parsgreen_sender_number">شماره خط فرستنده:</label><input type="text" id="parsgreen_sender_number" name="puzzling_settings[parsgreen_sender_number]" value="<?php echo esc_attr($settings['parsgreen_sender_number'] ?? ''); ?>" class="ltr-input" placeholder="مثال: 02100021000"></div>
            <h6>متن پیامک‌های یادآوری</h6>
            <p class="description">متن کامل پیامک را وارد کنید. برای نمایش مبلغ قسط از <code>{amount}</code> استفاده کنید.</p>
            <div class="form-group"><label for="parsgreen_msg_3_days">متن پیامک ۳ روز قبل:</label><textarea id="parsgreen_msg_3_days" name="puzzling_settings[parsgreen_msg_3_days]" rows="3"><?php echo esc_textarea($settings['parsgreen_msg_3_days'] ?? ''); ?></textarea></div>
            <div class="form-group"><label for="parsgreen_msg_1_day">متن پیامک ۱ روز قبل:</label><textarea id="parsgreen_msg_1_day" name="puzzling_settings[parsgreen_msg_1_day]" rows="3"><?php echo esc_textarea($settings['parsgreen_msg_1_day'] ?? ''); ?></textarea></div>
            <div class="form-group"><label for="parsgreen_msg_due_today">متن پیامک روز سررسید:</label><textarea id="parsgreen_msg_due_today" name="puzzling_settings[parsgreen_msg_due_today]" rows="3"><?php echo esc_textarea($settings['parsgreen_msg_due_today'] ?? ''); ?></textarea></div>
        </div>
        
        <div class="form-submit">
            <button type="submit" class="pzl-button">ذخیره تنظیمات</button>
        </div>
    </form>
</div>

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