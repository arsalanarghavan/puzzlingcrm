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
    <h3><span class="dashicons dashicons-text-page"></span> <?php esc_html_e('Your Pro-forma Invoices', 'puzzlingcrm'); ?></h3>

    <?php if (empty($invoices)): ?>
        <p><?php esc_html_e('No pro-forma invoices found.', 'puzzlingcrm'); ?></p>
    <?php else: ?>
        <table class="pzl-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Invoice Title', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Date Created', 'puzzlingcrm'); ?></th>
                    <th><?php esc_html_e('Details', 'puzzlingcrm'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($invoices as $invoice): ?>
                <tr>
                    <td><strong><?php echo esc_html($invoice->post_title); ?></strong></td>
                    <td><?php echo esc_html(get_the_date('Y/m/d', $invoice)); ?></td>
                    <td><?php echo wp_kses_post(apply_filters('the_content', $invoice->post_content)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>