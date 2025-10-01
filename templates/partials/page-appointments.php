<?php
/**
 * Template for System Manager to Manage Appointments - VISUALLY REVAMPED & AJAX-POWERED
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

// Ensure default appointment statuses exist.
if ( ! term_exists( 'pending', 'appointment_status' ) ) {
    PuzzlingCRM_CPT_Manager::create_default_terms();
}

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$appt_id = isset($_GET['appt_id']) ? intval($_GET['appt_id']) : 0;
$item_to_edit = ($appt_id > 0) ? get_post($appt_id) : null;
?>
<div class="pzl-dashboard-section">
    <?php if ($action === 'edit' || $action === 'new'): 
        $customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
    ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="fas fa-calendar-alt"></i> <?php echo $item_to_edit ? 'ویرایش قرار ملاقات' : 'ایجاد قرار ملاقات جدید'; ?></h3>
                <a href="<?php echo remove_query_arg(['action', 'appt_id']); ?>" class="pzl-button">&larr; بازگشت به لیست</a>
            </div>
            <form method="post" class="pzl-form pzl-ajax-form" data-action="puzzling_manage_appointment">
                <input type="hidden" name="appointment_id" value="<?php echo esc_attr($appt_id); ?>">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                
                <?php
                $selected_customer = $item_to_edit ? $item_to_edit->post_author : '';
                $datetime_str = $item_to_edit ? get_post_meta($item_to_edit->ID, '_appointment_datetime', true) : '';
                $date_val = $datetime_str ? puzzling_gregorian_to_jalali(date('Y-m-d', strtotime($datetime_str))) : '';
                $time_val = $datetime_str ? date('H:i', strtotime($datetime_str)) : '';
                ?>

                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="customer_id">مشتری</label>
                        <select id="customer_id" name="customer_id" required>
                            <option value="">-- انتخاب مشتری --</option>
                            <?php foreach($customers as $c){ echo "<option value='{$c->ID}'" . selected($selected_customer, $c->ID, false) . ">{$c->display_name}</option>"; } ?>
                        </select>
                    </div>
                    <div class="form-group half-width">
                        <label for="title">موضوع</label>
                        <input type="text" id="title" name="title" value="<?php echo $item_to_edit ? esc_attr($item_to_edit->post_title) : ''; ?>" required>
                    </div>
                </div>

                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="date">تاریخ</label>
                        <input type="text" id="date" name="date" value="<?php echo esc_attr($date_val); ?>" class="pzl-jalali-date-picker" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="time">ساعت</label>
                        <input type="time" id="time" name="time" value="<?php echo esc_attr($time_val); ?>" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="status">وضعیت قرار</label>
                     <select id="status" name="status" required>
                        <?php
                        $statuses = get_terms(['taxonomy' => 'appointment_status', 'hide_empty' => false]);
                        $current_status_terms = $item_to_edit ? wp_get_post_terms($appt_id, 'appointment_status') : [];
                        $current_status_slug = !is_wp_error($current_status_terms) && !empty($current_status_terms) ? $current_status_terms[0]->slug : 'pending';
                        foreach ($statuses as $status) {
                            echo '<option value="' . esc_attr($status->slug) . '" ' . selected($current_status_slug, $status->slug, false) . '>' . esc_html($status->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="notes">یادداشت‌ها</label>
                    <textarea id="notes" name="notes" rows="4"><?php echo $item_to_edit ? esc_textarea($item_to_edit->post_content) : ''; ?></textarea>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button"><?php echo $item_to_edit ? 'ذخیره تغییرات' : 'ایجاد قرار ملاقات'; ?></button>
                </div>
            </form>
        </div>
    <?php else: // List View ?>
        <div class="pzl-card-header">
            <h3><i class="fas fa-calendar-alt"></i> لیست قرار ملاقات‌ها</h3>
            <a href="<?php echo add_query_arg(['action' => 'new']); ?>" class="pzl-button">ایجاد قرار جدید</a>
        </div>
        <div class="pzl-card">
            <?php 
            $appointments = get_posts(['post_type' => 'pzl_appointment', 'posts_per_page' => -1, 'orderby' => 'meta_value', 'meta_key' => '_appointment_datetime', 'order' => 'DESC']);
            if (!empty($appointments)): ?>
            <table class="pzl-table">
                <thead><tr><th>موضوع</th><th>مشتری</th><th>تاریخ و ساعت</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                    <?php foreach($appointments as $appt):
                        $datetime_str = get_post_meta($appt->ID, '_appointment_datetime', true);
                        $status_terms = get_the_terms($appt->ID, 'appointment_status');
                        $status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : '---';
                        $status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';
                        $edit_url = add_query_arg(['action' => 'edit', 'appt_id' => $appt->ID]);
                    ?>
                    <tr>
                        <td><?php echo esc_html($appt->post_title); ?></td>
                        <td><?php echo get_the_author_meta('display_name', $appt->post_author); ?></td>
                        <td><?php echo jdate('Y/m/d H:i', strtotime($datetime_str)); ?></td>
                        <td><span class="pzl-status-badge status-<?php echo $status_slug; ?>"><?php echo $status_name; ?></span></td>
                        <td>
                            <a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-sm">ویرایش</a>
                            <button class="pzl-button pzl-button-sm delete-appointment-btn" data-id="<?php echo esc_attr($appt->ID); ?>" style="background-color: #dc3545 !important;">حذف</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="pzl-empty-state">
                    <h4>قراری یافت نشد</h4>
                    <p>در حال حاضر هیچ قرار ملاقاتی در سیستم ثبت نشده است.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('.delete-appointment-btn').on('click', function() {
        var button = $(this);
        var apptId = button.data('id');

        Swal.fire({
            title: 'آیا مطمئن هستید؟',
            text: "این عمل غیرقابل بازگشت است.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بله، حذف کن',
            cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: puzzlingcrm_ajax_obj.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'puzzling_delete_appointment',
                        security: puzzlingcrm_ajax_obj.nonce,
                        appointment_id: apptId
                    },
                    success: function(response) {
                        if (response.success) {
                            showPuzzlingAlert('موفق', response.data.message, 'success', true);
                        } else {
                            showPuzzlingAlert('خطا', response.data.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>

<style>
.pzl-status-badge.status-pending { background-color: #ffc107; color: #333; }
.pzl-status-badge.status-confirmed { background-color: #28a745; }
.pzl-status-badge.status-cancelled { background-color: #6c757d; }
</style>