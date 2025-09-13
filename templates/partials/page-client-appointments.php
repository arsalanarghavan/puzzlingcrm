<?php
/**
 * Template for Client to request an appointment.
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('customer')) return;
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-calendar-plus"></i> <?php esc_html_e('Schedule a New Appointment', 'puzzlingcrm'); ?></h3>
    <p><?php esc_html_e('Please fill out the form below to request a new appointment. We will review your request and confirm the schedule with you.', 'puzzlingcrm'); ?></p>

    <div class="pzl-form-container">
        <form method="post">
            <input type="hidden" name="puzzling_action" value="request_appointment">
            <?php wp_nonce_field('puzzling_request_appointment', '_wpnonce'); ?>

            <div class="form-group">
                <label for="appointment_title"><?php esc_html_e('Subject:', 'puzzlingcrm'); ?></label>
                <input type="text" id="appointment_title" name="title" required placeholder="<?php esc_attr_e('e.g., Project Kick-off Meeting', 'puzzlingcrm'); ?>">
            </div>
            <div class="form-group">
                <label for="appointment_date"><?php esc_html_e('Preferred Date:', 'puzzlingcrm'); ?></label>
                <input type="date" id="appointment_date" name="date" required>
            </div>
            <div class="form-group">
                <label for="appointment_time"><?php esc_html_e('Preferred Time:', 'puzzlingcrm'); ?></label>
                <input type="time" id="appointment_time" name="time" required>
            </div>
            <div class="form-group">
                <label for="appointment_notes"><?php esc_html_e('Notes (optional):', 'puzzlingcrm'); ?></label>
                <textarea id="appointment_notes" name="notes" rows="4" placeholder="<?php esc_attr_e('Any additional details you would like to provide.', 'puzzlingcrm'); ?>"></textarea>
            </div>
            <button type="submit" class="pzl-button"><?php esc_html_e('Submit Request', 'puzzlingcrm'); ?></button>
        </form>
    </div>
</div>