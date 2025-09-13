<?php
if (!defined('ABSPATH')) exit;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$appt_id = isset($_GET['appt_id']) ? intval($_GET['appt_id']) : 0;
$appt_to_edit = null;

if ($appt_id > 0) {
    $appt_to_edit = get_post($appt_id);
}

$customers = get_users(['role' => 'customer']);
$appointments = get_posts(['post_type' => 'pzl_appointment', 'posts_per_page' => -1]);
?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Manage Appointments', 'puzzlingcrm'); ?></h3>

    <?php if ($action === 'add' || ($action === 'edit' && $appt_to_edit)): ?>
        <h4><?php echo $appt_to_edit ? esc_html__('Edit Appointment', 'puzzlingcrm') : esc_html__('Create New Appointment', 'puzzlingcrm'); ?></h4>
        <a href="<?php echo remove_query_arg(['action', 'appt_id']); ?>">&larr; <?php esc_html_e('Back to Appointments List', 'puzzlingcrm'); ?></a>
        
        <form method="post" class="pzl-form-container" style="margin-top: 20px;">
            <input type="hidden" name="puzzling_action" value="manage_appointment">
            <input type="hidden" name="item_id" value="<?php echo esc_attr($appt_id); ?>">
            <?php wp_nonce_field('puzzling_manage_appointment_' . $appt_id, '_wpnonce'); ?>
            
            <?php
            $selected_customer = $appt_to_edit ? $appt_to_edit->post_author : '';
            $datetime_str = $appt_to_edit ? get_post_meta($appt_to_edit->ID, '_appointment_datetime', true) : '';
            $date_val = $datetime_str ? date('Y-m-d', strtotime($datetime_str)) : '';
            $time_val = $datetime_str ? date('H:i', strtotime($datetime_str)) : '';
            ?>

            <div class="form-group">
                <label><?php esc_html_e('Customer:', 'puzzlingcrm'); ?></label>
                <select name="customer_id" required>
                    <option value="">-- <?php esc_html_e('Select', 'puzzlingcrm'); ?> --</option>
                    <?php foreach($customers as $c){ echo "<option value='{$c->ID}'" . selected($selected_customer, $c->ID, false) . ">{$c->display_name}</option>"; } ?>
                </select>
            </div>
            <div class="form-group">
                <label><?php esc_html_e('Subject:', 'puzzlingcrm'); ?></label>
                <input type="text" name="title" value="<?php echo $appt_to_edit ? esc_attr($appt_to_edit->post_title) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label><?php esc_html_e('Date:', 'puzzlingcrm'); ?></label>
                <input type="date" name="date" value="<?php echo esc_attr($date_val); ?>" required>
            </div>
            <div class="form-group">
                <label><?php esc_html_e('Time:', 'puzzlingcrm'); ?></label>
                <input type="time" name="time" value="<?php echo esc_attr($time_val); ?>" required>
            </div>
            <div class="form-group">
                <label><?php esc_html_e('Notes:', 'puzzlingcrm'); ?></label>
                <textarea name="notes" rows="4"><?php echo $appt_to_edit ? esc_textarea($appt_to_edit->post_content) : ''; ?></textarea>
            </div>
            <button type="submit" class="pzl-button pzl-button-primary"><?php echo $appt_to_edit ? esc_html__('Save Changes', 'puzzlingcrm') : esc_html__('Create Appointment', 'puzzlingcrm'); ?></button>
        </form>
    <?php else: ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4><?php esc_html_e('Appointments List', 'puzzlingcrm'); ?></h4>
            <a href="<?php echo add_query_arg(['action' => 'add']); ?>" class="pzl-button pzl-button-primary"><?php esc_html_e('Create New Appointment', 'puzzlingcrm'); ?></a>
        </div>
        
        <table class="pzl-table">
            <thead><tr><th><?php esc_html_e('Subject', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Customer', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Date & Time', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Status', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Actions', 'puzzlingcrm'); ?></th></tr></thead>
            <tbody>
                <?php if (!empty($appointments)): foreach($appointments as $appt):
                    $datetime_str = get_post_meta($appt->ID, '_appointment_datetime', true);
                    $is_past = strtotime($datetime_str) < time();
                    $edit_url = add_query_arg(['action' => 'edit', 'appt_id' => $appt->ID]);
                ?>
                <tr>
                    <td><?php echo esc_html($appt->post_title); ?></td>
                    <td><?php echo get_the_author_meta('display_name', $appt->post_author); ?></td>
                    <td><?php echo date_i18n('Y/m/d H:i', strtotime($datetime_str)); ?></td>
                    <td><?php echo $is_past ? esc_html__('Completed', 'puzzlingcrm') : esc_html__('Upcoming', 'puzzlingcrm'); ?></td>
                    <td>
                        <a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-secondary"><?php esc_html_e('Edit', 'puzzlingcrm'); ?></a>
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
    <?php endif; ?>
</div>