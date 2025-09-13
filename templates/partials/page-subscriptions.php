<?php
if (!defined('ABSPATH')) exit;
$plans = get_terms(['taxonomy' => 'subscription_plan', 'hide_empty' => false]);
$customers = get_users(['role' => 'customer']);
$subscriptions = get_posts(['post_type' => 'pzl_subscription', 'posts_per_page' => -1]);
?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-update-alt"></span> <?php esc_html_e('Manage Subscriptions', 'puzzlingcrm'); ?></h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="pzl-form-container">
            <h4><?php esc_html_e('Manage Subscription Plans', 'puzzlingcrm'); ?></h4>
            <form method="post">
                <input type="hidden" name="puzzling_action" value="manage_subscription_plan">
                <?php wp_nonce_field('puzzling_manage_subscription_plan'); ?>
                <div class="form-group"><label><?php esc_html_e('Plan Name:', 'puzzlingcrm'); ?></label><input type="text" name="plan_name" required></div>
                <div class="form-group"><label><?php esc_html_e('Price (Toman):', 'puzzlingcrm'); ?></label><input type="number" name="price" required></div>
                <div class="form-group"><label><?php esc_html_e('Interval:', 'puzzlingcrm'); ?></label><select name="interval" required><option value="month"><?php esc_html_e('Monthly', 'puzzlingcrm'); ?></option><option value="year"><?php esc_html_e('Yearly', 'puzzlingcrm'); ?></option></select></div>
                <button type="submit" class="pzl-button pzl-button-secondary"><?php esc_html_e('Save Plan', 'puzzlingcrm'); ?></button>
            </form>
            <hr>
            <ul><?php foreach($plans as $plan) { echo '<li>' . esc_html($plan->name) . '</li>'; } ?></ul>
        </div>
        <div class="pzl-form-container">
            <h4><?php esc_html_e('Assign Subscription to Customer', 'puzzlingcrm'); ?></h4>
            <form method="post">
                <input type="hidden" name="puzzling_action" value="assign_subscription">
                <?php wp_nonce_field('puzzling_assign_subscription'); ?>
                <div class="form-group"><label><?php esc_html_e('Customer:', 'puzzlingcrm'); ?></label><select name="customer_id" required><option value="">-- <?php esc_html_e('Select', 'puzzlingcrm'); ?> --</option><?php foreach($customers as $c){ echo "<option value='{$c->ID}'>{$c->display_name}</option>"; } ?></select></div>
                <div class="form-group"><label><?php esc_html_e('Plan:', 'puzzlingcrm'); ?></label><select name="plan_id" required><option value="">-- <?php esc_html_e('Select', 'puzzlingcrm'); ?> --</option><?php foreach($plans as $p){ echo "<option value='{$p->term_id}'>{$p->name}</option>"; } ?></select></div>
                <div class="form-group"><label><?php esc_html_e('Start Date:', 'puzzlingcrm'); ?></label><input type="date" name="start_date" required></div>
                <button type="submit" class="pzl-button pzl-button-primary"><?php esc_html_e('Create Subscription', 'puzzlingcrm'); ?></button>
            </form>
        </div>
    </div>
    <hr>
    <h4><?php esc_html_e('Active Subscriptions List', 'puzzlingcrm'); ?></h4>
    <table class="pzl-table">
        <thead><tr><th><?php esc_html_e('Customer', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Plan', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Status', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Start Date', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Next Payment Date', 'puzzlingcrm'); ?></th><th><?php esc_html_e('Actions', 'puzzlingcrm'); ?></th></tr></thead>
        <tbody>
            <?php if (!empty($subscriptions)): foreach($subscriptions as $sub):
                $plan_id = get_post_meta($sub->ID, '_plan_id', true);
                $plan = $plan_id ? get_term($plan_id, 'subscription_plan') : null;
                $status_terms = get_the_terms($sub->ID, 'subscription_status');
            ?>
            <tr>
                <td><?php echo get_the_author_meta('display_name', $sub->post_author); ?></td>
                <td><?php echo $plan ? $plan->name : '---'; ?></td>
                <td><?php echo !empty($status_terms) ? $status_terms[0]->name : '---'; ?></td>
                <td><?php echo get_post_meta($sub->ID, '_start_date', true); ?></td>
                <td><?php echo get_post_meta($sub->ID, '_next_payment_date', true); ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete this subscription?', 'puzzlingcrm'); ?>');" style="display: inline;">
                         <input type="hidden" name="puzzling_action" value="delete_subscription">
                         <input type="hidden" name="item_id" value="<?php echo esc_attr($sub->ID); ?>">
                         <?php wp_nonce_field('puzzling_delete_subscription_' . $sub->ID, '_wpnonce'); ?>
                         <button type="submit" class="pzl-button pzl-button-primary" style="background-color: #dc3545;"><?php esc_html_e('Delete', 'puzzlingcrm'); ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6"><?php esc_html_e('No subscriptions found.', 'puzzlingcrm'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>