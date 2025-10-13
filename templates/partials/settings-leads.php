<?php
/**
 * Lead Settings and Statuses Page
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

// Handle saving lead statuses
if ( isset( $_POST['puzzling_action'] ) && $_POST['puzzling_action'] === 'save_lead_statuses' && check_admin_referer('puzzling_save_lead_statuses_nonce', 'security') ) {
    $statuses = [];
    if ( isset( $_POST['status_name'] ) ) {
        for ( $i = 0; $i < count( $_POST['status_name'] ); $i++ ) {
            if ( ! empty( $_POST['status_name'][$i] ) ) {
                $statuses[] = [
                    'name'  => sanitize_text_field( $_POST['status_name'][$i] ),
                    'color' => sanitize_hex_color( $_POST['status_color'][$i] ),
                ];
            }
        }
    }
    update_option( 'puzzling_lead_statuses', $statuses );
    echo '<div class="notice notice-success is-dismissible"><p>وضعیت‌های لید با موفقیت ذخیره شدند.</p></div>';
}

// Handle saving general lead settings (SMS, default status)
if (isset($_POST['puzzling_action']) && $_POST['puzzling_action'] === 'save_lead_settings' && check_admin_referer('puzzling_save_lead_settings_nonce', 'security')) {
    $settings = get_option('puzzling_settings', []);
    
    $settings['lead_auto_sms_enabled'] = isset($_POST['lead_auto_sms_enabled']) ? 1 : 0;
    $settings['lead_auto_sms_template'] = sanitize_textarea_field($_POST['lead_auto_sms_template']);
    $settings['lead_default_status'] = sanitize_text_field($_POST['lead_default_status']);

    update_option('puzzling_settings', $settings);
    echo '<div class="notice notice-success is-dismissible"><p>تنظیمات سرنخ با موفقیت ذخیره شدند.</p></div>';
}


$lead_statuses = get_option('puzzling_lead_statuses', [['name' => 'جدید', 'color' => '#0073aa']]);
$settings = get_option('puzzling_settings', []);
?>

<div class="pzl-form-container">
    <h4><i class="fas fa-cogs"></i> تنظیمات کلی سرنخ‌ها</h4>
    <form method="post" class="pzl-form" style="margin-top: 20px;">
        <input type="hidden" name="puzzling_action" value="save_lead_settings">
        <?php wp_nonce_field('puzzling_save_lead_settings_nonce', 'security'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('فعال‌سازی پیامک خودکار', 'puzzlingcrm'); ?></th>
                <td>
                    <label for="lead_auto_sms_enabled">
                        <input type="checkbox" id="lead_auto_sms_enabled" name="lead_auto_sms_enabled" value="1" <?php checked(isset($settings['lead_auto_sms_enabled']) ? $settings['lead_auto_sms_enabled'] : 0, 1); ?> />
                        <?php _e('هنگام ثبت سرنخ جدید، پیامک خودکار ارسال شود.', 'puzzlingcrm'); ?>
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('قالب پیامک خودکار', 'puzzlingcrm'); ?></th>
                <td>
                    <textarea id="lead_auto_sms_template" name="lead_auto_sms_template" rows="5" cols="50"><?php echo isset($settings['lead_auto_sms_template']) ? esc_textarea($settings['lead_auto_sms_template']) : ''; ?></textarea>
                    <p class="description">
                        <?php _e('می‌توانید از متغیرهای زیر استفاده کنید:', 'puzzlingcrm'); ?>
                        <code>{first_name}</code>, <code>{last_name}</code>, <code>{business_name}</code>
                    </p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('وضعیت پیش‌فرض سرنخ', 'puzzlingcrm'); ?></th>
                <td>
                    <select id="lead_default_status" name="lead_default_status">
                        <?php foreach ($lead_statuses as $status) : ?>
                            <option value="<?php echo esc_attr($status['name']); ?>" <?php selected(isset($settings['lead_default_status']) ? $settings['lead_default_status'] : '', $status['name']); ?>>
                                <?php echo esc_html($status['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('وضعیت پیش‌فرض برای سرنخ‌های جدید را انتخاب کنید.', 'puzzlingcrm'); ?></p>
                </td>
            </tr>
        </table>
        <div class="form-submit">
            <button type="submit" class="pzl-button">ذخیره تنظیمات</button>
        </div>
    </form>
</div>


<div class="pzl-form-container" style="margin-top: 40px;">
    <h4><i class="fas fa-users-cog"></i> مدیریت وضعیت‌های لید</h4>
    <p class="description">در این بخش می‌توانید وضعیت‌های مختلف برای سرنخ‌ها (لیدها) را تعریف کنید. وضعیت اول به عنوان وضعیت پیش‌فرض برای لیدهای جدید در نظر گرفته می‌شود (مگر اینکه در تنظیمات بالا وضعیت دیگری را انتخاب کنید).</p>
    <form method="post" class="pzl-form" style="margin-top: 20px;">
        <input type="hidden" name="puzzling_action" value="save_lead_statuses">
        <?php wp_nonce_field('puzzling_save_lead_statuses_nonce', 'security'); ?>
        
        <div id="lead-statuses-wrapper">
            <?php if ( ! empty( $lead_statuses ) ) : foreach ( $lead_statuses as $status ) : ?>
                <div class="lead-status-row form-group" style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" name="status_name[]" placeholder="نام وضعیت" value="<?php echo esc_attr($status['name']); ?>" required>
                    <input type="color" name="status_color[]" value="<?php echo esc_attr($status['color']); ?>">
                    <button type="button" class="pzl-button pzl-button-sm remove-status-row" style="background: #dc3545 !important;">حذف</button>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <button type="button" id="add-lead-status-row" class="pzl-button pzl-button-secondary">افزودن وضعیت جدید</button>

        <div class="form-submit" style="margin-top: 20px;">
            <button type="submit" class="pzl-button">ذخیره وضعیت‌ها</button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-lead-status-row').on('click', function() {
        var newRow = `
            <div class="lead-status-row form-group" style="display: flex; gap: 10px; align-items: center;">
                <input type="text" name="status_name[]" placeholder="نام وضعیت" required>
                <input type="color" name="status_color[]" value="#cccccc">
                <button type="button" class="pzl-button pzl-button-sm remove-status-row" style="background: #dc3545 !important;">حذف</button>
            </div>`;
        $('#lead-statuses-wrapper').append(newRow);
    });

    $('#lead-statuses-wrapper').on('click', '.remove-status-row', function() {
        $(this).closest('.lead-status-row').remove();
    });
});
</script>