<?php
/**
 * Finance Manager Dashboard Template - FULLY UPGRADED
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_tab = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'overview';
?>

<div class="pzl-dashboard-tabs">
    <a href="?view=overview" class="pzl-tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>"> <span class="dashicons dashicons-dashboard"></span> نمای کلی </a>
    <a href="?view=reports" class="pzl-tab <?php echo $active_tab === 'reports' ? 'active' : ''; ?>"> <span class="dashicons dashicons-chart-area"></span> گزارش‌گیری </a>
</div>

<div class="pzl-dashboard-tab-content">
<?php if ($active_tab === 'reports'): ?>
    <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-finance.php'; ?>
<?php else: ?>
    <?php
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
                        } catch (Exception $e) {}
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
<?php endif; ?>
</div>

<style>
/* Styles for stats, tabs, and tables */
</style>