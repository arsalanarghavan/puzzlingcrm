<?php
if (!defined('ABSPATH')) exit;
$customers = get_users(['role' => 'customer']);
$appointments = get_posts(['post_type' => 'pzl_appointment', 'posts_per_page' => -1]);
?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Manage Appointments', 'puzzlingcrm'); ?></h3>

    <div class="pzl-form-container">
        <h4><?php esc_html_e('Create New Appointment', 'puzzlingcrm'); ?></h4>
        <form method="post">
            <input type="hidden" name="puzzling_action" value="manage_appointment">
            <?php wp_nonce_field('puzzling_manage_appointment'); ?>
            <div class="form-group"><label><?php esc_html_e('Customer:', 'puzzlingcrm'); ?></label><select name="customer_id" required><option value="">-- <?php esc_html_e('Select', 'puzzlingcrm'); ?> --</option><?php foreach($customers as $c){ echo "<option value='{$c->ID}'>{$c->display_name}</option>"; } ?></select></div>
            <div class="form-group"><label><?php esc_html_e('Subject:', 'puzzlingcrm'); ?></label><input type="text" name="title" required></div>
            <div class="form-group"><label><?php esc_html_e('Date:', 'puzzlingcrm'); ?></label><input type="date" name="date" required></div>
            <div class="form-group"><label><?php esc_html_e('Time:', 'puzzlingcrm'); ?></label><input type="time" name="time" required></div>
            <div class="form-group"><label><?php esc_html_e('Notes:', 'puzzlingcrm'); ?></label><textarea name="notes" rows="4"></textarea></div>
            <button type="submit" class="pzl-button pzl-button-primary"><?php esc_html_e('Create Appointment', 'puzzlingcrm'); ?></button>
        </form>
    </div>
    <hr>
    <h4><?php esc_html_e('Appointments List', 'puzzlingcrm'); ?></h4>
    <table class="pzl-table">
        <thead><tr><th><?php esc_html_e('Subject', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Customer', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Date & Time', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Status', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Actions', 'puzzlingcrm'); ?></th></tr></thead>
        <tbody>
            <?php if (!empty($appointments)): foreach($appointments as $appt):
                $datetime_str = get_post_meta($appt->ID, '_appointment_datetime', true);
                $is_past = strtotime($datetime_str) < time();
            ?>
            <tr>
                <td><?php echo esc_html($appt->post_title); ?></td>
                <td><?php echo get_the_author_meta('display_name', $appt->post_author); ?></td>
                <td><?php echo date_i18n('Y/m/d H:i', strtotime($datetime_str)); ?></td>
                <td><?php echo $is_past ? esc_html__('Completed', 'puzzlingcrm') : esc_html__('Upcoming', 'puzzlingcrm'); ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete this appointment?', 'puzzlingcrm'); ?>');" style="display: inline;">
                         <input type="hidden" name="puzzling_action" value="delete_appointment">
                         <input type="hidden" name="item_id" value="<?php echo esc_attr($appt->ID); ?>">
                         <?php wp_nonce_field('puzzling_delete_appointment_' . $appt->ID, '_wpnonce'); ?>
                         <button type="submit" class="pzl-button pzl-button-primary" style="background-color: #dc3545;"><?php esc_html_e('Delete', 'puzzlingcrm'); ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5"><?php esc_html_e('No appointments found.', 'puzzlingcrm'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>