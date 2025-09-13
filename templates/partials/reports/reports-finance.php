<?php
/**
 * Finance Reports Template - Fully Completed & Improved
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Security check: Only users with 'manage_options' capability can view this page.
if ( ! current_user_can('manage_options') ) {
    echo '<p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p>';
    return;
}

// Handle date filters from GET request and sanitize them.
$start_date_str = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
$end_date_str = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

// Initialize variables for statistics.
$income_in_range = 0;
$paid_installments_count = 0;
$overdue_customers = [];
$chart_data = [];

// Fetch all contracts to process their installments.
$contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);
$today = new DateTime('now', new DateTimeZone('Asia/Tehran'));
$today->setTime(0, 0, 0); // Set time to beginning of the day for accurate comparison.

foreach ($contracts as $contract) {
    $installments = get_post_meta($contract->ID, '_installments', true);
    if (!is_array($installments)) continue;

    foreach ($installments as $inst) {
        if (!isset($inst['due_date']) || empty($inst['due_date'])) continue;

        try {
            $due_date_time = new DateTime($inst['due_date'], new DateTimeZone('Asia/Tehran'));
            $due_date_time->setTime(0, 0, 0);
        } catch (Exception $e) {
            // Log error for invalid date format and skip this installment.
            error_log('PuzzlingCRM Report Error: Invalid date format in contract ID ' . $contract->ID);
            continue;
        }
        
        // Check if the installment's due date is within the selected date range.
        $is_in_range = true;
        if ($start_date_str && $due_date_time < new DateTime($start_date_str)) $is_in_range = false;
        if ($end_date_str && $due_date_time > (new DateTime($end_date_str))->setTime(23, 59, 59)) $is_in_range = false;

        if ($is_in_range) {
            // Calculate total income and paid installments in the date range.
            if (isset($inst['status']) && $inst['status'] === 'paid' && isset($inst['amount'])) {
                $income_in_range += (int)$inst['amount'];
                $paid_installments_count++;
                
                // Prepare data for the monthly income chart.
                $paid_month = $due_date_time->format('Y-m');
                if (!isset($chart_data[$paid_month])) {
                    $chart_data[$paid_month] = 0;
                }
                $chart_data[$paid_month] += (int)$inst['amount'];
            }
            // Identify customers with overdue payments in the date range.
            if (isset($inst['status']) && $inst['status'] !== 'paid' && $due_date_time < $today) {
                $customer_id = $contract->post_author;
                if (!isset($overdue_customers[$customer_id])) {
                    $customer_info = get_userdata($customer_id);
                    $overdue_customers[$customer_id] = [
                        'name' => $customer_info ? $customer_info->display_name : 'کاربر حذف شده',
                        'count' => 0,
                    ];
                }
                $overdue_customers[$customer_id]['count']++;
            }
        }
    }
}
ksort($chart_data); // Sort chart data by month.
?>

<h3><span class="dashicons dashicons-chart-area"></span> گزارش‌های مالی</h3>
<form method="get" class="pzl-form-container">
    <input type="hidden" name="view" value="reports">
    <div class="form-row">
        <div>
            <label for="start_date">از تاریخ:</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date_str); ?>">
        </div>
        <div>
            <label for="end_date">تا تاریخ:</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date_str); ?>">
        </div>
        <button type="submit" class="pzl-button pzl-button-primary">اعمال فیلتر</button>
    </div>
</form>

<div class="pzl-dashboard-stats">
    <div class="stat-widget">
        <h4>درآمد در بازه (تومان)</h4>
        <span class="stat-number"><?php echo esc_html(number_format($income_in_range)); ?></span>
    </div>
    <div class="stat-widget">
        <h4>اقساط پرداخت شده</h4>
        <span class="stat-number"><?php echo esc_html($paid_installments_count); ?></span>
    </div>
    <div class="stat-widget">
        <h4>مشتریان با پرداخت معوق</h4>
        <span class="stat-number"><?php echo esc_html(count($overdue_customers)); ?></span>
    </div>
</div>

<div class="pzl-dashboard-section">
    <h4>نمودار درآمد ماهانه در بازه انتخابی (تومان)</h4>
    <div class="pzl-chart-container">
        <?php if (!empty($chart_data)): 
            $max_amount = max($chart_data) > 0 ? max($chart_data) : 1;
        ?>
            <?php foreach($chart_data as $month => $amount): ?>
                <div class="chart-bar-wrapper">
                    <div class="chart-bar" style="height: <?php echo esc_attr(max(1, ($amount / $max_amount) * 200)); ?>px;" title="<?php echo esc_attr(date_i18n('F Y', strtotime($month . '-01'))) . ': ' . esc_attr(number_format($amount)); ?>"></div>
                    <div class="chart-label"><?php echo esc_html(date_i18n('M', strtotime($month . '-01'))); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>داده‌ای برای نمایش نمودار در این بازه زمانی وجود ندارد.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($overdue_customers)): ?>
<div class="pzl-dashboard-section">
    <h4>لیست مشتریان با پرداخت معوق در بازه انتخابی</h4>
    <table class="pzl-table">
        <thead>
            <tr>
                <th>نام مشتری</th>
                <th>تعداد اقساط معوق</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($overdue_customers as $customer): ?>
            <tr>
                <td><?php echo esc_html($customer['name']); ?></td>
                <td><?php echo esc_html($customer['count']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<style>
.pzl-form-container .form-row { display: flex; align-items: flex-end; gap: 15px; background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; flex-wrap: wrap; }
.pzl-chart-container { display: flex; align-items: flex-end; gap: 10px; border: 1px solid #e0e0e0; padding: 20px; border-radius: 5px; height: 250px; background: #fcfcfc; }
.chart-bar-wrapper { flex: 1; text-align: center; display: flex; flex-direction: column; justify-content: flex-end; }
.chart-bar { background-color: var(--primary-color, #F0192A); width: 80%; max-width: 50px; margin: 0 auto; transition: background-color 0.3s, opacity 0.3s; opacity: 0.8; border-radius: 3px 3px 0 0; }
.chart-bar:hover { background-color: var(--secondary-color, #1D1E29); opacity: 1; }
.chart-label { font-size: 12px; margin-top: 5px; color: #555; }
</style>