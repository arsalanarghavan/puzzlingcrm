<?php
/**
 * Finance Reports Template - Fully Completed & Improved
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Security check
if ( ! current_user_can('manage_options') ) {
    echo '<p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p>';
    return;
}

// Handle date filters
$start_date_str = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
$end_date_str = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

// Initialize variables
$income_in_range = 0;
$paid_installments_count = 0;
$overdue_customers = [];
$chart_data = [];
$pending_in_range = 0;

$contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);
$today = new DateTime('now', new DateTimeZone('Asia/Tehran'));
$today->setTime(0, 0, 0);

foreach ($contracts as $contract) {
    $installments = get_post_meta($contract->ID, '_installments', true);
    if (!is_array($installments)) continue;

    foreach ($installments as $inst) {
        if (!isset($inst['due_date']) || empty($inst['due_date'])) continue;

        try {
            $due_date_time = new DateTime($inst['due_date'], new DateTimeZone('Asia/Tehran'));
            $due_date_time->setTime(0, 0, 0);
        } catch (Exception $e) {
            error_log('PuzzlingCRM Report Error: Invalid date format in contract ID ' . $contract->ID);
            continue;
        }
        
        $is_in_range = true;
        if ($start_date_str && $due_date_time < new DateTime($start_date_str)) $is_in_range = false;
        if ($end_date_str && $due_date_time > (new DateTime($end_date_str))->setTime(23, 59, 59)) $is_in_range = false;

        if ($is_in_range) {
            $amount = (int)($inst['amount'] ?? 0);
            if (isset($inst['status']) && $inst['status'] === 'paid') {
                $income_in_range += $amount;
                $paid_installments_count++;
                
                $paid_month = $due_date_time->format('Y-m');
                if (!isset($chart_data[$paid_month])) $chart_data[$paid_month] = 0;
                $chart_data[$paid_month] += $amount;
            } else {
                 $pending_in_range += $amount;
            }

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
ksort($chart_data);
?>
<div class="pzl-card">
    <h3><span class="dashicons dashicons-filter"></span> فیلتر گزارش مالی</h3>
    <form method="get" class="pzl-form">
        <input type="hidden" name="view" value="reports">
        <input type="hidden" name="tab" value="finance">
        <div class="pzl-form-row" style="align-items: flex-end;">
            <div class="form-group">
                <label for="start_date">از تاریخ:</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date_str); ?>">
            </div>
            <div class="form-group">
                <label for="end_date">تا تاریخ:</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date_str); ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="pzl-button pzl-button-secondary">اعمال فیلتر</button>
            </div>
        </div>
    </form>
</div>

<div class="finance-report-grid">
    <div class="report-card">
        <h4><span class="dashicons dashicons-money-alt"></span> درآمد در بازه</h4>
        <span class="stat-number"><?php echo esc_html(number_format($income_in_range)); ?></span>
        <span class="stat-label">تومان</span>
    </div>
    <div class="report-card">
        <h4><span class="dashicons dashicons-hourglass"></span> مبالغ در انتظار پرداخت</h4>
        <span class="stat-number"><?php echo esc_html(number_format($pending_in_range)); ?></span>
        <span class="stat-label">تومان</span>
    </div>
    <div class="report-card">
        <h4><span class="dashicons dashicons-yes"></span> اقساط پرداخت شده</h4>
        <span class="stat-number"><?php echo esc_html($paid_installments_count); ?></span>
        <span class="stat-label">قسط</span>
    </div>
    <div class="report-card">
        <h4><span class="dashicons dashicons-warning"></span> مشتریان با پرداخت معوق</h4>
        <span class="stat-number"><?php echo esc_html(count($overdue_customers)); ?></span>
        <span class="stat-label">مشتری</span>
    </div>
</div>

<div class="pzl-card">
    <h4>نمودار درآمد ماهانه در بازه انتخابی (تومان)</h4>
    <div class="pzl-chart-container">
        <?php if (!empty($chart_data)): 
            $max_amount = max($chart_data) > 0 ? max($chart_data) : 1;
        ?>
            <?php foreach($chart_data as $month => $amount): ?>
                <div class="chart-bar-wrapper">
                    <div class="chart-bar" style="height: <?php echo esc_attr(max(1, ($amount / $max_amount) * 250)); ?>px;" title="<?php echo esc_attr(date_i18n('F Y', strtotime($month . '-01'))) . ': ' . esc_attr(number_format($amount)); ?>"></div>
                    <div class="chart-label"><?php echo esc_html(date_i18n('M Y', strtotime($month . '-01'))); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>داده‌ای برای نمایش نمودار در این بازه زمانی وجود ندارد.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($overdue_customers)): ?>
<div class="pzl-card">
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