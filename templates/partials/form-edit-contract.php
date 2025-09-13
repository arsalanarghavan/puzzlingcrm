<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

global $puzzling_contract_id; // This global should be set by the template that includes this partial
$contract_id = $puzzling_contract_id;

$contract = get_post($contract_id);
if (!$contract || $contract->post_type !== 'contract') {
    echo '<p>' . esc_html__('Contract not found.', 'puzzlingcrm') . '</p>';
    return;
}

$project_id = get_post_meta($contract_id, '_project_id', true);
$installments = get_post_meta($contract_id, '_installments', true);
$back_url = add_query_arg('view', 'contracts', puzzling_get_dashboard_url());

?>
<div class="pzl-form-container">
    <a href="<?php echo esc_url($back_url); ?>" class="back-to-list-link">&larr; <?php esc_html_e('Back to Contracts List', 'puzzlingcrm'); ?></a>
    <h3><?php printf(esc_html__('Editing Contract for Project: %s', 'puzzlingcrm'), get_the_title($project_id)); ?></h3>
    
    <form id="edit-contract-form" method="post">
        <?php wp_nonce_field('puzzling_edit_contract_' . $contract_id, '_wpnonce'); ?>
        <input type="hidden" name="puzzling_action" value="edit_contract">
        <input type="hidden" name="item_id" value="<?php echo esc_attr($contract_id); ?>">

        <h4><?php esc_html_e('Installment Plan', 'puzzlingcrm'); ?></h4>
        <div id="payment-rows-container">
            <?php if (is_array($installments)): foreach($installments as $index => $inst): ?>
            <div class="payment-row form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <input type="number" name="payment_amount[]" placeholder="<?php esc_attr_e('Amount (Toman)', 'puzzlingcrm'); ?>" style="flex-grow: 1; padding: 8px;" value="<?php echo esc_attr($inst['amount']); ?>" required>
                <input type="date" name="payment_due_date[]" style="padding: 8px;" value="<?php echo esc_attr($inst['due_date']); ?>" required>
                <select name="payment_status[]" style="padding: 8px;">
                    <option value="pending" <?php selected($inst['status'], 'pending'); ?>><?php esc_html_e('Pending', 'puzzlingcrm'); ?></option>
                    <option value="paid" <?php selected($inst['status'], 'paid'); ?>><?php esc_html_e('Paid', 'puzzlingcrm'); ?></option>
                </select>
            </div>
            <?php endforeach; endif; ?>
        </div>
        
        <hr style="margin: 20px 0;">
        <button type="submit" name="submit_edit_contract" class="pzl-button pzl-button-primary" style="font-size: 16px;"><?php esc_html_e('Save Changes', 'puzzlingcrm'); ?></button>
    </form>
</div>