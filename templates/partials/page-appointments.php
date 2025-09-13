<?php
if (!defined('ABSPATH')) exit;
$customers = get_users(['role' => 'customer']);
$appointments = get_posts(['post_type' => 'pzl_appointment', 'posts_per_page' => -1]);
?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-calendar-alt"></span> مدیریت قرار ملاقات‌ها</h3>

    <div class="pzl-form-container">
        <h4>ثبت قرار ملاقات جدید</h4>
        <form method="post">
            <input type="hidden" name="puzzling_action" value="manage_appointment">
            <?php wp_nonce_field('puzzling_manage_appointment'); ?>
            <div class="form-group"><label>مشتری:</label><select name="customer_id" required><option value="">-- انتخاب --</option><?php foreach($customers as $c){ echo "<option value='{$c->ID}'>{$c->display_name}</option>"; } ?></select></div>
            <div class="form-group"><label>موضوع قرار:</label><input type="text" name="title" required></div>
            <div class="form-group"><label>تاریخ:</label><input type="date" name="date" required></div>
            <div class="form-group"><label>ساعت:</label><input type="time" name="time" required></div>
            <div class="form-group"><label>یادداشت:</label><textarea name="notes" rows="4"></textarea></div>
            <button type="submit" class="pzl-button pzl-button-primary">ثبت قرار</button>
        </form>
    </div>
    <hr>
    <h4>لیست قرارها</h4>
    <table class="pzl-table">
        <thead><tr><th>موضوع</th><th>مشتری</th><th>تاریخ و ساعت</th><th>وضعیت</th></tr></thead>
        <tbody>
            <?php if (!empty($appointments)): foreach($appointments as $appt):
                $datetime_str = get_post_meta($appt->ID, '_appointment_datetime', true);
                $is_past = strtotime($datetime_str) < time();
            ?>
            <tr>
                <td><?php echo esc_html($appt->post_title); ?></td>
                <td><?php echo get_the_author_meta('display_name', $appt->post_author); ?></td>
                <td><?php echo date_i18n('Y/m/d H:i', strtotime($datetime_str)); ?></td>
                <td><?php echo $is_past ? 'انجام شده' : 'در پیش رو'; ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="4">هیچ قراری ثبت نشده است.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>