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
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><?php echo $invoice_id > 0 ? 'ویرایش پیش‌فاکتور' : 'ایجاد پیش‌فاکتور جدید'; ?></h3>
                <a href="<?php echo remove_query_arg(['action', 'invoice_id']); ?>" class="pzl-button pzl-button-secondary">&larr; بازگشت به لیست</a>
            </div>
            <form method="post" class="pzl-form">
                <?php wp_nonce_field('puzzling_manage_pro_invoice'); ?>
                <input type="hidden" name="puzzling_action" value="manage_pro_invoice">
                <input type="hidden" name="item_id" value="<?php echo esc_attr($invoice_id); ?>">

                <div class="form-group">
                    <label for="invoice_title">عنوان:</label>
                    <input type="text" id="invoice_title" name="invoice_title" value="<?php echo $invoice_to_edit ? esc_attr($invoice_to_edit->post_title) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="customer_id">تخصیص به مشتری:</label>
                    <select name="customer_id" id="customer_id" required>
                        <option value="">-- انتخاب مشتری --</option>
                        <?php
                        $customers = get_users(['role' => 'customer', 'orderby' => 'display_name']);
                        foreach ($customers as $customer) {
                            $selected = $invoice_to_edit && $invoice_to_edit->post_author == $customer->ID ? 'selected' : '';
                            echo '<option value="' . esc_attr($customer->ID) . '" ' . $selected . '>' . esc_html($customer->display_name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="invoice_content">جزئیات / محتوا:</label>
                    <?php wp_editor($invoice_to_edit ? $invoice_to_edit->post_content : '', 'invoice_content', ['textarea_rows' => 10]); ?>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button pzl-button-primary"><?php echo $invoice_id > 0 ? 'ذخیره تغییرات' : 'ایجاد پیش‌فاکتور'; ?></button>
                </div>
            </form>
        </div>
    <?php else: // 'list' view ?>
        <div class="pzl-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3><span class="dashicons dashicons-text-page"></span> مدیریت پیش‌فاکتورها</h3>
                <a href="<?php echo add_query_arg(['action' => 'new']); ?>" class="pzl-button pzl-button-primary">ایجاد جدید</a>
            </div>
            
            <?php
            $invoices = get_posts(['post_type' => 'pzl_pro_invoice', 'posts_per_page' => -1]);
            if ($invoices): ?>
                <table class="pzl-table">
                    <thead><tr><th>عنوان</th><th>مشتری</th><th>تاریخ</th><th>عملیات</th></tr></thead>
                    <tbody>
                        <?php foreach($invoices as $invoice): ?>
                        <tr>
                            <td><strong><?php echo esc_html($invoice->post_title); ?></strong></td>
                            <td><?php echo esc_html(get_the_author_meta('display_name', $invoice->post_author)); ?></td>
                            <td><?php echo get_the_date('Y/m/d', $invoice); ?></td>
                            <td>
                                <a href="<?php echo add_query_arg(['action' => 'edit', 'invoice_id' => $invoice->ID]); ?>" class="pzl-button pzl-button-secondary pzl-button-sm">ویرایش</a>
                                <form method="post" onsubmit="return confirm('آیا مطمئن هستید؟');" style="display: inline;">
                                    <input type="hidden" name="puzzling_action" value="delete_pro_invoice">
                                    <input type="hidden" name="item_id" value="<?php echo esc_attr($invoice->ID); ?>">
                                    <?php wp_nonce_field('puzzling_delete_pro_invoice_' . $invoice->ID, '_wpnonce'); ?>
                                    <button type="submit" class="pzl-button pzl-button-danger pzl-button-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>هیچ پیش‌فاکتوری یافت نشد.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>