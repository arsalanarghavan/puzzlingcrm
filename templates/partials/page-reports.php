<?php
/**
 * Main Reports Page Template - V2 (Upgraded)
 * This template acts as a router for different report tabs: Finance, Tasks, and Agile.
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) {
    echo '<p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p>';
    return;
}

$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_key($_GET[ 'tab' ]) : 'finance';
$base_url = remove_query_arg(['puzzling_notice', 'tab']); // Base URL for tabs
$reports_url = add_query_arg('view', 'reports', $base_url); // Ensure view=reports is always present
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-chart-area"></i> گزارش‌ها</h3>

    <div class="pzl-dashboard-tabs">
        <a href="<?php echo esc_url(add_query_arg('tab', 'finance', $reports_url)); ?>" class="pzl-tab <?php echo $active_tab == 'finance' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i> گزارش مالی
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'tasks', $reports_url)); ?>" class="pzl-tab <?php echo $active_tab == 'tasks' ? 'active' : ''; ?>">
            <i class="fas fa-tasks"></i> گزارش وظایف
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'tickets', $reports_url)); ?>" class="pzl-tab <?php echo $active_tab == 'tickets' ? 'active' : ''; ?>">
            <i class="fas fa-life-ring"></i> گزارش تیکت‌ها
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'agile', $reports_url)); ?>" class="pzl-tab <?php echo $active_tab == 'agile' ? 'active' : ''; ?>">
            <i class="fas fa-rocket"></i> گزارش Agile
        </a>
    </div>

    <div class="pzl-dashboard-tab-content" style="margin-top: 20px;">
        <?php
        $template_path = '';
        switch ($active_tab) {
            case 'tasks':
                $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-tasks.php';
                break;
            case 'tickets':
                $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-tickets.php';
                break;
            case 'agile':
                $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-agile.php';
                break;
            case 'finance':
            default:
                $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-finance.php';
                break;
        }

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="pzl-alert pzl-alert-error">فایل قالب گزارش یافت نشد.</div>';
        }
        ?>
    </div>
</div>