<?php
/**
 * Advanced Finance Reports with Charts
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

// Calculate financial stats
$total_revenue = 0;
$total_pending = 0;
$total_paid = 0;

$contracts = get_posts([
    'post_type' => 'contract',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

foreach ($contracts as $contract) {
    $amount = (float) get_post_meta($contract->ID, '_total_amount', true);
    $total_revenue += $amount;
}

// Calculate payment stats
$all_payments = get_posts([
    'post_type' => 'payment',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

foreach ($all_payments as $payment) {
    $status = get_post_meta($payment->ID, '_payment_status', true);
    $amount = (float) get_post_meta($payment->ID, '_payment_amount', true);
    
    if ($status === 'paid') {
        $total_paid += $amount;
    } else {
        $total_pending += $amount;
    }
}

$payment_ratio = $total_revenue > 0 ? ($total_paid / $total_revenue) * 100 : 0;
?>

<div class="row">
    <!-- Financial Overview Cards -->
    <div class="col-xl-12 mb-4">
        <div class="row">
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card custom-card border border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="mb-2">
                                    <span class="avatar avatar-md avatar-rounded bg-primary">
                                        <i class="ri-money-dollar-circle-line fs-20"></i>
                                    </span>
                                </div>
                                <p class="mb-1 text-muted">کل درآمد</p>
                                <h4 class="fw-semibold mb-0 text-primary"><?php echo number_format($total_revenue); ?></h4>
                                <small class="text-muted">تومان</small>
                            </div>
                            <div id="total-revenue-spark"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card custom-card border border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="mb-2">
                                    <span class="avatar avatar-md avatar-rounded bg-success">
                                        <i class="ri-checkbox-circle-line fs-20"></i>
                                    </span>
                                </div>
                                <p class="mb-1 text-muted">پرداخت شده</p>
                                <h4 class="fw-semibold mb-0 text-success"><?php echo number_format($total_paid); ?></h4>
                                <small class="text-success"><?php echo number_format($payment_ratio, 1); ?>%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card custom-card border border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="mb-2">
                                    <span class="avatar avatar-md avatar-rounded bg-warning">
                                        <i class="ri-time-line fs-20"></i>
                                    </span>
                                </div>
                                <p class="mb-1 text-muted">در انتظار پرداخت</p>
                                <h4 class="fw-semibold mb-0 text-warning"><?php echo number_format($total_pending); ?></h4>
                                <small class="text-warning"><?php echo count($all_payments); ?> فقره</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card custom-card border border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="mb-2">
                                    <span class="avatar avatar-md avatar-rounded bg-info">
                                        <i class="ri-file-text-line fs-20"></i>
                                    </span>
                                </div>
                                <p class="mb-1 text-muted">تعداد قراردادها</p>
                                <h4 class="fw-semibold mb-0 text-info"><?php echo count($contracts); ?></h4>
                                <small class="text-info">فعال</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="col-xl-8">
        <!-- Revenue Trend -->
        <div class="card custom-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-line-chart-line me-2 text-primary"></i>روند درآمد ماهانه
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary active" data-period="6">6 ماه</button>
                    <button class="btn btn-outline-primary" data-period="12">سالانه</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="revenueMonthlyChart" height="80"></canvas>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-bank-card-line me-2 text-success"></i>روش‌های پرداخت
                </div>
            </div>
            <div class="card-body">
                <canvas id="paymentMethodsChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-xl-4">
        <!-- Payment Status Breakdown -->
        <div class="card custom-card mb-4">
            <div class="card-header bg-primary-transparent">
                <div class="card-title text-primary">
                    <i class="ri-pie-chart-2-line me-2"></i>وضعیت پرداخت‌ها
                </div>
            </div>
            <div class="card-body">
                <canvas id="paymentStatusChart" height="200"></canvas>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="card custom-card">
            <div class="card-header bg-success-transparent">
                <div class="card-title text-success">
                    <i class="ri-vip-crown-line me-2"></i>مشتریان برتر (درآمد)
                </div>
            </div>
            <div class="card-body">
                <?php
                $customer_revenues = [];
                foreach ($contracts as $contract) {
                    $customer_id = get_post_meta($contract->ID, '_customer_id', true);
                    $amount = (float) get_post_meta($contract->ID, '_total_amount', true);
                    
                    if (!isset($customer_revenues[$customer_id])) {
                        $customer_revenues[$customer_id] = 0;
                    }
                    $customer_revenues[$customer_id] += $amount;
                }
                
                arsort($customer_revenues);
                $top_customers = array_slice($customer_revenues, 0, 5, true);
                
                if (!empty($top_customers)):
                    $rank = 1;
                    foreach ($top_customers as $customer_id => $revenue):
                        $customer = get_user_by('id', $customer_id);
                        if (!$customer) continue;
                ?>
                <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-2" style="min-width: 25px;"><?php echo $rank++; ?></span>
                        <?php echo get_avatar($customer->ID, 32, '', '', ['class' => 'rounded-circle me-2']); ?>
                        <div>
                            <div class="fw-semibold"><?php echo esc_html($customer->display_name); ?></div>
                            <small class="text-success"><?php echo number_format($revenue); ?> تومان</small>
                        </div>
                    </div>
                </div>
                <?php
                    endforeach;
                else:
                ?>
                <p class="text-muted text-center">اطلاعاتی موجود نیست</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="col-xl-12 mt-4">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-file-list-3-line me-2 text-primary"></i>آخرین تراکنش‌ها
                </div>
                <button class="btn btn-sm btn-primary" id="export-finance-excel">
                    <i class="ri-file-excel-line me-1"></i>دریافت اکسل
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>مشتری</th>
                                <th>قرارداد</th>
                                <th>مبلغ</th>
                                <th>تاریخ</th>
                                <th>وضعیت</th>
                                <th>روش پرداخت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_payments = get_posts([
                                'post_type' => 'payment',
                                'posts_per_page' => 10,
                                'post_status' => 'publish',
                                'orderby' => 'date',
                                'order' => 'DESC'
                            ]);
                            
                            if (!empty($recent_payments)):
                                foreach ($recent_payments as $payment):
                                    $status = get_post_meta($payment->ID, '_payment_status', true);
                                    $amount = get_post_meta($payment->ID, '_payment_amount', true);
                                    $method = get_post_meta($payment->ID, '_payment_method', true) ?: 'نامشخص';
                                    $contract_id = get_post_meta($payment->ID, '_contract_id', true);
                                    $customer_id = get_post_meta($payment->ID, '_customer_id', true);
                                    
                                    $status_badges = [
                                        'paid' => '<span class="badge bg-success-transparent">پرداخت شده</span>',
                                        'pending' => '<span class="badge bg-warning-transparent">در انتظار</span>',
                                        'failed' => '<span class="badge bg-danger-transparent">ناموفق</span>'
                                    ];
                            ?>
                            <tr>
                                <td><span class="fw-semibold">#<?php echo $payment->ID; ?></span></td>
                                <td>
                                    <?php 
                                    $customer = get_user_by('id', $customer_id);
                                    echo $customer ? esc_html($customer->display_name) : '---';
                                    ?>
                                </td>
                                <td><a href="#" class="text-primary">قرارداد #<?php echo $contract_id; ?></a></td>
                                <td><span class="fw-semibold text-success"><?php echo number_format($amount); ?> تومان</span></td>
                                <td><?php echo get_the_date('Y/m/d', $payment->ID); ?></td>
                                <td><?php echo $status_badges[$status] ?? $status_badges['pending']; ?></td>
                                <td><i class="ri-wallet-3-line text-primary me-1"></i><?php echo esc_html($method); ?></td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="ri-inbox-line fs-40 d-block mb-2"></i>
                                    هیچ تراکنشی ثبت نشده است
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Revenue Monthly Chart
    const revenueMonthlyCtx = document.getElementById('revenueMonthlyChart');
    if (revenueMonthlyCtx) {
        new Chart(revenueMonthlyCtx, {
            type: 'bar',
            data: {
                labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'],
                datasets: [{
                    label: 'درآمد (میلیون تومان)',
                    data: [150, 220, 180, 290, 240, 310],
                    backgroundColor: 'rgba(132, 90, 223, 0.8)',
                    borderColor: '#845adf',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Payment Methods Chart
    const paymentMethodsCtx = document.getElementById('paymentMethodsChart');
    if (paymentMethodsCtx) {
        new Chart(paymentMethodsCtx, {
            type: 'bar',
            data: {
                labels: ['آنلاین', 'کارت به کارت', 'چک', 'نقدی', 'واریز'],
                datasets: [{
                    label: 'تعداد',
                    data: [45, 30, 15, 8, 22],
                    backgroundColor: [
                        'rgba(132, 90, 223, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Payment Status Chart
    const paymentStatusCtx = document.getElementById('paymentStatusChart');
    if (paymentStatusCtx) {
        new Chart(paymentStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['پرداخت شده', 'در انتظار', 'ناموفق'],
                datasets: [{
                    data: [<?php echo $total_paid; ?>, <?php echo $total_pending; ?>, 0],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Export to Excel
    $('#export-finance-excel').click(function() {
        const wb = XLSX.utils.book_new();
        const ws_data = [
            ['گزارش مالی - PuzzlingCRM'],
            [''],
            ['کل درآمد', '<?php echo number_format($total_revenue); ?>', 'تومان'],
            ['پرداخت شده', '<?php echo number_format($total_paid); ?>', 'تومان'],
            ['در انتظار', '<?php echo number_format($total_pending); ?>', 'تومان'],
            [''],
            ['شناسه', 'مشتری', 'مبلغ', 'تاریخ', 'وضعیت', 'روش پرداخت']
        ];
        
        <?php foreach ($recent_payments as $payment): 
            $status = get_post_meta($payment->ID, '_payment_status', true);
            $amount = get_post_meta($payment->ID, '_payment_amount', true);
            $method = get_post_meta($payment->ID, '_payment_method', true) ?: 'نامشخص';
            $customer_id = get_post_meta($payment->ID, '_customer_id', true);
            $customer = get_user_by('id', $customer_id);
        ?>
        ws_data.push([
            '<?php echo $payment->ID; ?>',
            '<?php echo $customer ? esc_js($customer->display_name) : '---'; ?>',
            '<?php echo number_format($amount); ?>',
            '<?php echo get_the_date('Y/m/d', $payment->ID); ?>',
            '<?php echo $status === 'paid' ? 'پرداخت شده' : ($status === 'pending' ? 'در انتظار' : 'ناموفق'); ?>',
            '<?php echo esc_js($method); ?>'
        ]);
        <?php endforeach; ?>
        
        const ws = XLSX.utils.aoa_to_sheet(ws_data);
        XLSX.utils.book_append_sheet(wb, ws, 'گزارش مالی');
        XLSX.writeFile(wb, 'financial-report.xlsx');
        
        Swal.fire({
            icon: 'success',
            title: 'موفق!',
            text: 'فایل اکسل دانلود شد',
            showConfirmButton: false,
            timer: 1500
        });
    });
});
</script>
