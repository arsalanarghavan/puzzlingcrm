<?php
/**
 * Settings Page Template for System Manager
 *
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get saved settings to populate the form fields
$zarinpal_merchant_id = PuzzlingCRM_Settings_Handler::get_setting('zarinpal_merchant_id');
$melipayamak_api_key = PuzzlingCRM_Settings_Handler::get_setting('melipayamak_api_key');
$melipayamak_api_secret = PuzzlingCRM_Settings_Handler::get_setting('melipayamak_api_secret');
$pattern_3_days = PuzzlingCRM_Settings_Handler::get_setting('pattern_3_days');
$pattern_1_day = PuzzlingCRM_Settings_Handler::get_setting('pattern_1_day');
$pattern_due_today = PuzzlingCRM_Settings_Handler::get_setting('pattern_due_today');
?>

<div class="pzl-form-container">
    <h3><span class="dashicons dashicons-admin-settings" style="vertical-align: middle;"></span> تنظیمات پلاگین</h3>
    <form id="puzzling-settings-form" method="post">
        <?php wp_nonce_field('puzzling_save_settings'); ?>
        <input type="hidden" name="puzzling_action" value="save_settings">

        <h4>تنظیمات درگاه پرداخت زرین‌پال</h4>
        <div class="form-group">
            <label for="zarinpal_merchant_id">مرچنت کد زرین‌پال:</label>
            <input type="text" id="zarinpal_merchant_id" name="puzzling_settings[zarinpal_merchant_id]" value="<?php echo esc_attr($zarinpal_merchant_id); ?>" class="ltr-input" required>
        </div>

        <hr>

        <h4>تنظیمات پنل پیامک ملی‌پیامک</h4>
        <div class="form-group">
            <label for="melipayamak_api_key">کلید API:</label>
            <input type="text" id="melipayamak_api_key" name="puzzling_settings[melipayamak_api_key]" value="<?php echo esc_attr($melipayamak_api_key); ?>" class="ltr-input" required>
        </div>
        <div class="form-group">
            <label for="melipayamak_api_secret">کلید Secret:</label>
            <input type="text" id="melipayamak_api_secret" name="puzzling_settings[melipayamak_api_secret]" value="<?php echo esc_attr($melipayamak_api_secret); ?>" class="ltr-input">
        </div>

        <h4>کدهای پترن (الگوی) پیامک</h4>
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
.pzl-form-container input[type="text"] { width: 100%; padding: 8px; }
.ltr-input { direction: ltr; text-align: left; }
</style>