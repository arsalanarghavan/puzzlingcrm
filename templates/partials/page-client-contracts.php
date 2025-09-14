<?php
/**
 * Template for Client to view their contracts.
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;

$contracts = get_posts([
    'post_type' => 'contract',
    'author' => get_current_user_id(),
    'posts_per_page' => -1,
]);
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-file-signature"></i> قراردادهای شما</h3>

    <?php if (empty($contracts)): ?>
        <div class="pzl-empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <h4>قراردادی یافت نشد</h4>
            <p>شما در حال حاضر هیچ قرارداد فعالی ندارید.</p>
        </div>
    <?php else: ?>
        <table class="pzl-table">
            <thead>
                <tr>
                    <th>قرارداد برای پروژه</th>
                    <th>مبلغ کل (تومان)</th>
                    <th>مبلغ پرداخت شده (تومان)</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($contracts as $contract): 
                    $project_id = get_post_meta($contract->ID, '_project_id', true);
                    $installments = get_post_meta($contract->ID, '_installments', true);
                    
                    $total_amount = 0;
                    $paid_amount = 0;
                    if (is_array($installments)) {
                        foreach($installments as $inst) {
                            $total_amount += (int)$inst['amount'];
                            if ($inst['status'] === 'paid') {
                                $paid_amount += (int)$inst['amount'];
                            }
                        }
                    }
                    $is_paid = ($paid_amount >= $total_amount && $total_amount > 0);
                ?>
                <tr>
                    <td><strong><?php echo esc_html(get_the_title($project_id)); ?></strong></td>
                    <td><?php echo esc_html(number_format($total_amount)); ?></td>
                    <td><?php echo esc_html(number_format($paid_amount)); ?></td>
                    <td>
                        <span class="pzl-status <?php echo $is_paid ? 'status-paid' : 'status-pending'; ?>">
                            <?php echo $is_paid ? 'تکمیل پرداخت' : 'در حال پرداخت'; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>