<?php
/**
 * Template to display WooCommerce Subscriptions for System Manager
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

// Check if the required function from WooCommerce Subscriptions exists
if (!function_exists('wcs_get_subscriptions')) {
    echo '<div class="pzl-alert pzl-alert-error">' . esc_html__('WooCommerce Subscriptions plugin is not active.', 'puzzlingcrm') . '</div>';
    return;
}

$subscriptions = wcs_get_subscriptions([
    'subscriptions_per_page' => -1,
]);
?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-update-alt"></span> <?php esc_html_e('Customer Subscriptions', 'puzzlingcrm'); ?></h3>
    <p class="description">
        <?php esc_html_e('This page displays all active subscriptions from the WooCommerce Subscriptions plugin. To manage or create new subscriptions, please use the WooCommerce menu.', 'puzzlingcrm'); ?>
    </p>

    <?php if (!empty($subscriptions)): ?>
        <table class="pzl-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Subscription', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Customer', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Status', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Total', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Start Date', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Next Payment Date', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Actions', 'puzzlingcrm'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($subscriptions as $subscription): 
                    $customer = $subscription->get_user();
                ?>
                <tr>
                    <td>#<?php echo esc_html($subscription->get_id()); ?></td>
                    <td><?php echo $customer ? esc_html($customer->display_name) : esc_html__('Guest', 'puzzlingcrm'); ?></td>
                    <td><span class="pzl-status status-<?php echo esc_attr($subscription->get_status()); ?>"><?php echo esc_html(wcs_get_subscription_status_name($subscription->get_status())); ?></span></td>
                    <td><?php echo wp_kses_post($subscription->get_formatted_order_total()); ?></td>
                    <td><?php echo esc_html($subscription->get_date_to_display('start_date')); ?></td>
                    <td><?php echo esc_html($subscription->get_date_to_display('next_payment_date')); ?></td>
                    <td>
                        <a href="<?php echo esc_url(get_edit_post_link($subscription->get_id())); ?>" class="pzl-button pzl-button-secondary" target="_blank"><?php esc_html_e('Manage', 'puzzlingcrm'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php esc_html_e('No subscriptions found.', 'puzzlingcrm'); ?></p>
    <?php endif; ?>
</div>

<style>
/* Add specific styles for WC Subscription statuses if needed */
.pzl-status.status-active { background-color: #28a745; }
.pzl-status.status-on-hold { background-color: #ffc107; color: #333; }
.pzl-status.status-cancelled { background-color: #dc3545; }
.pzl-status.status-expired, .pzl-status.status-pending-cancel { background-color: #6c757d; }
</style>