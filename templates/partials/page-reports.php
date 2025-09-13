<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_key($_GET[ 'tab' ]) : 'finance';
$base_url = remove_query_arg('puzzling_notice');
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-chart-area"></i> گزارش‌ها</h3>

    <div class="pzl-dashboard-tabs">
        <a href="<?php echo add_query_arg(['view' => 'reports', 'tab' => 'finance'], $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'finance' ? 'active' : ''; ?>"><i class="fas fa-money-bill-wave"></i> گزارش مالی</a>
        <a href="<?php echo add_query_arg(['view' => 'reports', 'tab' => 'tasks'], $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'tasks' ? 'active' : ''; ?>"><i class="fas fa-tasks"></i> گزارش وظایف</a>
    </div>

    <div class="pzl-dashboard-tab-content" style="margin-top: 20px;">
        <?php
        if ( $active_tab == 'tasks' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-tasks.php';
        } else {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-finance.php';
        }
        ?>
    </div>
</div>