<?php
/**
 * Template for System Manager to Manage Pro-forma Invoices - V3 (Layout Corrected)
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
                <h3><i class="fas fa-file-invoice"></i> <?php echo $invoice_to_edit ? 'ویرایش پیش‌فاکتور' : 'ایجاد پیش‌فاکتور جدید'; ?></h3>
                <a href="<?php echo remove_query_arg(['action', 'invoice_id']); ?>" class="pzl-button">&larr; بازگشت به لیست</a>
            </div>
            <form method="post" class="pzl-form pzl-ajax-form" id="pzl-pro-invoice-form" data-action="puzzling_manage_pro_invoice">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="invoice_id" value="<?php echo esc_attr($invoice_id); ?>">

                <h4><i class="fas fa-info-circle"></i> اطلاعات پایه</h4>
                <div class="pzl-form-row">
                    <div class="form-group">
                        <label for="customer_id">مربوط به مشتری</label>
                        <select name="customer_id" id="customer_id" required>
                            <option value="">-- انتخاب مشتری --</option>
                            <?php
                            $customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']);
                            $selected_customer = $invoice_to_edit ? $invoice_to_edit->post_author : 0;
                            foreach ($customers as $customer) {
                                echo '<option value="' . esc_attr($customer->ID) . '" ' . selected($selected_customer, $customer->ID, false) . '>' . esc_html($customer->display_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="project_id">مربوط به پروژه</label>
                        <select name="project_id" id="project_id" required>
                            <option value="">-- ابتدا مشتری را انتخاب کنید --</option>
                            <?php
                            if ($selected_customer) {
                                $projects = get_posts(['post_type' => 'project', 'author' => $selected_customer, 'posts_per_page' => -1]);
                                $selected_project = $invoice_to_edit ? get_post_meta($invoice_id, '_project_id', true) : 0;
                                foreach ($projects as $project) {
                                    echo '<option value="' . esc_attr($project->ID) . '" ' . selected($selected_project, $project->ID, false) . '>' . esc_html($project->post_title) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="pzl-form-row">
                    <div class="form-group">
                        <label for="pro_invoice_number">شماره پیش‌فاکتور</label>
                        <input type="text" id="pro_invoice_number" name="pro_invoice_number" value="<?php echo $invoice_to_edit ? esc_attr(get_post_meta($invoice_id, '_pro_invoice_number', true)) : 'در انتظار انتخاب پروژه...'; ?>" readonly class="ltr-input">
                    </div>
                    <div class="form-group">
                        <label for="issue_date">تاریخ صدور</label>
                        <input type="text" id="issue_date" name="issue_date" value="<?php echo jdate('Y/m/d'); ?>" class="pzl-jalali-date-picker" required>
                    </div>
                </div>

                <hr>
                <h4><i class="fas fa-cogs"></i> جزئیات خدمات و قیمت</h4>
                <div id="invoice-items-container">
                    <div class="pzl-form-row invoice-item-header">
                        <div class="header-item" style="flex-basis: 25%;">عنوان خدمت</div>
                        <div class="header-item" style="flex-basis: 30%;">توضیحات</div>
                        <div class="header-item" style="flex-basis: 15%;">قیمت (تومان)</div>
                        <div class="header-item" style="flex-basis: 15%;">تخفیف (تومان)</div>
                        <div class="header-item" style="flex-basis: 12%;">مبلغ کل</div>
                        <div class="header-item" style="flex-basis: 50px;"></div>
                    </div>
                    <div id="invoice-items-body">
                        <?php
                        $items = $invoice_to_edit ? get_post_meta($invoice_id, '_invoice_items', true) : [];
                        if (!empty($items) && is_array($items)) {
                            foreach ($items as $key => $item) {
                                echo '<div class="pzl-form-row invoice-item-row">';
                                echo '<div class="form-group"><input type="text" name="item_title[]" class="item-title" value="' . esc_attr($item['title']) . '" placeholder="عنوان خدمت" required></div>';
                                echo '<div class="form-group"><input type="text" name="item_desc[]" class="item-desc" value="' . esc_attr($item['desc']) . '" placeholder="توضیحات"></div>';
                                echo '<div class="form-group"><input type="text" name="item_price[]" class="item-price" value="' . esc_attr(number_format((float)$item['price'])) . '" placeholder="قیمت" required></div>';
                                echo '<div class="form-group"><input type="text" name="item_discount[]" class="item-discount" value="' . esc_attr(number_format((float)$item['discount'])) . '" placeholder="تخفیف"></div>';
                                echo '<div class="form-group item-total-wrapper"><span class="item-total">' . number_format((float)$item['price'] - (float)$item['discount']) . '</span></div>';
                                echo '<div class="form-group remove-btn-wrapper"><button type="button" class="pzl-button pzl-button-sm remove-item-btn" style="background: #dc3545 !important;">حذف</button></div>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <button type="button" id="add-invoice-item" class="pzl-button" style="align-self: flex-start;">افزودن ردیف جدید</button>

                <hr>
                <h4><i class="fas fa-file-invoice-dollar"></i> اطلاعات مالی و توضیحات</h4>
                <div class="pzl-financial-summary">
                    <div class="summary-item">
                        <span>جمع کل:</span>
                        <strong id="subtotal">0 تومان</strong>
                    </div>
                    <div class="summary-item">
                        <span>مبلغ نهایی (پس از تخفیف):</span>
                        <strong id="final-total">0 تومان</strong>
                    </div>
                </div>
                <div class="pzl-form-row">
                    <div class="form-group">
                        <label for="payment_method">نحوه پرداخت:</label>
                        <textarea id="payment_method" name="payment_method" rows="4"><?php echo $invoice_to_edit ? esc_textarea(get_post_meta($invoice_id, '_payment_method', true)) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="notes">یادداشت‌ها:</label>
                        <textarea id="notes" name="notes" rows="4"><?php echo $invoice_to_edit ? esc_textarea($invoice_to_edit->post_content) : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-submit">
                    <button type="submit" class="pzl-button"><?php echo $invoice_to_edit ? 'ذخیره و ارسال' : 'ایجاد و ارسال'; ?></button>
                </div>
            </form>
        </div>
    <?php else: // List View ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="fas fa-list-ul"></i> لیست پیش‌فاکتورها</h3>
                <a href="<?php echo add_query_arg('action', 'new'); ?>" class="pzl-button">ایجاد جدید</a>
            </div>
            <?php
            $invoices_query = new WP_Query(['post_type' => 'pzl_pro_invoice', 'posts_per_page' => 20, 'paged' => get_query_var('paged') ? get_query_var('paged') : 1]);
            if ($invoices_query->have_posts()): ?>
                <table class="pzl-table">
                    <thead><tr><th>شماره</th><th>مشتری</th><th>پروژه</th><th>مبلغ نهایی</th><th>تاریخ</th><th>عملیات</th></tr></thead>
                    <tbody>
                        <?php while($invoices_query->have_posts()): $invoices_query->the_post();
                            $invoice_id = get_the_ID();
                            $customer = get_userdata(get_post_field('post_author', $invoice_id));
                            $project_id = get_post_meta($invoice_id, '_project_id', true);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html(get_post_meta($invoice_id, '_pro_invoice_number', true)); ?></strong></td>
                            <td><?php echo $customer ? esc_html($customer->display_name) : '---'; ?></td>
                            <td><?php echo $project_id ? get_the_title($project_id) : '---'; ?></td>
                            <td><?php echo esc_html(number_format((float)get_post_meta($invoice_id, '_final_total', true))); ?> تومان</td>
                            <td><?php echo get_the_date('Y/m/d'); ?></td>
                            <td>
                                <a href="<?php echo add_query_arg(['action' => 'edit', 'invoice_id' => $invoice_id]); ?>" class="pzl-button pzl-button-sm">ویرایش</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php echo paginate_links(['total' => $invoices_query->max_num_pages]); ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                <div class="pzl-empty-state"><p>هیچ پیش‌فاکتوری یافت نشد.</p></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>