<?php
/**
 * Organizational Positions (Departments & Job Titles) Management Template
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$departments = get_terms(['taxonomy' => 'department', 'hide_empty' => false, 'orderby' => 'name']);
$job_titles = get_terms(['taxonomy' => 'job_title', 'hide_empty' => false, 'orderby' => 'name']);
?>

<div class="pzl-form-container">
    <h4><i class="fas fa-sitemap"></i> مدیریت جایگاه‌های شغلی</h4>
    <p class="description">در این بخش می‌توانید دپارتمان‌ها و عناوین شغلی را برای تخصیص به کارکنان تعریف، ویرایش یا حذف کنید.</p>

    <div class="pzl-positions-manager">
        <div class="pzl-positions-list pzl-card">
            <h5><i class="fas fa-building"></i> لیست دپارتمان‌ها</h5>
            <table class="pzl-table">
                <thead>
                    <tr><th>نام دپارتمان</th><th>عملیات</th></tr>
                </thead>
                <tbody id="department-list">
                    <?php if (!empty($departments) && !is_wp_error($departments)):
                        foreach($departments as $dept): ?>
                            <tr data-term-id="<?php echo esc_attr($dept->term_id); ?>">
                                <td data-label="name"><?php echo esc_html($dept->name); ?></td>
                                <td>
                                    <button class="pzl-button pzl-button-sm edit-dept-btn">ویرایش</button>
                                    <button class="pzl-button pzl-button-sm delete-dept-btn" style="background-color: var(--pzl-danger-color) !important;">حذف</button>
                                </td>
                            </tr>
                        <?php endforeach;
                    else: ?>
                        <tr><td colspan="2">هیچ دپارتمانی تعریف نشده است.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pzl-positions-form pzl-card">
            <h5 id="dept-form-title"><i class="fas fa-plus-circle"></i> افزودن دپارتمان جدید</h5>
            <form id="pzl-dept-form" class="pzl-form pzl-ajax-form" data-action="puzzling_manage_department">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="term_id" id="dept-term-id" value="0">
                <div class="form-group">
                    <label for="dept-name">نام دپارتمان</label>
                    <input type="text" id="dept-name" name="name" required>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button">ذخیره</button>
                    <button type="button" id="cancel-edit-dept" class="pzl-button-secondary" style="display: none;">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <hr style="margin: 30px 0;">

    <div class="pzl-positions-manager">
        <div class="pzl-positions-list pzl-card">
            <h5><i class="fas fa-user-tie"></i> لیست عناوین شغلی</h5>
            <table class="pzl-table">
                <thead>
                    <tr><th>عنوان شغلی</th><th>دپارتمان</th><th>عملیات</th></tr>
                </thead>
                <tbody id="job-title-list">
                    <?php if (!empty($job_titles) && !is_wp_error($job_titles)):
                        foreach($job_titles as $title):
                            $parent_dept = $title->parent ? get_term($title->parent, 'department') : null;
                        ?>
                            <tr data-term-id="<?php echo esc_attr($title->term_id); ?>" data-parent-id="<?php echo esc_attr($title->parent); ?>">
                                <td data-label="name"><?php echo esc_html($title->name); ?></td>
                                <td><?php echo $parent_dept ? esc_html($parent_dept->name) : '---'; ?></td>
                                <td>
                                    <button class="pzl-button pzl-button-sm edit-title-btn">ویرایش</button>
                                    <button class="pzl-button pzl-button-sm delete-title-btn" style="background-color: var(--pzl-danger-color) !important;">حذف</button>
                                </td>
                            </tr>
                        <?php endforeach;
                    else: ?>
                        <tr><td colspan="3">هیچ عنوان شغلی تعریف نشده است.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pzl-positions-form pzl-card">
            <h5 id="title-form-title"><i class="fas fa-plus-circle"></i> افزودن عنوان شغلی</h5>
            <form id="pzl-title-form" class="pzl-form pzl-ajax-form" data-action="puzzling_manage_job_title">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="term_id" id="title-term-id" value="0">
                <div class="form-group">
                    <label for="title-name">نام عنوان شغلی</label>
                    <input type="text" id="title-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="title-parent">دپارتمان والد</label>
                    <select id="title-parent" name="parent">
                        <option value="0">-- بدون دپارتمان --</option>
                        <?php foreach($departments as $dept) {
                            echo '<option value="'.esc_attr($dept->term_id).'">'.esc_html($dept->name).'</option>';
                        } ?>
                    </select>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button">ذخیره</button>
                    <button type="button" id="cancel-edit-title" class="pzl-button-secondary" style="display: none;">انصراف</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // --- Department UI Logic ---
    $('#department-list').on('click', '.edit-dept-btn', function() {
        var row = $(this).closest('tr');
        $('#dept-form-title').html('<i class="fas fa-edit"></i> ویرایش دپارتمان');
        $('#dept-term-id').val(row.data('term-id'));
        $('#dept-name').val(row.find('td[data-label="name"]').text()).focus();
        $('#cancel-edit-dept').show();
    });

    $('#cancel-edit-dept').on('click', function() {
        $('#dept-form-title').html('<i class="fas fa-plus-circle"></i> افزودن دپارتمان جدید');
        $('#pzl-dept-form').trigger('reset');
        $('#dept-term-id').val('0');
        $(this).hide();
    });
    
    $('#department-list').on('click', '.delete-dept-btn', function() {
        if (!confirm('آیا از حذف این دپارتمان مطمئن هستید؟ تمام عناوین شغلی زیرمجموعه آن نیز حذف خواهند شد.')) return;
        var row = $(this).closest('tr');
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_delete_department', term_id: row.data('term-id'), security: puzzlingcrm_ajax_obj.nonce },
            success: function(response) {
                if (response.success) showPuzzlingAlert('موفق', response.data.message, 'success', true);
                else showPuzzlingAlert('خطا', response.data.message, 'error');
            }
        });
    });

    // --- Job Title UI Logic ---
    $('#job-title-list').on('click', '.edit-title-btn', function() {
        var row = $(this).closest('tr');
        $('#title-form-title').html('<i class="fas fa-edit"></i> ویرایش عنوان شغلی');
        $('#title-term-id').val(row.data('term-id'));
        $('#title-name').val(row.find('td[data-label="name"]').text()).focus();
        $('#title-parent').val(row.data('parent-id'));
        $('#cancel-edit-title').show();
    });

    $('#cancel-edit-title').on('click', function() {
        $('#title-form-title').html('<i class="fas fa-plus-circle"></i> افزودن عنوان شغلی');
        $('#pzl-title-form').trigger('reset');
        $('#title-term-id').val('0');
        $(this).hide();
    });

    $('#job-title-list').on('click', '.delete-title-btn', function() {
        if (!confirm('آیا از حذف این عنوان شغلی مطمئن هستید؟')) return;
        var row = $(this).closest('tr');
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url, type: 'POST',
            data: { action: 'puzzling_delete_job_title', term_id: row.data('term-id'), security: puzzlingcrm_ajax_obj.nonce },
            success: function(response) {
                if (response.success) showPuzzlingAlert('موفق', response.data.message, 'success', true);
                else showPuzzlingAlert('خطا', response.data.message, 'error');
            }
        });
    });
});
</script>