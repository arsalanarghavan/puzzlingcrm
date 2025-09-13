<?php
/**
 * SMS Settings Page Template for System Manager
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

// Get saved settings to populate the form fields
$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
$melipayamak_api_key = $settings['melipayamak_api_key'] ?? '';
$melipayamak_api_secret = $settings['melipayamak_api_secret'] ?? '';
$melipayamak_sender_number = $settings['melipayamak_sender_number'] ?? '';
$pattern_3_days = $settings['pattern_3_days'] ?? '';
$pattern_1_day = $settings['pattern_1_day'] ?? '';
$pattern_due_today = $settings['pattern_due_today'] ?? '';
?>

<div class="pzl-form-container">
    <h3><span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span> تنظیمات سامانه پیامک</h3>
    <form id="puzzling-sms-settings-form" method="post">
        <?php wp_nonce_field('puzzling_save_settings'); ?>
        <input type="hidden" name="puzzling_action" value="save_sms_settings">

        <h4>تنظیمات پنل پیامک ملی‌پیامک</h4>
        <p class="description">اطلاعات حساب کاربری خود در ملی‌پیامک را برای ارسال خودکار پیامک‌های یادآوری اقساط وارد کنید.</p>
        <div class="form-group">
            <label for="melipayamak_api_key">کلید API:</label>
            <input type="text" id="melipayamak_api_key" name="puzzling_settings[melipayamak_api_key]" value="<?php echo esc_attr($melipayamak_api_key); ?>" class="ltr-input" required>
        </div>
        <div class="form-group">
            <label for="melipayamak_api_secret">کلید Secret:</label>
            <input type="text" id="melipayamak_api_secret" name="puzzling_settings[melipayamak_api_secret]" value="<?php echo esc_attr($melipayamak_api_secret); ?>" class="ltr-input">
        </div>
        <div class="form-group">
            <label for="melipayamak_sender_number">شماره خط فرستنده:</label>
            <input type="text" id="melipayamak_sender_number" name="puzzling_settings[melipayamak_sender_number]" value="<?php echo esc_attr($melipayamak_sender_number); ?>" class="ltr-input" required placeholder="مثال: 3000...">
        </div>

        <hr>

        <h4>کدهای پترن (الگوی) پیامک</h4>
        <p class="description">کد الگوهای ساخته شده در پنل ملی‌پیامک را برای هر سناریو وارد کنید. متغیر مورد استفاده در الگوها باید <code>%amount%</code> باشد.</p>
        <div class="form-group">
            <label for="pattern_3_days">الگوی یادآوری ۳ روز قبل:</label>
            <input type="text" id="pattern_3_days" name="puzzling_settings[pattern_3_days]" value="<?php echo esc_attr($pattern_3_days); ?>" class="ltr-input" placeholder="مثال: 98ab76c">
        </div>
        <div class="form-group">
            <label for="pattern_1_day">الگوی یادآوری ۱ روز قبل:</label>
            <input type="text" id="pattern_1_day" name="puzzling_settings[pattern_1_day]" value="<?php echo esc_attr($pattern_1_day); ?>" class="ltr-input">
        </div>
        <div class="form-group">
            <label for="pattern_due_today">الگوی یادآوری روز سررسید:</label>
            <input type="text" id="pattern_due_today" name="puzzling_settings[pattern_due_today]" value="<?php echo esc_attr($pattern_due_today); ?>" class="ltr-input">
        </div>
        
        <br>
        <button type="submit" class="pzl-button pzl-button-primary">ذخیره تنظیمات</button>
    </form>
</div>
<style>
.pzl-form-container .form-group { margin-bottom: 15px; }
.pzl-form-container label { display: block; margin-bottom: 5px; font-weight: bold; }
.pzl-form-container input[type="text"] { width: 100%; max-width: 400px; padding: 8px; }
.ltr-input { direction: ltr; text-align: left; }
</style>