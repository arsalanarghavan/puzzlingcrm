<?php
/**
 * Task Categories Management Template for Frontend - CORRECTED
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$categories = get_terms(['taxonomy' => 'task_category', 'hide_empty' => false, 'orderby' => 'name']);
?>

<div class="pzl-form-container">
    <h4><i class="ri-price-tag-lines"></i> مدیریت دسته‌بندی وظایف</h4>
    <p class="description">در این بخش می‌توانید دسته‌بندی‌های مختلف را برای وظایف تعریف، ویرایش یا حذف کنید.</p>

    <div class="pzl-positions-manager">
        <div class="pzl-positions-list pzl-card">
            <h5><i class="ri-list-check"></i> لیست دسته‌بندی‌ها</h5>
            <table class="pzl-table">
                <thead>
                    <tr>
                        <th>نام دسته‌بندی</th>
                        <th>تعداد وظایف</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                        <?php foreach($categories as $cat): ?>
                            <tr data-term-id="<?php echo esc_attr($cat->term_id); ?>">
                                <td data-label="name"><?php echo esc_html($cat->name); ?></td>
                                <td><?php echo esc_html($cat->count); ?></td>
                                <td>
                                    <button class="pzl-button pzl-button-sm edit-category-btn">ویرایش</button>
                                    <button class="pzl-button pzl-button-sm delete-category-btn" style="background-color: var(--pzl-danger-color) !important;">حذف</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">هیچ دسته‌بندی برای وظایف تعریف نشده است.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pzl-positions-form pzl-card">
            <h5 id="category-form-title"><i class="ri-add-circle-line"></i> افزودن دسته‌بندی جدید</h5>
            <form id="pzl-category-form" class="pzl-form pzl-ajax-form" data-action="puzzling_manage_task_category">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="term_id" id="category-term-id" value="0">
                <div class="form-group">
                    <label for="category-name">نام دسته‌بندی</label>
                    <input type="text" id="category-name" name="name" required>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button">ذخیره</button>
                    <button type="button" id="cancel-edit-category" class="pzl-button-secondary" style="display: none;">انصراف</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // This script now only handles the UI interactions (edit/cancel/delete), not the form submission itself.

    // Handle Edit Button Click
    $('.pzl-positions-list').on('click', '.edit-category-btn', function() {
        var row = $(this).closest('tr');
        var termId = row.data('term-id');
        var name = row.find('td[data-label="name"]').text();

        $('#category-form-title').html('<i class="ri-edit-line"></i> ویرایش دسته‌بندی');
        $('#category-term-id').val(termId);
        $('#category-name').val(name).focus();
        $('#cancel-edit-category').show();
    });

    // Handle Cancel Edit Button
    $('#cancel-edit-category').on('click', function() {
        $('#category-form-title').html('<i class="ri-add-circle-line"></i> افزودن دسته‌بندی جدید');
        $('#pzl-category-form').trigger('reset');
        $('#category-term-id').val('0');
        $(this).hide();
    });

    // Handle Delete Button Click
    $('.pzl-positions-list').on('click', '.delete-category-btn', function() {
        if (!confirm('آیا از حذف این دسته‌بندی مطمئن هستید؟')) {
            return;
        }
        var row = $(this).closest('tr');
        var termId = row.data('term-id');
        // Correctly get the nonce from the ajax object which is localized for the script
        var securityNonce = puzzlingcrm_ajax_obj.nonce;

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_task_category',
                term_id: termId,
                security: securityNonce
            },
            beforeSend: function() {
                row.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    // Use the global alert function and reload the page on success
                    showPuzzlingAlert('موفق', response.data.message, 'success', true);
                } else {
                    showPuzzlingAlert('خطا', response.data.message, 'error');
                    row.css('opacity', '1');
                }
            },
            error: function() {
                showPuzzlingAlert('خطا', 'خطای سرور رخ داد.', 'error');
                row.css('opacity', '1');
            }
        });
    });
});
</script>

<style>
/* This style ensures the two-column layout */
.pzl-positions-manager {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--pzl-spacing);
    align-items: flex-start;
}
@media (max-width: 900px) {
    .pzl-positions-manager {
        grid-template-columns: 1fr;
    }
}
</style>