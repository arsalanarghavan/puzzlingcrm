<?php
/**
 * Organizational Positions Management Template for Frontend
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$positions = get_terms(['taxonomy' => 'organizational_position', 'hide_empty' => false, 'orderby' => 'name']);
?>

<div class="pzl-form-container">
    <h4><i class="fas fa-sitemap"></i> مدیریت جایگاه‌های شغلی</h4>
    <p class="description">در این بخش می‌توانید دپارتمان‌ها و عناوین شغلی را برای تخصیص به کارکنان تعریف، ویرایش یا حذف کنید.</p>

    <div class="pzl-positions-manager">
        <div class="pzl-positions-list pzl-card">
            <h5><i class="fas fa-list-ul"></i> لیست جایگاه‌ها</h5>
            <table class="pzl-table">
                <thead>
                    <tr>
                        <th>نام جایگاه</th>
                        <th>تعداد کارکنان</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($positions) && !is_wp_error($positions)): ?>
                        <?php foreach($positions as $pos): ?>
                            <tr data-term-id="<?php echo esc_attr($pos->term_id); ?>">
                                <td data-label="name"><?php echo esc_html($pos->name); ?></td>
                                <td><?php echo esc_html($pos->count); ?></td>
                                <td>
                                    <button class="pzl-button pzl-button-sm edit-position-btn">ویرایش</button>
                                    <button class="pzl-button pzl-button-sm delete-position-btn" style="background-color: var(--pzl-danger-color) !important;">حذف</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">هیچ جایگاه شغلی تعریف نشده است.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pzl-positions-form pzl-card">
            <h5 id="position-form-title"><i class="fas fa-plus-circle"></i> افزودن جایگاه جدید</h5>
            <form id="pzl-position-form" class="pzl-form pzl-ajax-form" data-action="puzzling_manage_position">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="term_id" id="position-term-id" value="0">
                <div class="form-group">
                    <label for="position-name">نام جایگاه</label>
                    <input type="text" id="position-name" name="name" required>
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
    // Handle Edit Button Click
    $('.pzl-positions-list').on('click', '.edit-position-btn', function() {
        var row = $(this).closest('tr');
        var termId = row.data('term-id');
        var name = row.find('td[data-label="name"]').text();

        $('#position-form-title').html('<i class="fas fa-edit"></i> ویرایش جایگاه');
        $('#position-term-id').val(termId);
        $('#position-name').val(name).focus();
        $('#cancel-edit-position').show();
    });

    // Handle Cancel Edit Button
    $('#cancel-edit-position').on('click', function() {
        $('#position-form-title').html('<i class="fas fa-plus-circle"></i> افزودن جایگاه جدید');
        $('#pzl-position-form').trigger('reset');
        $('#position-term-id').val('0');
        $(this).hide();
    });

    // Handle Delete Button Click
    $('.pzl-positions-list').on('click', '.delete-position-btn', function() {
        if (!confirm('آیا از حذف این جایگاه مطمئن هستید؟')) {
            return;
        }

        var row = $(this).closest('tr');
        var termId = row.data('term-id');
        var security = $('#pzl-position-form').find('input[name="security"]').val();

        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_position',
                term_id: termId,
                security: security
            },
            beforeSend: function() {
                row.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() { $(this).remove(); });
                    // SweetAlert is defined in the main scripts file
                    if(typeof showPuzzlingAlert !== 'undefined') {
                        showPuzzlingAlert('موفق', response.data.message, 'success');
                    } else {
                        alert(response.data.message);
                    }
                } else {
                    row.css('opacity', '1');
                    if(typeof showPuzzlingAlert !== 'undefined') {
                        showPuzzlingAlert('خطا', response.data.message, 'error');
                    } else {
                        alert(response.data.message);
                    }
                }
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
@media (max-width: 768px) {
    .pzl-positions-manager {
        grid-template-columns: 1fr;
    }
}
</style>