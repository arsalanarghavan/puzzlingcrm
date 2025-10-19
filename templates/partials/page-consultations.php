<?php
/**
 * Template for System Manager to Manage Consultations - VISUALLY REVAMPED & FIXED
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

// Ensure default consultation statuses exist. This is a robust fix for plugin updates.
if ( ! term_exists( 'in-progress', 'consultation_status' ) ) {
    PuzzlingCRM_CPT_Manager::create_default_terms();
}

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$consultation_id = isset($_GET['consultation_id']) ? intval($_GET['consultation_id']) : 0;
$item_to_edit = ($consultation_id > 0) ? get_post($consultation_id) : null;
?>
<div class="pzl-dashboard-section">
    <?php if ($action === 'edit' || $action === 'new'): ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="ri-customer-service-line"></i> <?php echo $item_to_edit ? 'ویرایش مشاوره' : 'ثبت مشاوره جدید'; ?></h3>
                <a href="<?php echo remove_query_arg(['action', 'consultation_id']); ?>" class="pzl-button">&larr; بازگشت به لیست</a>
            </div>
            <form method="post" class="pzl-form pzl-ajax-form" data-action="puzzling_manage_consultation">
                <input type="hidden" name="consultation_id" value="<?php echo esc_attr($consultation_id); ?>">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>

                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="name">نام درخواست کننده (ضروری)</label>
                        <input type="text" id="name" name="name" value="<?php echo $item_to_edit ? esc_attr($item_to_edit->post_title) : ''; ?>" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="phone">شماره تماس (ضروری)</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo $item_to_edit ? esc_attr(get_post_meta($consultation_id, '_consultation_phone', true)) : ''; ?>" required class="ltr-input">
                    </div>
                </div>

                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="email">ایمیل</label>
                        <input type="email" id="email" name="email" value="<?php echo $item_to_edit ? esc_attr(get_post_meta($consultation_id, '_consultation_email', true)) : ''; ?>" class="ltr-input">
                    </div>
                    <div class="form-group half-width">
                        <label for="type">نوع مشاوره</label>
                        <select id="type" name="type" required>
                            <?php 
                            $current_type = $item_to_edit ? get_post_meta($consultation_id, '_consultation_type', true) : 'phone';
                            ?>
                            <option value="phone" <?php selected($current_type, 'phone'); ?>>تلفنی</option>
                            <option value="in-person" <?php selected($current_type, 'in-person'); ?>>حضوری</option>
                        </select>
                    </div>
                </div>

                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="date">تاریخ قرار</label>
                        <input type="text" id="date" name="date" class="pzl-jalali-date-picker" value="<?php echo $item_to_edit && get_post_meta($consultation_id, '_consultation_datetime', true) ? esc_attr(puzzling_gregorian_to_jalali(date('Y-m-d', strtotime(get_post_meta($consultation_id, '_consultation_datetime', true))))) : ''; ?>">
                    </div>
                    <div class="form-group half-width">
                        <label for="time">ساعت قرار</label>
                        <input type="time" id="time" name="time" value="<?php echo $item_to_edit && get_post_meta($consultation_id, '_consultation_datetime', true) ? esc_attr(date('H:i', strtotime(get_post_meta($consultation_id, '_consultation_datetime', true)))) : ''; ?>">
                    </div>
                </div>

                 <div class="form-group full-width">
                    <label for="status">نتیجه مشاوره</label>
                     <select id="status" name="status" required>
                        <?php
                        $statuses = get_terms(['taxonomy' => 'consultation_status', 'hide_empty' => false]);
                        $current_status_terms = $item_to_edit ? wp_get_post_terms($consultation_id, 'consultation_status') : [];
                        $current_status_slug = !is_wp_error($current_status_terms) && !empty($current_status_terms) ? $current_status_terms[0]->slug : 'in-progress';
                        foreach ($statuses as $status) {
                            echo '<option value="' . esc_attr($status->slug) . '" ' . selected($current_status_slug, $status->slug, false) . '>' . esc_html($status->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                 <div class="form-group full-width">
                    <label for="notes">یادداشت‌ها و جزئیات جلسه</label>
                    <textarea id="notes" name="notes" rows="4"><?php echo $item_to_edit ? esc_textarea($item_to_edit->post_content) : ''; ?></textarea>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="pzl-button"><?php echo $item_to_edit ? 'ذخیره تغییرات' : 'ثبت مشاوره'; ?></button>
                </div>
            </form>
        </div>

    <?php else: // List view ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="ri-customer-service-line"></i> مدیریت مشاوره‌ها</h3>
                <a href="<?php echo add_query_arg('action', 'new'); ?>" class="pzl-button">افزودن مشاوره جدید</a>
            </div>

            <table class="pzl-table" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>نام درخواست کننده</th>
                        <th>شماره تماس</th>
                        <th>نوع مشاوره</th>
                        <th>تاریخ و ساعت</th>
                        <th>نتیجه</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $consultations = get_posts(['post_type' => 'pzl_consultation', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC']);
                    if (empty($consultations)) {
                        echo '<tr><td colspan="6">هیچ درخواست مشاوره‌ای ثبت نشده است.</td></tr>';
                    } else {
                        foreach ($consultations as $consultation):
                            $consultation_id = $consultation->ID;
                            $phone = get_post_meta($consultation_id, '_consultation_phone', true);
                            $type = get_post_meta($consultation_id, '_consultation_type', true);
                            $datetime = get_post_meta($consultation_id, '_consultation_datetime', true);
                            $status_terms = get_the_terms($consultation_id, 'consultation_status');
                            $status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : '---';
                            $status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';

                            $type_text = ($type === 'in-person') ? 'حضوری' : 'تلفنی';
                            $datetime_formatted = !empty($datetime) ? jdate('Y/m/d H:i', strtotime($datetime)) : '---';
                            $edit_url = add_query_arg(['action' => 'edit', 'consultation_id' => $consultation_id]);
                    ?>
                        <tr>
                            <td><?php echo esc_html($consultation->post_title); ?></td>
                            <td><?php echo esc_html($phone); ?></td>
                            <td><?php echo esc_html($type_text); ?></td>
                            <td><?php echo esc_html($datetime_formatted); ?></td>
                            <td><span class="pzl-status-badge status-<?php echo $status_slug; ?>"><?php echo $status_name; ?></span></td>
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-sm">ویرایش</a>
                                <?php if ($status_slug !== 'converted'): ?>
                                <button class="pzl-button pzl-button-sm convert-to-project-btn" data-id="<?php echo esc_attr($consultation_id); ?>">تبدیل به پروژه</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; 
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('.convert-to-project-btn').on('click', function() {
        var button = $(this);
        var consultationId = button.data('id');

        Swal.fire({
            title: 'تبدیل به پروژه',
            text: "آیا مطمئن هستید؟ یک کاربر، قرارداد و پروژه جدید ایجاد خواهد شد.",
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'بله، تبدیل کن',
            cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: puzzlingcrm_ajax_obj.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'puzzling_convert_consultation_to_project',
                        security: puzzlingcrm_ajax_obj.nonce,
                        consultation_id: consultationId
                    },
                    beforeSend: function() {
                        button.html('<i class="ri-loader-4-line ri-spin"></i>').prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'موفقیت‌آمیز',
                                text: response.data.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = response.data.redirect_url;
                            });
                        } else {
                            Swal.fire('خطا', response.data.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('خطا', 'خطای سرور.', 'error');
                    },
                    complete: function() {
                        button.html('تبدیل به پروژه').prop('disabled', false);
                    }
                });
            }
        });
    });
});
</script>

<style>
.pzl-status-badge.status-in-progress { background-color: #ffc107; color: #333; }
.pzl-status-badge.status-converted { background-color: #28a745; }
.pzl-status-badge.status-closed { background-color: #6c757d; }
</style>