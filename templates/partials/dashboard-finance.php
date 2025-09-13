<?php
/**
 * Finance Manager Dashboard Template - CACHED & UPGRADED
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_tab = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'overview';
?>

<div class="pzl-dashboard-tabs">
    <a href="?view=overview" class="pzl-tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>"> <i class="fas fa-tachometer-alt"></i> <?php esc_html_e('Overview', 'puzzlingcrm'); ?></a>
    <a href="?view=reports" class="pzl-tab <?php echo $active_tab === 'reports' ? 'active' : ''; ?>"> <i class="fas fa-chart-area"></i> <?php esc_html_e('Reports', 'puzzlingcrm'); ?></a>
</div>

<div class="pzl-dashboard-tab-content">
<?php if ($active_tab === 'reports'): ?>
    <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-finance.php'; ?>
<?php else: ?>
    <?php
    // Use transient caching for stats
    if ( false === ( $stats = get_transient( 'puzzling_finance_stats' ) ) ) {
        $stats = [
            'total_income' => 0,
            'pending_amount' => 0,
            'overdue_installments' => 0,
        ];
        $today = new DateTime('now', new DateTimeZone('Asia/Tehran'));
        $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);

        foreach ($contracts as $contract) {
            $installments = get_post_meta($contract->ID, '_installments', true);
            if (is_array($installments)) {
                foreach ($installments as $inst) {
                    if (isset($inst['status']) && $inst['status'] === 'paid' && isset($inst['amount'])) {
                        $stats['total_income'] += (int)$inst['amount'];
                    } else if (isset($inst['amount'])) {
                        $stats['pending_amount'] += (int)$inst['amount'];
                        if (isset($inst['due_date']) && !empty($inst['due_date'])) {
                            try {
                                if (new DateTime($inst['due_date'], new DateTimeZone('Asia/Tehran')) < $today) {
                                    $stats['overdue_installments']++;
                                }
                            } catch (Exception $e) {
                                // In case of invalid date format, log the error for debugging
                                error_log('PuzzlingCRM: Invalid date format for contract ID ' . $contract->ID);
                            }
                        }
                    }
                }
            }
        }
        // Cache the stats for 1 hour
        set_transient( 'puzzling_finance_stats', $stats, HOUR_IN_SECONDS );
    }
    ?>
    <div class="pzl-dashboard-stats">
        <div class="stat-widget">
            <h4><?php esc_html_e('Total Income (Toman)', 'puzzlingcrm'); ?></h4>
            <span class="stat-number"><?php echo esc_html( number_format($stats['total_income']) ); ?></span>
        </div>
        <div class="stat-widget">
            <h4><?php esc_html_e('Pending Amount (Toman)', 'puzzlingcrm'); ?></h4>
            <span class="stat-number"><?php echo esc_html( number_format($stats['pending_amount']) ); ?></span>
        </div>
        <div class="stat-widget">
            <h4><?php esc_html_e('Overdue Installments', 'puzzlingcrm'); ?></h4>
            <span class="stat-number"><?php echo esc_html( $stats['overdue_installments'] ); ?></span>
        </div>
    </div>
    <div class="pzl-dashboard-section">
        <h3><i class="fas fa-list-ul" style="vertical-align: middle;"></i> <?php esc_html_e('All Installments List', 'puzzlingcrm'); ?></h3>
        <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/common/payments-table.php'; ?>
    </div>
<?php endif; ?>
</div>
<style>
.pzl-dashboard-tabs { border-bottom: 2px solid #e0e0e0; margin-bottom: 20px; display: flex; }
.pzl-tab { padding: 10px 20px; text-decoration: none; color: #555; border-bottom: 2px solid transparent; margin-bottom: -2px; }
.pzl-tab.active { color: var(--pzl-primary-color, #F0192A); border-bottom-color: var(--pzl-primary-color, #F0192A); font-weight: bold; }
.pzl-tab .fas { vertical-align: middle; margin-left: 5px; }
.pzl-dashboard-stats { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
.stat-widget { flex: 1; min-width: 200px; background: #fff; border: 1px solid #e0e0e0; padding: 20px; border-radius: 5px; text-align: center; }
.stat-widget h4 { margin: 0 0 10px; font-size: 16px; color: #555; }
.stat-widget .stat-number { font-size: 32px; font-weight: bold; color: var(--pzl-primary-color, #F0192A); }
</style>