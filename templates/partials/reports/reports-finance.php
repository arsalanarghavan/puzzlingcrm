<?php
/**
 * Finance Reports Template
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) {
    echo '<p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p>';
    return;
}

// Handle date filters
$start_date_str = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
$end_date_str = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

$income_in_range = 0;
$paid_installments_count = 0;
$overdue_customers = [];

$contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);
$today = new DateTime('now', new DateTimeZone('Asia/Tehran'));

foreach ($contracts as $contract) {
    $installments = get_post_meta($contract->ID, '_installments', true);
    if (!is_array($installments)) continue;

    foreach ($installments as $inst) {
        if (!isset($inst['due_date']) || empty($inst['due_date'])) continue;

        try {
            $due_date_time = new DateTime($inst['due_date'], new DateTimeZone('Asia/Tehran'));
        } catch (Exception $e) {
            continue; // Skip invalid dates
        }
        
        $is_in_range = true;
        if ($start_date_str && $due_date_time < new DateTime($start_date_str)) $is_in_range = false;
        if ($end_date_str && $due_date_time > new DateTime($end_date_str)) $is_in_range = false;

        if ($is_in_range) {
            if (isset($inst['status']) && $inst['status'] === 'paid' && isset($inst['amount'])) {
                $income_in_range += (int)$inst['amount'];
                $paid_installments_count++;
            }
            if (isset($inst['status']) && $inst['status'] === 'pending' && $due_date_time < $today) {
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
.pzl-form-container .form-row { display: flex; align-items: flex-end; gap: 15px; background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
</style>