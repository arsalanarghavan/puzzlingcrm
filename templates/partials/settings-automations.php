<?php
/**
 * Workflow Automations Settings Page Template
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
$automations = $settings['automations'] ?? [];

$statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false, 'orderby' => 'term_order']);
$users = get_users(['role__in' => ['system_manager', 'team_member', 'administrator']]);

$triggers = [
    'status_changed' => 'وقتی وضعیت وظیفه تغییر می‌کند',
    'comment_added' => 'وقتی نظری جدید ثبت می‌شود',
    'file_attached' => 'وقتی فایلی پیوست می‌شود',
];
$actions = [
    'change_status' => 'تغییر وضعیت به',
    'assign_user' => 'تخصیص به کاربر',
    'add_comment' => 'افزودن کامنت',
];
?>

<div class="pzl-form-container">
    <h4><i class="fas fa-robot"></i> اتوماسیون گردش کار</h4>
    <p class="description">قوانین خودکار برای فرآیندهای تکراری تعریف کنید. برای مثال: "اگر وضعیت به 'نیاز به بازبینی' تغییر کرد، وظیفه را به مدیر سیستم تخصیص بده."</p>
    
    <form id="puzzling-automations-form" method="post" class="pzl-form" style="margin-top: 20px;">
        <?php wp_nonce_field('puzzling_save_settings_nonce', 'security'); ?>

        <div id="automations-container">
            <?php 
            $i = 0;
            if (!empty($automations)) :
                foreach ($automations as $index => $automation) : $i = $index; ?>
                <div class="automation-rule pzl-card" style="margin-bottom: 15px; background: #f9f9f9;">
                    <div class="pzl-form-row">
                        <div class="form-group">
                            <label><strong>اگر این اتفاق افتاد (Trigger):</strong></label>
                            <select name="puzzling_settings[automations][<?php echo $i; ?>][trigger]">
                                <?php foreach($triggers as $key => $label) { echo '<option value="'.$key.'" '.selected($automation['trigger'], $key, false).'>'.$label.'</option>'; } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>آنگاه این کار را انجام بده (Action):</strong></label>
                            <select name="puzzling_settings[automations][<?php echo $i; ?>][action]">
                                <?php foreach($actions as $key => $label) { echo '<option value="'.$key.'" '.selected($automation['action'], $key, false).'>'.$label.'</option>'; } ?>
                            </select>
                        </div>
                        <div class="form-group">
                             <label><strong>با این مقدار:</strong></label>
                             <input type="text" name="puzzling_settings[automations][<?php echo $i; ?>][value]" value="<?php echo esc_attr($automation['value']); ?>" placeholder="شناسه یا متن مقدار">
                        </div>
                        <button type="button" class="pzl-button pzl-button-sm remove-automation-rule" style="align-self: flex-end;">حذف</button>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <button type="button" id="add-automation-rule" class="pzl-button">افزودن قانون جدید</button>
        
        <div class="form-submit">
            <button type="submit" class="pzl-button">ذخیره قوانین اتوماسیون</button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    let ruleIndex = <?php echo $i + 1; ?>;
    $('#add-automation-rule').on('click', function() {
        const ruleHtml = `
            <div class="automation-rule pzl-card" style="margin-bottom: 15px; background: #f9f9f9;">
                <div class="pzl-form-row">
                    <div class="form-group">
                        <label><strong>اگر این اتفاق افتاد (Trigger):</strong></label>
                        <select name="puzzling_settings[automations][${ruleIndex}][trigger]">
                            <?php foreach($triggers as $key => $label) { echo '<option value="'.$key.'">'.$label.'</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>آنگاه این کار را انجام بده (Action):</strong></label>
                        <select name="puzzling_settings[automations][${ruleIndex}][action]">
                             <?php foreach($actions as $key => $label) { echo '<option value="'.$key.'">'.$label.'</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                         <label><strong>با این مقدار:</strong></label>
                         <input type="text" name="puzzling_settings[automations][${ruleIndex}][value]" placeholder="شناسه وضعیت، شناسه کاربر یا متن کامنت">
                    </div>
                    <button type="button" class="pzl-button pzl-button-sm remove-automation-rule" style="align-self: flex-end;">حذف</button>
                </div>
            </div>`;
        $('#automations-container').append(ruleHtml);
        ruleIndex++;
    });

    $('#automations-container').on('click', '.remove-automation-rule', function() {
        $(this).closest('.automation-rule').remove();
    });
});
</script>