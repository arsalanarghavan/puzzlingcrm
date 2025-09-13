<?php
/**
 * Client Dashboard Template
 *
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$current_user = wp_get_current_user();
$customer_id = $current_user->ID;
?>

<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-portfolio" style="vertical-align: middle;"></span> پروژه‌های شما</h3>
    <?php
    $projects = get_posts([
        'post_type' => 'project',
        'author' => $customer_id,
        'posts_per_page' => -1,
    ]);

    if (empty($projects)) {
        echo '<p>شما در حال حاضر هیچ پروژه‌ای ندارید.</p>';
    } else {
        foreach ($projects as $project) {
            echo '<h4>' . esc_html($project->post_title) . '</h4>';
        }
    }
    ?>
</div>

<hr style="margin: 30px 0;">

<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-money-alt" style="vertical-align: middle;"></span> وضعیت پرداخت‌ها و اقساط</h3>
    <?php
    $contracts = get_posts([
        'post_type' => 'contract',
        'author' => $customer_id,
        'posts_per_page' => -1,
    ]);

    if (empty($contracts)) {
        echo '<p>هیچ برنامه پرداختی برای شما ثبت نشده است.</p>';
    } else {
    ?>
    <table class="pzl-table">
        <thead>
            <tr>
                <th>پروژه</th>
                <th>مبلغ قسط (تومان)</th>
                <th>تاریخ سررسید</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($contracts as $contract) {
                $project_id = get_post_meta($contract->ID, '_project_id', true);
                $project_title = get_the_title($project_id);
                $installments = get_post_meta($contract->ID, '_installments', true);

                if (is_array($installments)) {
                    foreach ($installments as $index => $installment) {
                        $status = $installment['status'] ?? 'pending';
                        $status_text = ($status === 'paid') ? 'پرداخت شده' : 'در انتظار پرداخت';
                        $status_class = ($status === 'paid') ? 'status-paid' : 'status-pending';
                        
                        echo '<tr>';
                        echo '<td>' . esc_html($project_title) . '</td>';
                        echo '<td>' . esc_html(number_format($installment['amount'])) . '</td>';
                        echo '<td>' . esc_html(date_i18n('Y/m/d', strtotime($installment['due_date']))) . '</td>';
                        echo '<td><span class="pzl-status ' . esc_attr($status_class) . '">' . $status_text . '</span></td>';
                        echo '<td>';
                        if ($status === 'pending') {
                            $nonce = wp_create_nonce('pay_installment_' . $contract->ID . '_' . $index);
                            $payment_url = add_query_arg(['puzzling_action' => 'pay_installment', 'contract_id' => $contract->ID, 'installment_index' => $index, '_wpnonce' => $nonce, ], get_permalink());
                            echo '<a href="' . esc_url($payment_url) . '" class="pzl-button pzl-button-primary">پرداخت آنلاین</a>';
                        } else {
                            echo '<span>—</span>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                }
            }
            ?>
        </tbody>
    </table>
    <?php } ?>
</div>

<hr style="margin: 30px 0;">

<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-sos" style="vertical-align: middle;"></span> پشتیبانی و تیکت‌ها</h3>
    <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/list-tickets.php'; ?>
</div>

<style>
.pzl-dashboard-section { margin-bottom: 30px; }
.pzl-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
.pzl-table th, .pzl-table td { padding: 12px 15px; border: 1px solid #e0e0e0; text-align: right; vertical-align: middle; }
.pzl-table th { background-color: #f9f9f9; font-weight: bold; }
.pzl-status { display: inline-block; padding: 5px 10px; border-radius: 15px; font-size: 12px; color: #fff; min-width: 90px; text-align: center; }
.status-paid { background-color: var(--success-color, #28a745); }
.status-pending { background-color: var(--warning-color, #ffc107); color: #333; }
</style>