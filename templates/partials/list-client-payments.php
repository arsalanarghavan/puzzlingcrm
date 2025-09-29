<?php
/**
 * Template for listing the client's payment installments.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();
$customer_id = $current_user->ID;

$contracts = get_posts([
    'post_type' => 'contract',
    'author' => $customer_id,
    'posts_per_page' => -1,
]);

$all_installments = [];
if ($contracts) {
    foreach ($contracts as $contract) {
        $installments = get_post_meta($contract->ID, '_installments', true);
        if (is_array($installments)) {
            $project_title = get_the_title(get_post_meta($contract->ID, '_project_id', true));
            foreach ($installments as $index => $installment) {
                $installment['project_title'] = $project_title;
                $installment['contract_id'] = $contract->ID;
                $installment['installment_index'] = $index;
                $all_installments[] = $installment;
            }
        }
    }
}
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-money-bill-wave"></i> وضعیت پرداخت‌ها و اقساط</h3>
    <?php if (empty($all_installments)) : ?>
        <div class="pzl-empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <h4>برنامه پرداختی یافت نشد</h4>
            <p>در حال حاضر هیچ برنامه پرداخت یا قسطی برای شما ثبت نشده است.</p>
        </div>
    <?php else : ?>
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
            foreach ($all_installments as $installment) {
                $status = $installment['status'] ?? 'pending';
                $status_text = ($status === 'paid') ? 'پرداخت شده' : 'در انتظار پرداخت';
                $status_class = ($status === 'paid') ? 'status-paid' : 'status-pending';
                
                echo '<tr>';
                echo '<td>' . esc_html($installment['project_title']) . '</td>';
                echo '<td>' . esc_html(number_format($installment['amount'])) . '</td>';
                echo '<td>' . jdate('Y/m/d', strtotime($installment['due_date'])) . '</td>';
                echo '<td><span class="pzl-status ' . esc_attr($status_class) . '">' . $status_text . '</span></td>';
                echo '<td>';
                if ($status === 'pending') {
                    $nonce = wp_create_nonce('pay_installment_' . $installment['contract_id'] . '_' . $installment['installment_index']);
                    $payment_url = add_query_arg([
                        'puzzling_action' => 'pay_installment',
                        'contract_id' => $installment['contract_id'],
                        'installment_index' => $installment['installment_index'],
                        '_wpnonce' => $nonce,
                    ], get_permalink());

                    echo '<a href="' . esc_url($payment_url) . '" class="pzl-button">پرداخت آنلاین</a>';
                } else {
                    echo '<span>—</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>