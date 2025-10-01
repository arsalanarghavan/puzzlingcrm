<?php
/**
 * Template for Client to request an appointment - AJAX Powered.
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-calendar-plus"></i> ثبت قرار ملاقات جدید</h3>
    <p>لطفاً برای درخواست قرار ملاقات جدید، فرم زیر را تکمیل کنید. ما درخواست شما را بررسی کرده و زمان‌بندی را با شما هماهنگ خواهیم کرد.</p>

    <div class="pzl-form-container pzl-card">
        <form method="post" id="pzl-client-appointment-form" class="pzl-form pzl-ajax-form" data-action="puzzling_client_request_appointment">
            <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>

            <div class="form-group">
                <label for="appointment_title">موضوع:</label>
                <input type="text" id="appointment_title" name="title" required placeholder="مثال: جلسه شروع پروژه">
            </div>
            <div class="pzl-form-row">
                <div class="form-group half-width">
                    <label for="appointment_date">تاریخ مورد نظر:</label>
                    <input type="text" id="appointment_date" name="date" class="pzl-jalali-date-picker" required>
                </div>
                <div class="form-group half-width">
                    <label for="appointment_time">ساعت مورد نظر:</label>
                    <input type="time" id="appointment_time" name="time" required>
                </div>
            </div>
            <div class="form-group">
                <label for="appointment_notes">یادداشت (اختیاری):</label>
                <textarea id="appointment_notes" name="notes" rows="4" placeholder="جزئیات بیشتری که می‌خواهید ارائه دهید."></textarea>
            </div>
            <div class="form-submit">
                <button type="submit" class="pzl-button">ارسال درخواست</button>
            </div>
        </form>
    </div>
</div>