<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// Simple user creation/listing for now. A more robust solution would use WP_List_Table.
?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-admin-users"></span> مدیریت مشتریان</h3>

    <div class="pzl-form-container">
        <h4>افزودن مشتری جدید</h4>
        <form method="post">
            <?php // Nonce, action fields, etc. for user creation go here ?>
            <p>فرم افزودن مشتری (ایجاد کاربر وردپرس با نقش Customer) در این بخش قرار می‌گیرد.</p>
        </form>
    </div>

    <hr>
    <h4>لیست مشتریان</h4>
    <table class="pzl-table">
        <thead>
            <tr><th>نام</th><th>ایمیل</th><th>تاریخ ثبت نام</th></tr>
        </thead>
        <tbody>
            <?php foreach(get_users(['role' => 'customer']) as $customer): ?>
                <tr>
                    <td><?php echo esc_html($customer->display_name); ?></td>
                    <td><?php echo esc_html($customer->user_email); ?></td>
                    <td><?php echo esc_html(date_i18n('Y/m/d', strtotime($customer->user_registered))); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>