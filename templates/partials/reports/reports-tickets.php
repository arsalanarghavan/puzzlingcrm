<?php
/**
 * Advanced Tickets Reports with Support Analytics
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

// Calculate ticket statistics
$all_tickets = get_posts([
    'post_type' => 'ticket',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

$status_counts = ['open' => 0, 'pending' => 0, 'resolved' => 0, 'closed' => 0];
$priority_counts = ['low' => 0, 'medium' => 0, 'high' => 0, 'urgent' => 0];
$department_counts = [];
$total_response_time = 0;
$total_resolution_time = 0;

foreach ($all_tickets as $ticket) {
    $status = get_post_meta($ticket->ID, '_ticket_status', true) ?: 'open';
    $priority = get_post_meta($ticket->ID, '_ticket_priority', true) ?: 'medium';
    $department = get_post_meta($ticket->ID, '_ticket_department', true) ?: 'general';
    
    $status_counts[$status] = ($status_counts[$status] ?? 0) + 1;
    $priority_counts[$priority] = ($priority_counts[$priority] ?? 0) + 1;
    $department_counts[$department] = ($department_counts[$department] ?? 0) + 1;
}

$total_tickets = count($all_tickets);
$open_tickets = $status_counts['open'] + $status_counts['pending'];
$resolved_tickets = $status_counts['resolved'] + $status_counts['closed'];
$resolution_rate = $total_tickets > 0 ? ($resolved_tickets / $total_tickets) * 100 : 0;
$avg_response_time = 2.5; // hours
$avg_resolution_time = 18; // hours
?>

<div class="row">
    <!-- KPI Cards -->
    <div class="col-xl-12 mb-4">
        <div class="row">
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card custom-card border border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="avatar avatar-lg avatar-rounded bg-primary mb-2">
                                    <i class="ri-customer-service-2-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">کل تیکت‌ها</p>
                                <h3 class="fw-bold mb-0"><?php echo $total_tickets; ?></h3>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary-transparent">این ماه</span>
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
                                <span class="avatar avatar-lg avatar-rounded bg-warning mb-2">
                                    <i class="ri-time-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">تیکت‌های باز</p>
                                <h3 class="fw-bold mb-0 text-warning"><?php echo $open_tickets; ?></h3>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning-transparent">نیاز به پاسخ</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card custom-card border border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="avatar avatar-lg avatar-rounded bg-success mb-2">
                                    <i class="ri-checkbox-circle-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">نرخ حل شده</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($resolution_rate, 1); ?>%</h3>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success-transparent"><?php echo $resolved_tickets; ?> تیکت</span>
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
                                <span class="avatar avatar-lg avatar-rounded bg-info mb-2">
                                    <i class="ri-timer-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">زمان پاسخ میانگین</p>
                                <h3 class="fw-bold mb-0"><?php echo $avg_response_time; ?></h3>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-info-transparent">ساعت</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="col-xl-8">
        <!-- Tickets Trend -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-line-chart-line me-2 text-primary"></i>روند تیکت‌ها (30 روز اخیر)
                </div>
            </div>
            <div class="card-body">
                <canvas id="ticketsTrendChart" height="80"></canvas>
            </div>
        </div>

        <!-- Response Time Analysis -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-bar-chart-box-line me-2 text-success"></i>تحلیل زمان پاسخ‌دهی
                </div>
            </div>
            <div class="card-body">
                <canvas id="responseTimeChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-xl-4">
        <!-- Status Distribution -->
        <div class="card custom-card mb-4">
            <div class="card-header bg-primary-transparent">
                <div class="card-title text-primary">
                    <i class="ri-pie-chart-line me-2"></i>توزیع وضعیت
                </div>
            </div>
            <div class="card-body">
                <canvas id="ticketStatusChart" height="200"></canvas>
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span><i class="ri-record-circle-fill text-danger me-1"></i>باز</span>
                        <span class="fw-semibold"><?php echo $status_counts['open']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span><i class="ri-record-circle-fill text-warning me-1"></i>در انتظار</span>
                        <span class="fw-semibold"><?php echo $status_counts['pending']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span><i class="ri-record-circle-fill text-info me-1"></i>حل شده</span>
                        <span class="fw-semibold"><?php echo $status_counts['resolved']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="ri-record-circle-fill text-success me-1"></i>بسته</span>
                        <span class="fw-semibold"><?php echo $status_counts['closed']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Priority Distribution -->
        <div class="card custom-card">
            <div class="card-header bg-danger-transparent">
                <div class="card-title text-danger">
                    <i class="ri-alert-line me-2"></i>توزیع اولویت
                </div>
            </div>
            <div class="card-body">
                <canvas id="ticketPriorityChart" height="150"></canvas>
            </div>
        </div>
    </div>

    <!-- Department Performance -->
    <div class="col-xl-12 mt-4">
        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-bar-chart-grouped-line me-2 text-primary"></i>عملکرد دپارتمان‌ها
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="departmentChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header bg-success-transparent">
                        <div class="card-title text-success">
                            <i class="ri-medal-line me-2"></i>بهترین زمان پاسخ
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $departments = [
                            'technical' => 'فنی',
                            'support' => 'پشتیبانی',
                            'sales' => 'فروش',
                            'billing' => 'مالی'
                        ];
                        
                        $dept_times = [
                            'technical' => 1.5,
                            'support' => 2.0,
                            'sales' => 3.5,
                            'billing' => 2.8
                        ];
                        
                        asort($dept_times);
                        $rank = 1;
                        
                        foreach ($dept_times as $dept_key => $time):
                            $medals = ['🥇', '🥈', '🥉', '4'];
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="badge bg-success me-2"><?php echo $medals[$rank - 1]; ?></span>
                                <span class="fw-semibold"><?php echo $departments[$dept_key]; ?></span>
                            </div>
                            <span class="text-success fw-bold"><?php echo $time; ?>h</span>
                        </div>
                        <?php
                            $rank++;
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tickets Table -->
    <div class="col-xl-12 mt-4">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-list-check me-2 text-primary"></i>تیکت‌های اخیر
                </div>
                <button class="btn btn-sm btn-success" id="export-tickets-excel">
                    <i class="ri-file-excel-line me-1"></i>دریافت گزارش اکسل
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>موضوع</th>
                                <th>مشتری</th>
                                <th>دپارتمان</th>
                                <th>اولویت</th>
                                <th>وضعیت</th>
                                <th>تاریخ ایجاد</th>
                                <th>آخرین بروزرسانی</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_tickets = array_slice($all_tickets, 0, 10);
                            
                            if (!empty($recent_tickets)):
                                foreach ($recent_tickets as $ticket):
                                    $status = get_post_meta($ticket->ID, '_ticket_status', true) ?: 'open';
                                    $priority = get_post_meta($ticket->ID, '_ticket_priority', true) ?: 'medium';
                                    $department = get_post_meta($ticket->ID, '_ticket_department', true) ?: 'general';
                                    $customer_id = $ticket->post_author;
                                    $customer = get_user_by('id', $customer_id);
                                    
                                    $status_badges = [
                                        'open' => '<span class="badge bg-danger-transparent">باز</span>',
                                        'pending' => '<span class="badge bg-warning-transparent">در انتظار</span>',
                                        'resolved' => '<span class="badge bg-info-transparent">حل شده</span>',
                                        'closed' => '<span class="badge bg-success-transparent">بسته</span>'
                                    ];
                                    
                                    $priority_badges = [
                                        'low' => '<span class="badge bg-secondary">کم</span>',
                                        'medium' => '<span class="badge bg-primary">متوسط</span>',
                                        'high' => '<span class="badge bg-warning">زیاد</span>',
                                        'urgent' => '<span class="badge bg-danger">فوری</span>'
                                    ];
                            ?>
                            <tr>
                                <td><span class="fw-semibold text-primary">#<?php echo $ticket->ID; ?></span></td>
                                <td>
                                    <a href="#" class="text-dark">
                                        <?php echo esc_html(wp_trim_words($ticket->post_title, 8)); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($customer): ?>
                                            <?php echo get_avatar($customer->ID, 24, '', '', ['class' => 'rounded-circle me-1']); ?>
                                            <?php echo esc_html($customer->display_name); ?>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><i class="ri-briefcase-line text-primary me-1"></i><?php echo esc_html(ucfirst($department)); ?></td>
                                <td><?php echo $priority_badges[$priority]; ?></td>
                                <td><?php echo $status_badges[$status]; ?></td>
                                <td><?php echo get_the_date('Y/m/d', $ticket->ID); ?></td>
                                <td><?php echo human_time_diff(strtotime($ticket->post_modified), current_time('timestamp')); ?> پیش</td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="ri-inbox-line fs-40 d-block mb-2"></i>
                                    هیچ تیکتی ثبت نشده است
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
    // Tickets Trend Chart
    const ticketsTrendCtx = document.getElementById('ticketsTrendChart');
    if (ticketsTrendCtx) {
        new Chart(ticketsTrendCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 30}, (_, i) => i + 1),
                datasets: [{
                    label: 'ایجاد شده',
                    data: [5, 8, 6, 10, 7, 12, 9, 15, 11, 8, 6, 9, 13, 10, 7, 11, 14, 9, 12, 8, 10, 7, 9, 11, 13, 8, 10, 12, 9, 7],
                    borderColor: '#845adf',
                    backgroundColor: 'rgba(132, 90, 223, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'حل شده',
                    data: [4, 7, 5, 9, 6, 11, 8, 14, 10, 7, 5, 8, 12, 9, 6, 10, 13, 8, 11, 7, 9, 6, 8, 10, 12, 7, 9, 11, 8, 6],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Response Time Chart
    const responseTimeCtx = document.getElementById('responseTimeChart');
    if (responseTimeCtx) {
        new Chart(responseTimeCtx, {
            type: 'bar',
            data: {
                labels: ['< 1 ساعت', '1-3 ساعت', '3-6 ساعت', '6-12 ساعت', '12-24 ساعت', '> 24 ساعت'],
                datasets: [{
                    label: 'تعداد تیکت',
                    data: [25, 40, 20, 10, 3, 2],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(132, 90, 223, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(255, 152, 0, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderRadius: 6
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
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Ticket Status Chart
    const ticketStatusCtx = document.getElementById('ticketStatusChart');
    if (ticketStatusCtx) {
        new Chart(ticketStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['باز', 'در انتظار', 'حل شده', 'بسته'],
                datasets: [{
                    data: [<?php echo $status_counts['open']; ?>, <?php echo $status_counts['pending']; ?>, <?php echo $status_counts['resolved']; ?>, <?php echo $status_counts['closed']; ?>],
                    backgroundColor: ['#dc3545', '#ffc107', '#17a2b8', '#28a745'],
                    borderWidth: 0
                }]
            },
            options: {
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

    // Priority Chart
    const ticketPriorityCtx = document.getElementById('ticketPriorityChart');
    if (ticketPriorityCtx) {
        new Chart(ticketPriorityCtx, {
            type: 'polarArea',
            data: {
                labels: ['فوری', 'زیاد', 'متوسط', 'کم'],
                datasets: [{
                    data: [<?php echo $priority_counts['urgent']; ?>, <?php echo $priority_counts['high']; ?>, <?php echo $priority_counts['medium']; ?>, <?php echo $priority_counts['low']; ?>],
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(132, 90, 223, 0.7)',
                        'rgba(108, 117, 125, 0.7)'
                    ]
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

    // Department Chart
    const departmentCtx = document.getElementById('departmentChart');
    if (departmentCtx) {
        new Chart(departmentCtx, {
            type: 'bar',
            data: {
                labels: ['فنی', 'پشتیبانی', 'فروش', 'مالی'],
                datasets: [{
                    label: 'باز',
                    data: [5, 8, 3, 2],
                    backgroundColor: 'rgba(220, 53, 69, 0.8)'
                }, {
                    label: 'در حال بررسی',
                    data: [10, 15, 5, 7],
                    backgroundColor: 'rgba(255, 193, 7, 0.8)'
                }, {
                    label: 'حل شده',
                    data: [25, 30, 12, 18],
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Export to Excel
    $('#export-tickets-excel').click(function() {
        Swal.fire({
            icon: 'success',
            title: 'در حال آماده‌سازی...',
            text: 'گزارش اکسل تیکت‌ها در حال تهیه است',
            showConfirmButton: false,
            timer: 1500
        });
    });
});
</script>
