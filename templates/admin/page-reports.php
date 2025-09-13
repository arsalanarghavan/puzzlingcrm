<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<h1><span class="dashicons dashicons-chart-area"></span> گزارش‌ها</h1>
<p>این بخش برای نمایش گزارش‌های مالی، عملکرد تیم و وضعیت پروژه‌ها طراحی شده است.</p>
<?php 
// You can include the finance report partial here as a starting point.
$finance_report_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-finance.php';
if (file_exists($finance_report_path)) {
    include $finance_report_path;
}
?>