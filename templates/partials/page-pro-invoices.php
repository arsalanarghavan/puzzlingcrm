<?php
/**
 * Template for System Manager to Manage Pro-forma Invoices.
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;
$invoice_to_edit = ($invoice_id > 0) ? get_post($invoice_id) : null;
?>
<div class="pzl-dashboard-section">
    <?php if ($action === 'edit' || $action === 'new'): ?>
        <h3><?php echo $invoice_id > 0 ? __('Edit Pro-forma Invoice', 'puzzlingcrm') : __('Create New Pro-forma Invoice', 'puzzlingcrm'); ?></h3>
        <a href="<?php echo remove_query_arg(['action', 'invoice_id']); ?>">&larr; <?php _e('Back to List', 'puzzlingcrm'); ?></a>

        <form method="post" class="pzl-form-container" style="margin-top: 20px;">
            <?php wp_nonce_field('puzzling_manage_pro_invoice'); ?>
            <input type="hidden" name="puzzling_action" value="manage_pro_invoice">
            <input type="hidden" name="item_id" value="<?php echo esc_attr($invoice_id); ?>">

            <div class="form-group">
                <label for="invoice_title"><?php _e('Title:', 'puzzlingcrm'); ?></label>
                <input type="text" id="invoice_title" name="invoice_title" value="<?php echo $invoice_to_edit ? esc_attr($invoice_to_edit->post_title) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="invoice_content"><?php _e('Details / Content:', 'puzzlingcrm'); ?></label>
                <?php wp_editor($invoice_to_edit ? $invoice_to_edit->post_content : '', 'invoice_content', ['textarea_rows' => 10]); ?>
            </div>
            <div class="form-group">
                <label for="customer_id"><?php _e('Assign to Customer:', 'puzzlingcrm'); ?></label>
                <select name="customer_id" id="customer_id" required>
                    <option value="">-- <?php _e('Select Customer', 'puzzlingcrm'); ?> --</option>
                    <?php
                    $customers = get_users(['role' => 'customer', 'orderby' => 'display_name']);
                    foreach ($customers as $customer) {
                        $selected = $invoice_to_edit && $invoice_to_edit->post_author == $customer->ID ? 'selected' : '';
                        echo '<option value="' . esc_attr($customer->ID) . '" ' . $selected . '>' . esc_html($customer->display_name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="pzl-button pzl-button-primary"><?php echo $invoice_id > 0 ? __('Save Changes', 'puzzlingcrm') : __('Create Invoice', 'puzzlingcrm'); ?></button>
        </form>

    <?php else: // 'list' view ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3><span class="dashicons dashicons-text-page"></span> <?php _e('Manage Pro-forma Invoices', 'puzzlingcrm'); ?></h3>
            <a href="<?php echo add_query_arg(['action' => 'new']); ?>" class="pzl-button pzl-button-primary"><?php _e('Create New', 'puzzlingcrm'); ?></a>
        </div>
        
        <?php
        $invoices = get_posts(['post_type' => 'pzl_pro_invoice', 'posts_per_page' => -1]);
        if ($invoices): ?>
            <table class="pzl-table">
                <thead><tr><th><?php _e('Title', 'puzzlingcrm'); ?></th><th><?php _e('Customer', 'puzzlingcrm'); ?></th><th><?php _e('Date', 'puzzlingcrm'); ?></th><th><?php _e('Actions', 'puzzlingcrm'); ?></th></tr></thead>
                <tbody>
                    <?php foreach($invoices as $invoice): ?>
                    <tr>
                        <td><strong><?php echo esc_html($invoice->post_title); ?></strong></td>
                        <td><?php echo esc_html(get_the_author_meta('display_name', $invoice->post_author)); ?></td>
                        <td><?php echo get_the_date('Y/m/d', $invoice); ?></td>
                        <td>
                            <a href="<?php echo add_query_arg(['action' => 'edit', 'invoice_id' => $invoice->ID]); ?>" class="pzl-button pzl-button-secondary"><?php _e('Edit', 'puzzlingcrm'); ?></a>
                            <form method="post" onsubmit="return confirm('<?php esc_attr_e('Are you sure?', 'puzzlingcrm'); ?>');" style="display: inline;">
                                 <input type="hidden" name="puzzling_action" value="delete_pro_invoice">
                                 <input type="hidden" name="item_id" value="<?php echo esc_attr($invoice->ID); ?>">
                                 <?php wp_nonce_field('puzzling_delete_pro_invoice_' . $invoice->ID, '_wpnonce'); ?>
                                 <button type="submit" class="pzl-button" style="background-color: #dc3545; color: white;"><?php _e('Delete', 'puzzlingcrm'); ?></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No pro-forma invoices found.', 'puzzlingcrm'); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>