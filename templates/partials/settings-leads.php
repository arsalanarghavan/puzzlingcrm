<?php
/**
 * Lead Statuses Settings Page
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

// Handle form submission
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

$lead_statuses = get_option('puzzling_lead_statuses', [['name' => 'جدید', 'color' => '#0073aa']]);
?>
<div class="pzl-form-container">
    <h4><i class="fas fa-users-cog"></i> مدیریت وضعیت‌های لید</h4>
    <p class="description">در این بخش می‌توانید وضعیت‌های مختلف برای سرنخ‌ها (لیدها) را تعریف کنید. وضعیت اول به عنوان وضعیت پیش‌فرض برای لیدهای جدید در نظر گرفته می‌شود.</p>
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
            <button type="submit" class="pzl-button">ذخیره تنظیمات</button>
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