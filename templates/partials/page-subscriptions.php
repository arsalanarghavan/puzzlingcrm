<?php
/**
 * Template to display WooCommerce Subscriptions for System Manager
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

// Check if the required function from WooCommerce Subscriptions exists
if (!function_exists('wcs_get_subscriptions')) {
    echo '<div class="pzl-alert pzl-alert-error">افزونه WooCommerce Subscriptions فعال نیست.</div>';
    return;
}

$subscriptions = wcs_get_subscriptions([
    'subscriptions_per_page' => -1,
]);
?>
<div class="pzl-dashboard-section">
    <div class="pzl-card">
        <h3><i class="ri-refresh-line"></i> اشتراک‌های مشتریان</h3>
        <p class="description">
            این صفحه تمام اشتراک‌های فعال از افزونه WooCommerce Subscriptions را نمایش می‌دهد. برای مدیریت یا ایجاد اشتراک جدید، لطفاً از منوی ووکامرس اقدام کنید.
        </p>

        <?php if (!empty($subscriptions)): ?>
            <table class="pzl-table">
                <thead>
                    <tr>
                        <th>اشتراک</th>
                        <th>مشتری</th>
                        <th>وضعیت</th>
                        <th>مبلغ کل</th>
                        <th>تاریخ شروع</th>
                        <th>تاریخ پرداخت بعدی</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($subscriptions as $subscription): 
                        $customer = $subscription->get_user();
                        $customer_profile_url = $customer ? add_query_arg(['view' => 'customers', 'action' => 'edit', 'user_id' => $customer->ID]) : '#';
                    ?>
                    <tr>
                        <td>#<?php echo esc_html($subscription->get_id()); ?></td>
                        <td><?php echo $customer ? esc_html($customer->display_name) : 'مهمان'; ?></td>
                        <td><span class="pzl-status status-<?php echo esc_attr($subscription->get_status()); ?>"><?php echo esc_html(wcs_get_subscription_status_name($subscription->get_status())); ?></span></td>
                        <td><?php echo wp_kses_post($subscription->get_formatted_order_total()); ?></td>
                        <td><?php echo esc_html($subscription->get_date_to_display('start_date')); ?></td>
                        <td><?php echo esc_html($subscription->get_date_to_display('next_payment_date')); ?></td>
                        <td>
                            <a href="<?php echo esc_url($customer_profile_url); ?>" class="pzl-button pzl-button-sm">مشاهده پروفایل مشتری</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>هیچ اشتراکی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<style>
/* Add specific styles for WC Subscription statuses if needed */
.pzl-status.status-active { background-color: #28a745; color: #fff; }
.pzl-status.status-on-hold { background-color: #ffc107; color: #333; }
.pzl-status.status-cancelled { background-color: #dc3545; color: #fff; }
.pzl-status.status-expired, .pzl-status.status-pending-cancel { background-color: #6c757d; color: #fff; }
</style>