<?php
/**
 * Template for System Manager to Manage Appointments - VISUALLY REVAMPED & TRANSLATED
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$appt_id = isset($_GET['appt_id']) ? intval($_GET['appt_id']) : 0;
$appt_to_edit = ($appt_id > 0) ? get_post($appt_id) : null;
?>
<div class="puzzling-dashboard-wrapper">
    <?php if ($action === 'edit' || $action === 'add'): 
        $customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
    ?>
        <div class="pzl-card-header">
            <h3><i class="fas fa-calendar-alt"></i> <?php echo $appt_to_edit ? 'ویرایش قرار ملاقات' : 'ایجاد قرار ملاقات جدید'; ?></h3>
            <a href="<?php echo remove_query_arg(['action', 'appt_id']); ?>" class="pzl-button">&larr; بازگشت به لیست</a>
        </div>
        <div class="pzl-card">
            <form method="post" class="pzl-form" id="pzl-appointment-form">
                <input type="hidden" name="item_id" value="<?php echo esc_attr($appt_id); ?>">
                <?php wp_nonce_field('puzzling_manage_appointment_nonce', 'security'); ?>
                
                <?php
                $selected_customer = $appt_to_edit ? $appt_to_edit->post_author : '';
                $datetime_str = $appt_to_edit ? get_post_meta($appt_to_edit->ID, '_appointment_datetime', true) : '';
                $date_val = $datetime_str ? date('Y-m-d', strtotime($datetime_str)) : '';
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
                        <input type="text" id="title" name="title" value="<?php echo $appt_to_edit ? esc_attr($appt_to_edit->post_title) : ''; ?>" required>
                    </div>
                </div>

                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="date">تاریخ</label>
                        <input type="date" id="date" name="date" value="<?php echo esc_attr($date_val); ?>" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="time">ساعت</label>
                        <input type="time" id="time" name="time" value="<?php echo esc_attr($time_val); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">یادداشت‌ها</label>
                    <textarea id="notes" name="notes" rows="4"><?php echo $appt_to_edit ? esc_textarea($appt_to_edit->post_content) : ''; ?></textarea>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button"><?php echo $appt_to_edit ? 'ذخیره تغییرات' : 'ایجاد قرار ملاقات'; ?></button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="pzl-card-header">
            <h3><i class="fas fa-calendar-alt"></i> لیست قرار ملاقات‌ها</h3>
            <a href="<?php echo add_query_arg(['action' => 'add']); ?>" class="pzl-button">ایجاد قرار جدید</a>
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
                        $is_past = strtotime($datetime_str) < time();
                        $edit_url = add_query_arg(['action' => 'edit', 'appt_id' => $appt->ID]);
                    ?>
                    <tr>
                        <td><?php echo esc_html($appt->post_title); ?></td>
                        <td><?php echo get_the_author_meta('display_name', $appt->post_author); ?></td>
                        <td><?php echo date_i18n('Y/m/d H:i', strtotime($datetime_str)); ?></td>
                        <td><?php echo $is_past ? 'انجام شده' : 'در پیش رو'; ?></td>
                        <td>
                            <a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-sm">ویرایش</a>
                            <form method="post" onsubmit="return confirm('آیا از حذف این قرار مطمئن هستید؟');" style="display: inline;">
                                <input type="hidden" name="puzzling_action" value="delete_appointment">
                                <input type="hidden" name="item_id" value="<?php echo esc_attr($appt->ID); ?>">
                                <?php wp_nonce_field('puzzling_delete_appointment_' . $appt->ID, '_wpnonce'); ?>
                                <button type="submit" class="pzl-button pzl-button-sm">حذف</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>هیچ قرار ملاقاتی یافت نشد.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>