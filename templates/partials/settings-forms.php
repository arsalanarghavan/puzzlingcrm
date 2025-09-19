<?php
/**
 * Forms Settings Page Template for System Manager - Full Frontend CRUD
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$form_to_edit = ($form_id > 0) ? get_post($form_id) : null;
?>
<div class="pzl-form-container">
    <?php if ($action === 'edit' || $action === 'new'): ?>
        <div class="pzl-card-header" style="border: none; padding-bottom: 0;">
            <h3><i class="fas fa-edit"></i> <?php echo $form_id > 0 ? __('ویرایش فرم', 'puzzlingcrm') : __('ایجاد فرم جدید', 'puzzlingcrm'); ?></h3>
            <a href="<?php echo remove_query_arg(['action', 'form_id']); ?>" class="pzl-button">&larr; <?php _e('بازگشت به لیست فرم‌ها', 'puzzlingcrm'); ?></a>
        </div>
        <form id="manage-form" method="post" class="pzl-form" style="margin-top: 20px;">
            <?php wp_nonce_field('puzzling_manage_form_nonce', 'security'); ?>
            <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">

            <div class="form-group">
                <label for="form_title"><?php _e('عنوان فرم', 'puzzlingcrm'); ?></label>
                <input type="text" id="form_title" name="form_title" value="<?php echo $form_to_edit ? esc_attr($form_to_edit->post_title) : ''; ?>" required>
            </div>
             <div class="form-group">
                <label for="form_content"><?php _e('توضیحات فرم (به مشتری نمایش داده می‌شود)', 'puzzlingcrm'); ?></label>
                <textarea id="form_content" name="form_content" rows="4"><?php echo $form_to_edit ? esc_textarea($form_to_edit->post_content) : ''; ?></textarea>
            </div>
            
            <hr>
            <h4><?php _e('فیلدهای فرم', 'puzzlingcrm'); ?></h4>
            <div id="form-fields-container">
                 <?php 
                 $fields = $form_to_edit ? get_post_meta($form_id, '_form_fields', true) : [];
                 if (!empty($fields) && is_array($fields)): foreach ($fields as $index => $field): ?>
                    <div class="form-field-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                        <input type="text" name="form_fields[<?php echo $index; ?>][label]" value="<?php echo esc_attr($field['label']); ?>" placeholder="<?php _e('برچسب فیلد', 'puzzlingcrm'); ?>" style="flex-grow: 1;">
                        <label style="margin-bottom: 0;"><input type="checkbox" name="form_fields[<?php echo $index; ?>][required]" value="1" <?php checked($field['required'] ?? false, true); ?>> <?php _e('ضروری', 'puzzlingcrm'); ?></label>
                        <button type="button" class="pzl-button pzl-button-sm remove-field" style="background: #dc3545 !important;"><?php _e('حذف', 'puzzlingcrm'); ?></button>
                    </div>
                <?php endforeach; endif; ?>
            </div>
             <button type="button" id="add-form-field" class="pzl-button" style="align-self: flex-start;"><?php _e('افزودن فیلد', 'puzzlingcrm'); ?></button>

            <div class="form-submit">
                <button type="submit" class="pzl-button"><?php echo $form_id > 0 ? __('ذخیره تغییرات', 'puzzlingcrm') : __('ایجاد فرم', 'puzzlingcrm'); ?></button>
            </div>
        </form>
        
        <script>
            jQuery(document).ready(function($) {
                let fieldIndex = <?php echo (is_array($fields) ? count($fields) : 0); ?>;
                $('#add-form-field').on('click', function() {
                    $('#form-fields-container').append(`
                        <div class="form-field-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                            <input type="text" name="form_fields[${fieldIndex}][label]" placeholder="<?php _e('برچسب فیلد', 'puzzlingcrm'); ?>" style="flex-grow: 1;">
                            <label style="margin-bottom: 0;"><input type="checkbox" name="form_fields[${fieldIndex}][required]" value="1"> <?php _e('ضروری', 'puzzlingcrm'); ?></label>
                            <button type="button" class="pzl-button pzl-button-sm remove-field" style="background: #dc3545 !important;"><?php _e('حذف', 'puzzlingcrm'); ?></button>
                        </div>
                    `);
                    fieldIndex++;
                });
                $('#form-fields-container').on('click', '.remove-field', function() {
                    $(this).closest('.form-field-row').remove();
                });
            });
        </script>

    <?php else: // List view ?>
        <div class="pzl-card-header" style="border: none; padding-bottom: 0;">
             <h4><i class="fas fa-clipboard-list"></i> <?php _e('مدیریت فرم‌ها', 'puzzlingcrm'); ?></h4>
             <a href="<?php echo add_query_arg('action', 'new'); ?>" class="pzl-button"><?php _e('ایجاد فرم جدید', 'puzzlingcrm'); ?></a>
        </div>
        <p class="description"><?php _e('فرم‌هایی ایجاد کنید تا پس از خرید محصول به مشتری نمایش داده شوند و پروژه به صورت خودکار ایجاد شود.', 'puzzlingcrm'); ?></p>
        
        <table class="pzl-table" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th><?php _e('عنوان فرم', 'puzzlingcrm'); ?></th>
                    <th><?php _e('تعداد فیلدها', 'puzzlingcrm'); ?></th>
                    <th><?php _e('عملیات', 'puzzlingcrm'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $forms = get_posts(['post_type' => 'pzl_form', 'posts_per_page' => -1]); ?>
                <?php if (!empty($forms)): foreach ($forms as $form): 
                    $fields = get_post_meta($form->ID, '_form_fields', true);
                    $field_count = is_array($fields) ? count($fields) : 0;
                    $delete_url = add_query_arg([
                        'puzzling_action' => 'delete_form',
                        'form_id' => $form->ID,
                        '_wpnonce' => wp_create_nonce('puzzling_delete_form_' . $form->ID)
                    ]);
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($form->post_title); ?></strong></td>
                        <td><?php echo esc_html($field_count); ?></td>
                        <td>
                            <a href="<?php echo add_query_arg(['action' => 'edit', 'form_id' => $form->ID]); ?>" class="pzl-button pzl-button-sm"><?php _e('ویرایش', 'puzzlingcrm'); ?></a>
                            <a href="<?php echo esc_url($delete_url); ?>" class="pzl-button pzl-button-sm" style="background: #dc3545 !important;" onclick="return confirm('<?php _e('آیا از حذف این فرم مطمئن هستید؟', 'puzzlingcrm'); ?>');"><?php _e('حذف', 'puzzlingcrm'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="3"><?php _e('هیچ فرمی یافت نشد. برای شروع یک فرم جدید ایجاد کنید.', 'puzzlingcrm'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>