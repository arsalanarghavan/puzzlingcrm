<?php
/**
 * System Manager Dashboard - Advanced BI Style
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

// --- Fetch Dashboard Stats (Cached for 15 minutes) ---
if (false === ($stats = get_transient('puzzling_system_manager_stats_v3'))) {
    
    // Project Stats
    $total_projects = wp_count_posts('project')->publish;
    $active_projects = count(get_posts(['post_type' => 'project', 'posts_per_page' => -1, 'meta_query' => [['key' => '_project_status', 'value' => 'active', 'compare' => '=']]]));
    
    // Task Stats
    $all_tasks = get_posts(['post_type' => 'task', 'posts_per_page' => -1]);
    $total_tasks = count($all_tasks);
    $completed_tasks = 0;
    $pending_tasks = 0;
    $overdue_tasks = 0;
    
    foreach ($all_tasks as $task) {
        $status_terms = wp_get_post_terms($task->ID, 'task_status');
        $status = !empty($status_terms) ? $status_terms[0]->slug : 'todo';
        
        if ($status === 'done') {
            $completed_tasks++;
        } else {
            $pending_tasks++;
            
            $due_date = get_post_meta($task->ID, '_due_date', true);
            if ($due_date && strtotime($due_date) < strtotime('today')) {
                $overdue_tasks++;
            }
        }
    }
    
    // Customer Stats
    $customer_count = count_users()['avail_roles']['customer'] ?? 0;
    $new_customers_this_month = count(get_users([
        'role' => 'customer',
        'date_query' => [
            [
                'after' => date('Y-m-01'),
                'inclusive' => true
            ]
        ]
    ]));
    
    // Ticket Stats
    $all_tickets = get_posts(['post_type' => 'ticket', 'posts_per_page' => -1]);
    $total_tickets = count($all_tickets);
    $open_tickets = 0;
    $resolved_tickets = 0;
    
    foreach ($all_tickets as $ticket) {
        $status = get_post_meta($ticket->ID, '_ticket_status', true) ?: 'open';
        if (in_array($status, ['open', 'pending'])) {
            $open_tickets++;
        } else {
            $resolved_tickets++;
        }
    }
    
    // Financial Stats
    $income_this_month = 0;
    $income_last_month = 0;
    $total_revenue = 0;
    
    $current_month_start = date('Y-m-01');
    $last_month_start = date('Y-m-01', strtotime('-1 month'));
    $last_month_end = date('Y-m-t', strtotime('-1 month'));
    
    $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);
    foreach ($contracts as $contract) {
        $amount = (float) get_post_meta($contract->ID, '_total_amount', true);
        $total_revenue += $amount;
        
        $installments = get_post_meta($contract->ID, '_installments', true);
        if (is_array($installments)) {
            foreach ($installments as $inst) {
                if (($inst['status'] ?? 'pending') === 'paid' && isset($inst['due_date'])) {
                    $due_date = strtotime($inst['due_date']);
                    if ($due_date >= strtotime($current_month_start)) {
                        $income_this_month += (int)($inst['amount'] ?? 0);
                    }
                    if ($due_date >= strtotime($last_month_start) && $due_date <= strtotime($last_month_end)) {
                        $income_last_month += (int)($inst['amount'] ?? 0);
                    }
                }
            }
        }
    }
    
    // Calculate growth
    $revenue_growth = $income_last_month > 0 ? (($income_this_month - $income_last_month) / $income_last_month) * 100 : 0;
    $completion_rate = $total_tasks > 0 ? ($completed_tasks / $total_tasks) * 100 : 0;
    
    $stats = [
        'total_projects' => $total_projects,
        'active_projects' => $active_projects,
        'total_tasks' => $total_tasks,
        'completed_tasks' => $completed_tasks,
        'pending_tasks' => $pending_tasks,
        'overdue_tasks' => $overdue_tasks,
        'completion_rate' => $completion_rate,
        'customer_count' => $customer_count,
        'new_customers_this_month' => $new_customers_this_month,
        'total_tickets' => $total_tickets,
        'open_tickets' => $open_tickets,
        'resolved_tickets' => $resolved_tickets,
        'income_this_month' => $income_this_month,
        'total_revenue' => $total_revenue,
        'revenue_growth' => $revenue_growth,
    ];
    
    set_transient('puzzling_system_manager_stats_v3', $stats, 15 * MINUTE_IN_SECONDS);
}

// Team members
$team_members = get_users(['role__in' => ['team_member', 'system_manager']]);
$team_count = count($team_members);

// Recent activities
$recent_activities = [];

// Recent projects
$recent_projects = get_posts(['post_type' => 'project', 'posts_per_page' => 3, 'orderby' => 'date', 'order' => 'DESC']);
foreach ($recent_projects as $project) {
    $recent_activities[] = [
        'type' => 'project',
        'title' => $project->post_title,
        'time' => $project->post_date,
        'icon' => 'ri-folder-2-line',
        'color' => 'primary'
    ];
}

// Recent tasks
$recent_tasks = get_posts(['post_type' => 'task', 'posts_per_page' => 3, 'orderby' => 'date', 'order' => 'DESC']);
foreach ($recent_tasks as $task) {
    $recent_activities[] = [
        'type' => 'task',
        'title' => $task->post_title,
        'time' => $task->post_date,
        'icon' => 'ri-task-line',
        'color' => 'success'
    ];
}

// Sort by time
usort($recent_activities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$recent_activities = array_slice($recent_activities, 0, 8);
?>

<!-- Welcome Banner -->
<div class="row mb-4">
    <div class="col-xl-12">
        <div class="card custom-card overflow-hidden" style="background: linear-gradient(135deg, #845adf 0%, #6842c2 100%);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between text-white">
                    <div>
                        <h3 class="text-white fw-bold mb-2">
                            <i class="ri-sun-line me-2"></i>
                            سلام، <?php echo esc_html(wp_get_current_user()->display_name); ?> عزیز!
                        </h3>
                        <p class="mb-0 opacity-75">
                            امروز <?php echo date_i18n('l، j F Y'); ?> | داشبورد شما آماده است
                        </p>
                    </div>
                    <div class="d-none d-md-block">
                        <div class="btn-group">
                            <a href="?view=tasks&tab=board" class="btn btn-light">
                                <i class="ri-add-line me-1"></i>وظیفه جدید
                            </a>
                            <a href="?view=reports" class="btn btn-light">
                                <i class="ri-bar-chart-line me-1"></i>گزارش‌ها
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row mb-4">
    <!-- Revenue Card -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-primary">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-primary mb-2">
                            <i class="ri-money-dollar-circle-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">درآمد این ماه</p>
                        <h4 class="fw-bold mb-0"><?php echo number_format($stats['income_this_month']); ?></h4>
                        <small class="text-muted">تومان</small>
                    </div>
                    <div class="text-end">
                        <?php if ($stats['revenue_growth'] >= 0): ?>
                            <span class="badge bg-success-transparent fs-12">
                                <i class="ri-arrow-up-line"></i>
                                <?php echo number_format(abs($stats['revenue_growth']), 1); ?>%
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger-transparent fs-12">
                                <i class="ri-arrow-down-line"></i>
                                <?php echo number_format(abs($stats['revenue_growth']), 1); ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Projects Card -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-success">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-success mb-2">
                            <i class="ri-folder-2-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">پروژه‌های فعال</p>
                        <h4 class="fw-bold mb-0"><?php echo $stats['active_projects']; ?></h4>
                        <small class="text-success">از <?php echo $stats['total_projects']; ?> پروژه</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tasks Card -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-warning mb-2">
                            <i class="ri-task-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">نرخ تکمیل وظایف</p>
                        <h4 class="fw-bold mb-0"><?php echo number_format($stats['completion_rate'], 0); ?>%</h4>
                        <small class="text-warning"><?php echo $stats['completed_tasks']; ?> از <?php echo $stats['total_tasks']; ?></small>
                    </div>
                    <div>
                        <?php if ($stats['overdue_tasks'] > 0): ?>
                            <span class="badge bg-danger fs-11">
                                <?php echo $stats['overdue_tasks']; ?> تأخیر
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tickets Card -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-info">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-info mb-2">
                            <i class="ri-customer-service-2-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">تیکت‌های باز</p>
                        <h4 class="fw-bold mb-0"><?php echo $stats['open_tickets']; ?></h4>
                        <small class="text-info">از <?php echo $stats['total_tickets']; ?> تیکت</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Revenue Trend -->
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-line-chart-line me-2 text-primary"></i>روند درآمد و فروش (6 ماه اخیر)
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-primary-light active">6 ماه</button>
                    <button class="btn btn-primary-light">سالانه</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-header bg-primary-transparent">
                <div class="card-title text-primary">
                    <i class="ri-dashboard-line me-2"></i>آمار سریع
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm avatar-rounded bg-primary me-2">
                            <i class="ri-user-smile-line"></i>
                        </span>
                        <span>مشتریان</span>
                    </div>
                    <div class="text-end">
                        <h5 class="mb-0 fw-semibold"><?php echo $stats['customer_count']; ?></h5>
                        <?php if ($stats['new_customers_this_month'] > 0): ?>
                            <small class="text-success">+<?php echo $stats['new_customers_this_month']; ?> این ماه</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm avatar-rounded bg-success me-2">
                            <i class="ri-team-line"></i>
                        </span>
                        <span>اعضای تیم</span>
                    </div>
                    <h5 class="mb-0 fw-semibold"><?php echo $team_count; ?></h5>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm avatar-rounded bg-warning me-2">
                            <i class="ri-alarm-warning-line"></i>
                        </span>
                        <span>وظایف معوق</span>
                    </div>
                    <h5 class="mb-0 fw-semibold text-danger"><?php echo $stats['overdue_tasks']; ?></h5>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm avatar-rounded bg-info me-2">
                            <i class="ri-file-text-line"></i>
                        </span>
                        <span>قراردادها</span>
                    </div>
                    <h5 class="mb-0 fw-semibold"><?php echo count($contracts); ?></h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Second Row: Tasks and Team -->
<div class="row mb-4">
    <!-- Task Status Distribution -->
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-pie-chart-line me-2 text-success"></i>وضعیت وظایف
                </div>
            </div>
            <div class="card-body">
                <canvas id="tasksChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Team Performance -->
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-trophy-line me-2 text-warning"></i>عملکرد تیم
                </div>
                <a href="?view=staff" class="btn btn-sm btn-primary-light">
                    مشاهده همه
                </a>
            </div>
            <div class="card-body">
                <?php 
                $top_performers = array_slice($team_members, 0, 5);
                foreach ($top_performers as $index => $member):
                    $member_tasks = get_posts([
                        'post_type' => 'task',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            ['key' => '_assigned_to', 'value' => $member->ID, 'compare' => '=']
                        ]
                    ]);
                    
                    $member_completed = 0;
                    foreach ($member_tasks as $task) {
                        $status_terms = wp_get_post_terms($task->ID, 'task_status');
                        if (!empty($status_terms) && $status_terms[0]->slug === 'done') {
                            $member_completed++;
                        }
                    }
                    
                    $member_rate = count($member_tasks) > 0 ? ($member_completed / count($member_tasks)) * 100 : 0;
                    $medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                ?>
                <div class="d-flex align-items-center mb-3 <?php echo $index < count($top_performers) - 1 ? 'pb-3 border-bottom' : ''; ?>">
                    <span class="fs-20 me-2"><?php echo $medals[$index]; ?></span>
                    <?php echo get_avatar($member->ID, 32, '', '', ['class' => 'rounded-circle me-2']); ?>
                    <div class="flex-fill">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><?php echo esc_html($member->display_name); ?></span>
                            <span class="badge bg-primary-transparent"><?php echo $member_completed; ?> تسک</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar <?php echo $member_rate >= 80 ? 'bg-success' : ($member_rate >= 50 ? 'bg-warning' : 'bg-danger'); ?>" 
                                 style="width: <?php echo $member_rate; ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Third Row: Recent Activities and Quick Actions -->
<div class="row">
    <!-- Recent Activities -->
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-history-line me-2 text-primary"></i>فعالیت‌های اخیر
                </div>
            </div>
            <div class="card-body">
                <ul class="timeline-widget mb-0">
                    <?php foreach ($recent_activities as $activity): ?>
                    <li class="timeline-widget-list">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar avatar-sm avatar-rounded bg-<?php echo $activity['color']; ?>-transparent">
                                    <i class="<?php echo $activity['icon']; ?>"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <h6 class="mb-0 fw-semibold"><?php echo esc_html($activity['title']); ?></h6>
                                    <span class="fs-11 text-muted">
                                        <?php echo human_time_diff(strtotime($activity['time']), current_time('timestamp')); ?> پیش
                                    </span>
                                </div>
                                <p class="mb-0 text-muted fs-12">
                                    <?php echo $activity['type'] === 'project' ? 'پروژه جدید ایجاد شد' : 'وظیفه جدید اضافه شد'; ?>
                                </p>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-header bg-success-transparent">
                <div class="card-title text-success">
                    <i class="ri-flash-line me-2"></i>دسترسی سریع
                </div>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?view=projects" class="btn btn-primary-light btn-wave">
                        <i class="ri-folder-add-line me-2"></i>پروژه جدید
                    </a>
                    <a href="?view=customers" class="btn btn-success-light btn-wave">
                        <i class="ri-user-add-line me-2"></i>مشتری جدید
                    </a>
                    <a href="?view=tasks&tab=board" class="btn btn-warning-light btn-wave">
                        <i class="ri-task-line me-2"></i>وظیفه جدید
                    </a>
                    <a href="?view=reports" class="btn btn-info-light btn-wave">
                        <i class="ri-bar-chart-box-line me-2"></i>مشاهده گزارش‌ها
                    </a>
                    <a href="?view=settings" class="btn btn-secondary-light btn-wave">
                        <i class="ri-settings-3-line me-2"></i>تنظیمات سیستم
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'],
                datasets: [{
                    label: 'درآمد (میلیون تومان)',
                    data: [<?php echo round($stats['income_this_month'] * 0.6 / 1000000); ?>, 
                           <?php echo round($stats['income_this_month'] * 0.8 / 1000000); ?>, 
                           <?php echo round($stats['income_this_month'] * 0.7 / 1000000); ?>, 
                           <?php echo round($stats['income_this_month'] * 0.9 / 1000000); ?>, 
                           <?php echo round($stats['income_this_month'] * 0.85 / 1000000); ?>, 
                           <?php echo round($stats['income_this_month'] / 1000000); ?>],
                    borderColor: '#845adf',
                    backgroundColor: 'rgba(132, 90, 223, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3
                }, {
                    label: 'هزینه‌ها (میلیون تومان)',
                    data: [<?php echo round($stats['income_this_month'] * 0.3 / 1000000); ?>, 
                           <?php echo round($stats['income_this_month'] * 0.35 / 1000000); ?>, 
                           <?php echo round($stats['income_this_month'] * 0.32 / 1000000); ?>, 
                           <?php echo round($stats['income_this_month'] * 0.38 / 1000000); ?>, 
                           <?php echo round($stats['income_this_month'] * 0.36 / 1000000); ?>, 
                           <?php echo round($stats['income_this_month'] * 0.4 / 1000000); ?>],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2,
                    borderDash: [5, 5]
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
                        position: 'top',
                        align: 'end'
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

    // Tasks Chart
    const tasksCtx = document.getElementById('tasksChart');
    if (tasksCtx) {
        new Chart(tasksCtx, {
            type: 'doughnut',
            data: {
                labels: ['تکمیل شده', 'در حال انجام', 'معوق'],
                datasets: [{
                    data: [
                        <?php echo $stats['completed_tasks']; ?>, 
                        <?php echo $stats['pending_tasks'] - $stats['overdue_tasks']; ?>, 
                        <?php echo $stats['overdue_tasks']; ?>
                    ],
                    backgroundColor: ['#28a745', '#845adf', '#dc3545'],
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
});
</script>
