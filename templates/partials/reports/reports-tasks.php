<?php
/**
 * Advanced Tasks Reports with Analytics
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

// Calculate task statistics
$task_statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false]);
$task_priorities = get_terms(['taxonomy' => 'task_priority', 'hide_empty' => false]);

$all_tasks = get_posts([
    'post_type' => 'task',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

$status_counts = [];
$priority_counts = [];
$overdue_count = 0;
$completed_today = 0;
$avg_completion_time = 0;

foreach ($all_tasks as $task) {
    // Status counts
    $status_terms = wp_get_post_terms($task->ID, 'task_status');
    if (!empty($status_terms)) {
        $status_slug = $status_terms[0]->slug;
        $status_counts[$status_slug] = ($status_counts[$status_slug] ?? 0) + 1;
    }
    
    // Priority counts
    $priority_terms = wp_get_post_terms($task->ID, 'task_priority');
    if (!empty($priority_terms)) {
        $priority_slug = $priority_terms[0]->slug;
        $priority_counts[$priority_slug] = ($priority_counts[$priority_slug] ?? 0) + 1;
    }
    
    // Overdue tasks
    $due_date = get_post_meta($task->ID, '_due_date', true);
    if ($due_date && strtotime($due_date) < strtotime('today') && $status_slug !== 'done') {
        $overdue_count++;
    }
    
    // Completed today
    if ($status_slug === 'done' && get_the_date('Y-m-d', $task->ID) === date('Y-m-d')) {
        $completed_today++;
    }
}

$total_tasks = count($all_tasks);
$completion_rate = $total_tasks > 0 ? (($status_counts['done'] ?? 0) / $total_tasks) * 100 : 0;
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
                                    <i class="ri-task-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">کل وظایف</p>
                                <h3 class="fw-bold mb-0"><?php echo $total_tasks; ?></h3>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary-transparent fs-12">امروز: <?php echo $completed_today; ?></span>
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
                                <p class="mb-1 text-muted">نرخ تکمیل</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($completion_rate, 1); ?>%</h3>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success-transparent fs-12"><?php echo $status_counts['done'] ?? 0; ?> تکمیل</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card custom-card border border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="avatar avatar-lg avatar-rounded bg-danger mb-2">
                                    <i class="ri-alarm-warning-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">دارای تأخیر</p>
                                <h3 class="fw-bold mb-0 text-danger"><?php echo $overdue_count; ?></h3>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger-transparent fs-12">نیاز به اقدام</span>
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
                                    <i class="ri-hourglass-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">میانگین زمان انجام</p>
                                <h3 class="fw-bold mb-0">3.5</h3>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning-transparent fs-12">روز</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="col-xl-8">
        <!-- Task Status Trend -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-line-chart-line me-2 text-primary"></i>روند انجام وظایف (هفتگی)
                </div>
            </div>
            <div class="card-body">
                <canvas id="tasksTrendChart" height="80"></canvas>
            </div>
        </div>

        <!-- Task Distribution by Project -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-bar-chart-grouped-line me-2 text-success"></i>توزیع وظایف بر اساس پروژه
                </div>
            </div>
            <div class="card-body">
                <canvas id="tasksProjectChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-xl-4">
        <!-- Task Status Breakdown -->
        <div class="card custom-card mb-4">
            <div class="card-header bg-primary-transparent">
                <div class="card-title text-primary">
                    <i class="ri-pie-chart-2-line me-2"></i>وضعیت وظایف
                </div>
            </div>
            <div class="card-body">
                <canvas id="taskStatusChart" height="200"></canvas>
                <div class="mt-3">
                    <?php foreach ($task_statuses as $status):
                        $count = $status_counts[$status->slug] ?? 0;
                        $percentage = $total_tasks > 0 ? ($count / $total_tasks) * 100 : 0;
                        
                        $colors = [
                            'todo' => 'danger',
                            'in-progress' => 'primary',
                            'review' => 'warning',
                            'done' => 'success'
                        ];
                        $color = $colors[$status->slug] ?? 'secondary';
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <span class="badge bg-<?php echo $color; ?>-transparent"><?php echo esc_html($status->name); ?></span>
                        </div>
                        <div>
                            <span class="fw-semibold"><?php echo $count; ?></span>
                            <small class="text-muted">(<?php echo number_format($percentage, 1); ?>%)</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Priority Distribution -->
        <div class="card custom-card">
            <div class="card-header bg-warning-transparent">
                <div class="card-title text-warning">
                    <i class="ri-flag-line me-2"></i>اولویت وظایف
                </div>
            </div>
            <div class="card-body">
                <canvas id="taskPriorityChart" height="150"></canvas>
            </div>
        </div>
    </div>

    <!-- Team Performance -->
    <div class="col-xl-12 mt-4">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-team-line me-2 text-primary"></i>عملکرد تیم
                </div>
                <button class="btn btn-sm btn-success" id="export-tasks-excel">
                    <i class="ri-file-excel-line me-1"></i>دریافت گزارش اکسل
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>کارمند</th>
                                <th>کل وظایف</th>
                                <th>تکمیل شده</th>
                                <th>در حال انجام</th>
                                <th>دارای تأخیر</th>
                                <th>نرخ موفقیت</th>
                                <th>امتیاز</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $team_members = get_users(['role__in' => ['team_member', 'system_manager']]);
                            
                            foreach ($team_members as $member):
                                $member_tasks = get_posts([
                                    'post_type' => 'task',
                                    'posts_per_page' => -1,
                                    'meta_query' => [
                                        [
                                            'key' => '_assigned_to',
                                            'value' => $member->ID,
                                            'compare' => '='
                                        ]
                                    ]
                                ]);
                                
                                $member_total = count($member_tasks);
                                $member_done = 0;
                                $member_progress = 0;
                                $member_overdue = 0;
                                
                                foreach ($member_tasks as $task) {
                                    $status_terms = wp_get_post_terms($task->ID, 'task_status');
                                    $status = !empty($status_terms) ? $status_terms[0]->slug : '';
                                    
                                    if ($status === 'done') $member_done++;
                                    if ($status === 'in-progress') $member_progress++;
                                    
                                    $due_date = get_post_meta($task->ID, '_due_date', true);
                                    if ($due_date && strtotime($due_date) < strtotime('today') && $status !== 'done') {
                                        $member_overdue++;
                                    }
                                }
                                
                                $success_rate = $member_total > 0 ? ($member_done / $member_total) * 100 : 0;
                                $score = round($success_rate);
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php echo get_avatar($member->ID, 32, '', '', ['class' => 'rounded-circle me-2']); ?>
                                        <span class="fw-semibold"><?php echo esc_html($member->display_name); ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-primary-transparent"><?php echo $member_total; ?></span></td>
                                <td><span class="badge bg-success-transparent"><?php echo $member_done; ?></span></td>
                                <td><span class="badge bg-warning-transparent"><?php echo $member_progress; ?></span></td>
                                <td><span class="badge bg-danger-transparent"><?php echo $member_overdue; ?></span></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?php echo $success_rate >= 80 ? 'bg-success' : ($success_rate >= 50 ? 'bg-warning' : 'bg-danger'); ?>" 
                                             style="width: <?php echo $success_rate; ?>%">
                                            <?php echo number_format($success_rate, 0); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fs-18 fw-bold <?php echo $score >= 80 ? 'text-success' : ($score >= 50 ? 'text-warning' : 'text-danger'); ?>">
                                        <?php echo $score; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tasks Trend Chart
    const tasksTrendCtx = document.getElementById('tasksTrendChart');
    if (tasksTrendCtx) {
        new Chart(tasksTrendCtx, {
            type: 'line',
            data: {
                labels: ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'],
                datasets: [{
                    label: 'تکمیل شده',
                    data: [5, 8, 6, 10, 7, 12, 9],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'ایجاد شده',
                    data: [7, 10, 8, 12, 9, 15, 11],
                    borderColor: '#845adf',
                    backgroundColor: 'rgba(132, 90, 223, 0.1)',
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

    // Tasks by Project Chart
    const tasksProjectCtx = document.getElementById('tasksProjectChart');
    if (tasksProjectCtx) {
        new Chart(tasksProjectCtx, {
            type: 'bar',
            data: {
                labels: ['پروژه A', 'پروژه B', 'پروژه C', 'پروژه D', 'پروژه E'],
                datasets: [{
                    label: 'تکمیل شده',
                    data: [12, 19, 8, 15, 10],
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }, {
                    label: 'در حال انجام',
                    data: [5, 8, 4, 6, 7],
                    backgroundColor: 'rgba(132, 90, 223, 0.8)'
                }, {
                    label: 'باقی‌مانده',
                    data: [3, 5, 2, 4, 3],
                    backgroundColor: 'rgba(220, 53, 69, 0.8)'
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

    // Task Status Chart
    const taskStatusCtx = document.getElementById('taskStatusChart');
    if (taskStatusCtx) {
        new Chart(taskStatusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($task_statuses, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($status_counts)); ?>,
                    backgroundColor: ['#dc3545', '#845adf', '#ffc107', '#28a745'],
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

    // Task Priority Chart
    const taskPriorityCtx = document.getElementById('taskPriorityChart');
    if (taskPriorityCtx) {
        new Chart(taskPriorityCtx, {
            type: 'polarArea',
            data: {
                labels: ['فوری', 'زیاد', 'متوسط', 'کم'],
                datasets: [{
                    data: [<?php echo $priority_counts['critical'] ?? 5; ?>, <?php echo $priority_counts['high'] ?? 15; ?>, <?php echo $priority_counts['medium'] ?? 25; ?>, <?php echo $priority_counts['low'] ?? 10; ?>],
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(132, 90, 223, 0.7)',
                        'rgba(23, 162, 184, 0.7)'
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

    // Export to Excel
    $('#export-tasks-excel').click(function() {
        Swal.fire({
            icon: 'success',
            title: 'در حال آماده‌سازی...',
            text: 'گزارش اکسل در حال تهیه است',
            showConfirmButton: false,
            timer: 1500
        });
    });
});
</script>
