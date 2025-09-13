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
    <h3><span class="dashicons dashicons-money-alt" style="vertical-align: middle;"></span> تنظیمات درگاه پرداخت</h3>
    <form id="puzzling-payment-settings-form" method="post">
        <?php wp_nonce_field('puzzling_save_settings'); ?>
        <input type="hidden" name="puzzling_action" value="save_payment_settings">

        <h4>تنظیمات درگاه پرداخت زرین‌پال</h4>
        <p class="description">برای اتصال سایت به درگاه پرداخت، مرچنت کد دریافت شده از زرین‌پال را در فیلد زیر وارد کنید.</p>
        <div class="form-group">
            <label for="zarinpal_merchant_id">مرچنت کد زرین‌پال:</label>
            <input type="text" id="zarinpal_merchant_id" name="puzzling_settings[zarinpal_merchant_id]" value="<?php echo esc_attr($zarinpal_merchant_id); ?>" class="ltr-input" required>
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