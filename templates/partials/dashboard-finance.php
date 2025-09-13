<?php
/**
 * Finance Manager Dashboard Template
 *
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-money-alt" style="vertical-align: middle;"></span> نمای کلی قراردادها و پرداخت‌ها</h3>
    <p>در این بخش می‌توانید تمام قراردادهای ثبت شده در سیستم و وضعیت پرداخت اقساط مربوط به آن‌ها را مشاهده کنید.</p>
</div>

<div class="pzl-dashboard-section">
    <h4>لیست تمام اقساط</h4>
    <table class="pzl-table">
        <thead>
            <tr>
                <th>نام مشتری</th>
                <th>پروژه</th>
                <th>مبلغ قسط (تومان)</th>
                <th>تاریخ سررسید</th>
                <th>وضعیت</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $contracts = get_posts([
                'post_type' => 'contract',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ]);

            if (empty($contracts)) {
                echo '<tr><td colspan="5">هیچ قراردادی یافت نشد.</td></tr>';
            } else {
                foreach ($contracts as $contract) {
                    $project_id = get_post_meta($contract->ID, '_project_id', true);
                    $project_title = get_the_title($project_id);
                    $customer_id = $contract->post_author;
                    $customer_info = get_userdata($customer_id);
                    $customer_name = $customer_info ? $customer_info->display_name : 'کاربر حذف شده';
                    $installments = get_post_meta($contract->ID, '_installments', true);

                    if (is_array($installments)) {
                        foreach ($installments as $installment) {
                            $status = $installment['status'] ?? 'pending';
                            $status_text = ($status === 'paid') ? 'پرداخت شده' : 'در انتظار پرداخت';
                            $status_class = ($status === 'paid') ? 'status-paid' : 'status-pending';
                            
                            echo '<tr>';
                            echo '<td>' . esc_html($customer_name) . '</td>';
                            echo '<td>' . esc_html($project_title) . '</td>';
                            echo '<td>' . esc_html(number_format($installment['amount'])) . '</td>';
                            echo '<td>' . esc_html(date_i18n('Y/m/d', strtotime($installment['due_date']))) . '</td>';
                            echo '<td><span class="pzl-status ' . esc_attr($status_class) . '">' . $status_text . '</span></td>';
                            echo '</tr>';
                        }
                    }
                }
            }
            ?>
        </tbody>
    </table>
</div>

<style>
/* Re-using the same table styles from client dashboard */
.pzl-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.pzl-table th, .pzl-table td { padding: 12px; border: 1px solid #e0e0e0; text-align: right; }
.pzl-table th { background-color: #f9f9f9; }
.pzl-status { padding: 4px 8px; border-radius: 12px; font-size: 12px; color: #fff; }
.status-paid { background-color: var(--success-color); }
.status-pending { background-color: var(--warning-color); color: #333; }
</style>