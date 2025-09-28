<?php
/**
 * Organizational Positions Management Template (Hierarchical View - CORRECTED)
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

// Function to display terms hierarchically
function pzl_display_positions_tree($parent_id = 0, $level = 0) {
    $terms = get_terms(['taxonomy' => 'organizational_position', 'hide_empty' => false, 'parent' => $parent_id, 'orderby' => 'name']);
    if (empty($terms) || is_wp_error($terms)) {
        return;
    }

    foreach ($terms as $term) {
        ?>
        <tr data-term-id="<?php echo esc_attr($term->term_id); ?>" data-parent-id="<?php echo esc_attr($term->parent); ?>">
            <td data-label="name" style="padding-right: <?php echo $level * 20 + 10; ?>px;">
                <?php echo ($level > 0 ? '&#9492;&nbsp;' : ''); // Sub-item indicator ?>
                <strong><?php echo esc_html($term->name); ?></strong>
            </td>
            <td><?php echo esc_html($term->count); ?></td>
            <td>
                <button class="pzl-button pzl-button-sm edit-position-btn">ویرایش</button>
                <button class="pzl-button pzl-button-sm delete-position-btn" style="background-color: var(--pzl-danger-color) !important;">حذف</button>
            </td>
        </tr>
        <?php
        pzl_display_positions_tree($term->term_id, $level + 1);
    }
}
?>

<div class="pzl-form-container">
    <h4><i class="fas fa-sitemap"></i> مدیریت جایگاه‌های سازمانی</h4>
    <p class="description">در این بخش می‌توانید دپارتمان‌ها (سطح اصلی) و عناوین شغلی (زیرمجموعه‌ها) را مدیریت کنید.</p>

    <div class="pzl-positions-manager">
        <div class="pzl-positions-list pzl-card">
            <h5><i class="fas fa-list-ul"></i> ساختار سازمانی</h5>
            <table class="pzl-table">
                <thead>
                    <tr>
                        <th>نام (دپارتمان / عنوان شغلی)</th>
                        <th>تعداد کارکنان</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php pzl_display_positions_tree(); ?>
                </tbody>
            </table>
        </div>

        <div class="pzl-positions-form pzl-card">
            <h5 id="position-form-title"><i class="fas fa-plus-circle"></i> افزودن جایگاه جدید</h5>
            <form id="pzl-position-form" class="pzl-form pzl-ajax-form" data-action="puzzling_manage_position">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="term_id" id="position-term-id" value="0">
                <div class="form-group">
                    <label for="position-name">نام جایگاه (دپارتمان یا عنوان شغلی)</label>
                    <input type="text" id="position-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="position-parent">دپارتمان والد (اختیاری)</label>
                    <?php
                        wp_dropdown_categories([
                            'taxonomy'         => 'organizational_position',
                            'name'             => 'parent',
                            'id'               => 'position-parent',
                            'show_option_none' => __('این یک دپارتمان اصلی است', 'puzzlingcrm'),
                            'hierarchical'     => true,
                            'hide_empty'       => false,
                            'orderby'          => 'name',
                            'order'            => 'ASC'
                        ]);
                    ?>
                     <p class="description">برای ایجاد یک عنوان شغلی، دپارتمان والد آن را انتخاب کنید.</p>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button">ذخیره</button>
                    <button type="button" id="cancel-edit-position" class="pzl-button-secondary" style="display: none;">انصراف</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Edit Button Click
    $('.pzl-positions-list').on('click', '.edit-position-btn', function() {
        var row = $(this).closest('tr');
        var termId = row.data('term-id');
        var parentId = row.data('parent-id');
        var name = row.find('td[data-label="name"]').text().replace('↳ ', '').trim();

        $('#position-form-title').html('<i class="fas fa-edit"></i> ویرایش جایگاه');
        $('#position-term-id').val(termId);
        $('#position-name').val(name).focus();
        $('#position-parent').val(parentId);
        $('#cancel-edit-position').show();
    });

    // Cancel Edit Button
    $('#cancel-edit-position').on('click', function() {
        $('#position-form-title').html('<i class="fas fa-plus-circle"></i> افزودن جایگاه جدید');
        $('#pzl-position-form').trigger('reset');
        $('#position-term-id').val('0');
        $('#position-parent').val(0);
        $(this).hide();
    });

    // Delete Button Click
    $('.pzl-positions-list').on('click', '.delete-position-btn', function() {
        if (!confirm('آیا از حذف این جایگاه مطمئن هستید؟ تمام زیرمجموعه‌های آن نیز حذف خواهند شد.')) {
            return;
        }
        var row = $(this).closest('tr');
        var termId = row.data('term-id');
        var securityNonce = puzzlingcrm_ajax_obj.nonce;

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_position',
                term_id: termId,
                security: securityNonce
            },
            beforeSend: function() { row.css('opacity', '0.5'); },
            success: function(response) {
                if (response.success) {
                    showPuzzlingAlert('موفق', response.data.message, 'success', true);
                } else {
                    showPuzzlingAlert('خطا', response.data.message, 'error');
                    row.css('opacity', '1');
                }
            },
            error: function() {
                showPuzzlingAlert('خطا', 'خطای سرور.', 'error');
                row.css('opacity', '1');
            }
        });
    });
});
</script>

<style>
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