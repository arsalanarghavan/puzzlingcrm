<?php
/**
 * Notifications Settings Page Template
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
?>

<div class="pzl-form-container">
    <h4><i class="ri-notification-3-line"></i> تنظیمات اطلاع‌رسانی‌ها</h4>
    <p class="description">کانال‌های اطلاع‌رسانی برای رویدادهای مختلف سیستم را در این بخش مدیریت کنید.</p>

    <form id="puzzling-notifications-settings-form" method="post" class="pzl-form" style="margin-top: 20px;">
        <?php wp_nonce_field('puzzling_save_settings_nonce', 'security'); ?>

        <div class="pzl-card" style="background-color: #f5faff;">
            <h5><i class="fab fa-telegram-plane"></i> تنظیمات ربات تلگرام</h5>
            <div class="form-group">
                <label for="telegram_bot_token">توکن ربات تلگرام (Bot Token)</label>
                <input type="text" id="telegram_bot_token" name="puzzling_settings[telegram_bot_token]" value="<?php echo esc_attr($settings['telegram_bot_token'] ?? ''); ?>" class="ltr-input">
            </div>
            <div class="form-group">
                <label for="telegram_chat_id">شناسه چت (Chat ID)</label>
                <input type="text" id="telegram_chat_id" name="puzzling_settings[telegram_chat_id]" value="<?php echo esc_attr($settings['telegram_chat_id'] ?? ''); ?>" class="ltr-input" placeholder="شناسه کانال، گروه یا کاربر">
                <p class="description">برای ارسال به کانال، ربات باید ادمین کانال باشد.</p>
            </div>
        </div>

        <div class="pzl-card">
            <h5><i class="ri-task-line"></i> اطلاع‌رسانی‌های مربوط به وظایف</h5>
            <table class="pzl-table">
                <thead>
                    <tr>
                        <th>رویداد</th>
                        <th>ایمیل</th>
                        <th>پیامک</th>
                        <th>تلگرام</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>ایجاد تسک جدید برای کارمند</strong></td>
                        <td>
                            <label><input type="checkbox" name="puzzling_settings[notifications][new_task][email]" value="1" <?php checked($settings['notifications']['new_task']['email'] ?? 1); ?>> فعال</label>
                        </td>
                        <td>
                            <label><input type="checkbox" name="puzzling_settings[notifications][new_task][sms]" value="1" <?php checked($settings['notifications']['new_task']['sms'] ?? 0); ?>> فعال</label>
                        </td>
                        <td>
                            <label><input type="checkbox" name="puzzling_settings[notifications][new_task][telegram]" value="1" <?php checked($settings['notifications']['new_task']['telegram'] ?? 0); ?>> فعال</label>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>تغییر وضعیت تسک</strong></td>
                        <td>
                            <label><input type="checkbox" name="puzzling_settings[notifications][status_change][email]" value="1" <?php checked($settings['notifications']['status_change']['email'] ?? 0); ?>> فعال</label>
                        </td>
                        <td>
                             <label><input type="checkbox" name="puzzling_settings[notifications][status_change][sms]" value="1" <?php checked($settings['notifications']['status_change']['sms'] ?? 0); ?>> فعال</label>
                        </td>
                        <td>
                             <label><input type="checkbox" name="puzzling_settings[notifications][status_change][telegram]" value="1" <?php checked($settings['notifications']['status_change']['telegram'] ?? 0); ?>> فعال</label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="form-submit">
            <button type="submit" class="pzl-button" data-puzzling-skip-global-handler="true">ذخیره تنظیمات اطلاع‌رسانی</button>
        </div>
    </form>
</div>