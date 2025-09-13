<?php
/**
 * Finance Manager Dashboard Template - IMPROVED
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Stats for widgets
$total_income = 0;
$pending_amount = 0;
$overdue_installments = 0;
$today = new DateTime('now', new DateTimeZone('Asia/Tehran'));
$contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);

foreach ($contracts as $contract) {
    $installments = get_post_meta($contract->ID, '_installments', true);
    if (is_array($installments)) {
        foreach ($installments as $inst) {
            if (isset($inst['status']) && $inst['status'] === 'paid' && isset($inst['amount'])) {
                $total_income += (int)$inst['amount'];
            } else if (isset($inst['amount'])) {
                $pending_amount += (int)$inst['amount'];
                if (isset($inst['due_date'])) {
                    try {
                        $due_date = new DateTime($inst['due_date'], new DateTimeZone('Asia/Tehran'));
                        if ($due_date < $today) {
                            $overdue_installments++;
                        }
                    } catch (Exception $e) {
                        // Ignore invalid dates
                    }
                }
            }
        }
    }
}
?>

<div class="pzl-dashboard-stats">
    <div class="stat-widget">
        <h4>درآمد کل (تومان)</h4>
        <span class="stat-number"><?php echo number_format($total_income); ?></span>
    </div>
    <div class="stat-widget">
        <h4>مبلغ در انتظار پرداخت (تومان)</h4>
        <span class="stat-number"><?php echo number_format($pending_amount); ?></span>
    </div>
    <div class="stat-widget">
        <h4>اقساط معوق</h4>
        <span class="stat-number"><?php echo $overdue_installments; ?></span>
    </div>
</div>

<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-list-view" style="vertical-align: middle;"></span> لیست تمام اقساط</h3>
    <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/common/payments-table.php'; ?>
</div>

<style>
.pzl-dashboard-stats { display: flex; gap: 20px; margin-bottom: 30px; }
.stat-widget { flex: 1; background: #fff; border: 1px solid #e0e0e0; padding: 20px; border-radius: 5px; text-align: center; }
.stat-widget h4 { margin: 0 0 10px; font-size: 16px; color: #555; }
.stat-widget .stat-number { font-size: 32px; font-weight: bold; color: var(--primary-color, #F0192A); }
</style>