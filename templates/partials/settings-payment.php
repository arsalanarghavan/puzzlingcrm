<?php
/**
 * Payment Settings Page Template for System Manager
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

// Get saved settings to populate the form fields
$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
$zarinpal_merchant_id = $settings['zarinpal_merchant_id'] ?? '';
?>

<div class="pzl-form-container">
    <h4><span class="dashicons dashicons-admin-generic"></span> تنظیمات درگاه پرداخت زرین‌پال</h4>
    <p class="description">برای اتصال سایت به درگاه پرداخت، مرچنت کد دریافت شده از زرین‌پال را در فیلد زیر وارد کنید.</p>
    <form id="puzzling-payment-settings-form" method="post" class="pzl-form" style="margin-top: 20px;">
        <?php wp_nonce_field('puzzling_save_settings'); ?>
        <input type="hidden" name="puzzling_action" value="save_settings">

        <div class="form-group">
            <label for="zarinpal_merchant_id">مرچنت کد زرین‌پال:</label>
            <input type="text" id="zarinpal_merchant_id" name="puzzling_settings[zarinpal_merchant_id]" value="<?php echo esc_attr($zarinpal_merchant_id); ?>" class="ltr-input" required>
        </div>
        
        <div class="form-submit">
            <button type="submit" class="pzl-button pzl-button-primary">ذخیره تنظیمات</button>
        </div>
    </form>
</div>