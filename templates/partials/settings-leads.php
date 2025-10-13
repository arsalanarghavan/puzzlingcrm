<?php
/**
 * Lead Settings and Statuses Page (Corrected Version using Taxonomy)
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

// Handle saving general lead settings (SMS, default status)
if (isset($_POST['puzzling_action']) && $_POST['puzzling_action'] === 'save_lead_settings' && check_admin_referer('puzzling_save_lead_settings_nonce', 'security')) {
    $settings = get_option('puzzling_settings', []);
    
    $settings['lead_auto_sms_enabled'] = isset($_POST['lead_auto_sms_enabled']) ? 1 : 0;
    $settings['lead_auto_sms_template'] = sanitize_textarea_field($_POST['lead_auto_sms_template']);
    // Save the term SLUG, not the name.
    $settings['lead_default_status'] = sanitize_text_field($_POST['lead_default_status']);

    update_option('puzzling_settings', $settings);
    echo '<div class="notice notice-success is-dismissible"><p>تنظیمات سرنخ با موفقیت ذخیره شدند.</p></div>';
}

// Fetch statuses directly from the taxonomy
$lead_statuses = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false]);
$settings = get_option('puzzling_settings', []);
$add_nonce = wp_create_nonce('puzzling_add_lead_status_nonce');
$delete_nonce = wp_create_nonce('puzzling_delete_lead_status_nonce');
?>

<div class="pzl-form-container">
    <h4><i class="fas fa-cogs"></i> تنظیمات کلی سرنخ‌ها</h4>
    <form method="post" class="pzl-form" style="margin-top: 20px;">
        <input type="hidden" name="puzzling_action" value="save_lead_settings">
        <?php wp_nonce_field('puzzling_save_lead_settings_nonce', 'security'); ?>
        <table class="form-table">
             <tr valign="top">
                <th scope="row"><?php _e('وضعیت پیش‌فرض سرنخ', 'puzzlingcrm'); ?></th>
                <td>
                    <select id="lead_default_status" name="lead_default_status">
                        <option value=""><?php _e('هیچکدام', 'puzzlingcrm'); ?></option>
                        <?php if (!is_wp_error($lead_statuses) && !empty($lead_statuses)) : ?>
                            <?php foreach ($lead_statuses as $status) : ?>
                                <option value="<?php echo esc_attr($status->slug); ?>" <?php selected(isset($settings['lead_default_status']) ? $settings['lead_default_status'] : '', $status->slug); ?>>
                                    <?php echo esc_html($status->name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="description"><?php _e('وضعیت پیش‌فرض برای سرنخ‌های جدید را انتخاب کنید.', 'puzzlingcrm'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('فعال‌سازی پیامک خودکار', 'puzzlingcrm'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="lead_auto_sms_enabled" value="1" <?php checked(isset($settings['lead_auto_sms_enabled']) ? $settings['lead_auto_sms_enabled'] : 0, 1); ?> />
                        <?php _e('هنگام ثبت سرنخ جدید، پیامک خودکار ارسال شود.', 'puzzlingcrm'); ?>
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('قالب پیامک خودکار', 'puzzlingcrm'); ?></th>
                <td>
                    <textarea name="lead_auto_sms_template" rows="5" cols="50" class="large-text"><?php echo isset($settings['lead_auto_sms_template']) ? esc_textarea($settings['lead_auto_sms_template']) : ''; ?></textarea>
                    <p class="description">
                        <?php _e('می‌توانید از متغیرهای زیر استفاده کنید:', 'puzzlingcrm'); ?>
                        <code>{first_name}</code>, <code>{last_name}</code>, <code>{business_name}</code>
                    </p>
                </td>
            </tr>
        </table>
        <div class="form-submit">
            <button type="submit" class="pzl-button">ذخیره تنظیمات</button>
        </div>
    </form>
</div>


<div class="pzl-form-container" style="margin-top: 40px;">
    <h4><i class="fas fa-tags"></i> مدیریت وضعیت‌های سرنخ</h4>
    <p class="description">در این بخش می‌توانید وضعیت‌های مختلف برای سرنخ‌ها را تعریف کنید.</p>
    
    <div class="pzl-form" style="margin-top: 20px;">
        <div id="add-new-status-form" style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
            <input type="text" id="new-status-name" placeholder="نام وضعیت جدید" required>
            <button type="button" id="add-lead-status-btn" class="pzl-button" data-nonce="<?php echo esc_attr($add_nonce); ?>">افزودن وضعیت</button>
        </div>

        <table class="pzl-table" id="lead-statuses-table">
            <thead>
                <tr>
                    <th>نام وضعیت</th>
                    <th style="width: 100px;">عملیات</th>
                </tr>
            </thead>
            <tbody id="lead-statuses-wrapper">
                <?php if (!is_wp_error($lead_statuses) && !empty($lead_statuses)) : foreach ($lead_statuses as $status) : ?>
                    <tr data-term-id="<?php echo esc_attr($status->term_id); ?>">
                        <td><?php echo esc_html($status->name); ?></td>
                        <td>
                            <button type="button" class="pzl-button pzl-button-sm pzl-delete-status-btn" data-nonce="<?php echo esc_attr($delete_nonce); ?>" style="background: #dc3545 !important;">حذف</button>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr id="no-statuses-row"><td colspan="2">هیچ وضعیتی یافت نشد.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Add new lead status
    $('#add-lead-status-btn').on('click', function() {
        const button = $(this);
        const statusName = $('#new-status-name').val().trim();
        if (!statusName) {
            alert('لطفا نام وضعیت را وارد کنید.');
            return;
        }

        $.ajax({
            url: window.puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_add_lead_status',
                security: button.data('nonce'),
                status_name: statusName
            },
            beforeSend: function() {
                button.prop('disabled', true).text('در حال افزودن...');
            },
            success: function(response) {
                if (response.success) {
                    const status = response.data;
                    const newRow = `
                        <tr data-term-id="${status.term_id}">
                            <td>${status.name}</td>
                            <td>
                                <button type="button" class="pzl-button pzl-button-sm pzl-delete-status-btn" data-nonce="<?php echo esc_attr($delete_nonce); ?>" style="background: #dc3545 !important;">حذف</button>
                            </td>
                        </tr>`;
                    $('#no-statuses-row').remove();
                    $('#lead-statuses-wrapper').append(newRow);
                    $('#new-status-name').val('');
                    // Add to default status dropdown as well
                    $('#lead_default_status').append($('<option>', {
                        value: status.slug,
                        text: status.name
                    }));
                } else {
                    alert('خطا: ' + response.data.message);
                }
            },
            complete: function() {
                button.prop('disabled', false).text('افزودن وضعیت');
            }
        });
    });

    // Delete lead status
    $('#lead-statuses-table').on('click', '.pzl-delete-status-btn', function() {
        if (!confirm('آیا از حذف این وضعیت مطمئن هستید؟ سرنخ‌هایی که این وضعیت را دارند، بدون وضعیت خواهند شد.')) {
            return;
        }
        
        const button = $(this);
        const row = button.closest('tr');
        const termId = row.data('term-id');

        $.ajax({
            url: window.puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_lead_status',
                security: button.data('nonce'),
                term_id: termId
            },
            beforeSend: function() {
                row.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    // Remove from default status dropdown
                    $('#lead_default_status option[value="' + response.data.slug + '"]').remove();
                    row.remove();
                } else {
                    alert('خطا: ' + response.data.message);
                    row.css('opacity', '1');
                }
            }
        });
    });
});
</script>