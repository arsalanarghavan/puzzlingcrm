<?php
/**
 * Template for Client to view their Pro-forma Invoices.
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;

$invoices = get_posts([
    'post_type' => 'pzl_pro_invoice',
    'author' => get_current_user_id(),
    'posts_per_page' => -1,
]);
?>
<div class="pzl-dashboard-section">
    <h3><i class="ri-file-line-invoice"></i> پیش‌فاکتورهای شما</h3>

    <?php if (empty($invoices)): ?>
        <div class="pzl-empty-state">
            <i class="ri-error-warning-line"></i>
            <h4>موردی یافت نشد</h4>
            <p>در حال حاضر هیچ پیش‌فاکتوری برای شما ثبت نشده است.</p>
        </div>
    <?php else: ?>
        <table class="pzl-table">
            <thead>
                <tr>
                    <th>عنوان پیش‌فاکتور</th>
                    <th>تاریخ ایجاد</th>
                    <th>جزئیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($invoices as $invoice): ?>
                <tr>
                    <td><strong><?php echo esc_html($invoice->post_title); ?></strong></td>
                    <td><?php echo esc_html(get_the_date('Y/m/d', $invoice)); ?></td>
                    <td><div class="pzl-invoice-content"><?php echo wp_kses_post(apply_filters('the_content', $invoice->post_content)); ?></div></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>