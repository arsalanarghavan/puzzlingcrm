<?php
/**
 * Workflow Settings Page Template for System Manager
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
$rules = $settings['workflow_rules'] ?? [];
$work_start_hour = $settings['work_start_hour'] ?? '09:00';
$statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false, 'orderby' => 'term_order']);
$roles = get_editable_roles();
?>

<div class="pzl-form-container">
    <h4><i class="fas
-sitemap"></i> تنظیمات گردش کار و اتوماسیون</h4>
    <p class="description">در این بخش می‌توانید قوانین دسترسی و تنظیمات مربوط به اتوماسیون وظایف را مدیریت کنید.</p>
    
    <div class="pzl-card">
        <form id="puzzling-workflow-settings-form" method="post" class="pzl-form" style="margin-top: 20px;">
            <?php wp_nonce_field('puzzling_save_settings_nonce', 'security'); ?>

            <h5><i class="ri-time-line"></i> تنظیمات اتوماسیون تسک روزانه</h5>
            <div class="form-group">
                <label for="work_start_hour">ساعت شروع کار (برای ساخت خودکار تسک‌ها)</label>
                <input type="time" id="work_start_hour" name="puzzling_settings[work_start_hour]" value="<?php echo esc_attr($work_start_hour); ?>" class="ltr-input">
                <p class="description">سیستم هر روز در این ساعت، تسک‌های روزانه را بر اساس قالب‌های تعریف شده ایجاد خواهد کرد.</p>
            </div>

            <hr style="margin: 30px 0;">

            <h5><i class="ri-check-line-double"></i> قوانین دسترسی به ستون‌های کانبان</h5>
            <table class="pzl-table">
                <thead>
                    <tr><th>وضعیت (ستون مقصد)</th><th>نقش‌های کاربری مجاز</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($statuses as $status): 
                        $allowed_roles = $rules[$status->slug] ?? [];
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($status->name); ?></strong></td>
                        <td>
                            <?php foreach($roles as $role_key => $role_details): ?>
                                <label style="margin-right: 15px; font-weight: normal;">
                                    <input type="checkbox" name="puzzling_settings[workflow_rules][<?php echo esc_attr($status->slug); ?>][]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $allowed_roles)); ?>>
                                    <?php echo esc_html($role_details['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="form-submit">
                <button type="submit" class="pzl-button" data-puzzling-skip-global-handler="true">ذخیره قوانین گردش کار</button>
            </div>
        </form>
    </div>

    <div class="pzl-card">
        <h5><i class="ri-user-smile-line"></i> مدیریت جایگاه‌های شغلی</h5>
        <p class="description">برای تعریف، ویرایش یا حذف دپارتمان‌ها و عناوین شغلی سازمان، لطفاً به تب **"جایگاه‌های شغلی"** در همین صفحه تنظیمات مراجعه کنید.</p>
    </div>
</div>