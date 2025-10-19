<?php
/**
 * Reports Overview Dashboard
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

// Calculate comprehensive stats
$total_projects = wp_count_posts('project')->publish;
$total_tasks = wp_count_posts('task')->publish;
$total_tickets = wp_count_posts('ticket')->publish;
$total_leads = wp_count_posts('pzl_lead')->publish;
$total_customers = count_users()['avail_roles']['customer'] ?? 0;
$total_contracts = wp_count_posts('contract')->publish;
?>

<div class="row">
    <!-- Left Column - Charts -->
    <div class="col-xl-8">
        <!-- Revenue Trend -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-line-chart-line me-2 text-primary"></i>روند درآمد (6 ماه اخیر)
                </div>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>

        <!-- Projects vs Tasks -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-pie-chart-line me-2 text-success"></i>وضعیت پروژه‌ها
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="projectsChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-donut-chart-line me-2 text-warning"></i>وضعیت وظایف
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="tasksChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column - Quick Stats -->
    <div class="col-xl-4">
        <div class="card custom-card mb-3">
            <div class="card-header bg-primary-transparent">
                <div class="card-title text-primary">
                    <i class="ri-dashboard-line me-2"></i>آمار سریع
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm avatar-rounded bg-primary me-2">
                            <i class="ri-folder-2-line"></i>
                        </span>
                        <span>پروژه‌ها</span>
                    </div>
                    <h5 class="mb-0 fw-semibold"><?php echo $total_projects; ?></h5>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm avatar-rounded bg-success me-2">
                            <i class="ri-task-line"></i>
                        </span>
                        <span>وظایف</span>
                    </div>
                    <h5 class="mb-0 fw-semibold"><?php echo $total_tasks; ?></h5>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm avatar-rounded bg-warning me-2">
                            <i class="ri-customer-service-2-line"></i>
                        </span>
                        <span>تیکت‌ها</span>
                    </div>
                    <h5 class="mb-0 fw-semibold"><?php echo $total_tickets; ?></h5>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm avatar-rounded bg-info me-2">
                            <i class="ri-user-add-line"></i>
                        </span>
                        <span>سرنخ‌ها</span>
                    </div>
                    <h5 class="mb-0 fw-semibold"><?php echo $total_leads; ?></h5>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm avatar-rounded bg-secondary me-2">
                            <i class="ri-user-smile-line"></i>
                        </span>
                        <span>مشتریان</span>
                    </div>
                    <h5 class="mb-0 fw-semibold"><?php echo $total_customers; ?></h5>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm avatar-rounded bg-danger me-2">
                            <i class="ri-file-text-line"></i>
                        </span>
                        <span>قراردادها</span>
                    </div>
                    <h5 class="mb-0 fw-semibold"><?php echo $total_contracts; ?></h5>
                </div>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="card custom-card">
            <div class="card-header bg-success-transparent">
                <div class="card-title text-success">
                    <i class="ri-trophy-line me-2"></i>کارمندان برتر این ماه
                </div>
            </div>
            <div class="card-body">
                <?php
                $top_users = get_users([
                    'role__in' => ['team_member', 'system_manager'],
                    'number' => 5,
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC'
                ]);
                
                if ($top_users):
                    foreach ($top_users as $index => $user):
                        $medals = ['🥇', '🥈', '🥉', '4', '5'];
                ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-primary-transparent me-2" style="min-width: 30px;">
                        <?php echo $medals[$index]; ?>
                    </span>
                    <?php echo get_avatar($user->ID, 32, '', '', ['class' => 'rounded-circle me-2']); ?>
                    <div class="flex-fill">
                        <div class="fw-semibold"><?php echo esc_html($user->display_name); ?></div>
                        <small class="text-muted">12 وظیفه تکمیل شده</small>
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
</div>

<script>
// Revenue Trend Chart
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'],
            datasets: [{
                label: 'درآمد (میلیون تومان)',
                data: [120, 190, 150, 250, 200, 280],
                borderColor: '#845adf',
                backgroundColor: 'rgba(132, 90, 223, 0.1)',
                tension: 0.4,
                fill: true
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

// Projects Status Chart
const projectsCtx = document.getElementById('projectsChart');
if (projectsCtx) {
    new Chart(projectsCtx, {
        type: 'doughnut',
        data: {
            labels: ['فعال', 'تکمیل شده', 'در انتظار', 'لغو شده'],
            datasets: [{
                data: [45, 30, 15, 10],
                backgroundColor: ['#28a745', '#845adf', '#ffc107', '#dc3545'],
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

// Tasks Status Chart
const tasksCtx = document.getElementById('tasksChart');
if (tasksCtx) {
    new Chart(tasksCtx, {
        type: 'polarArea',
        data: {
            labels: ['انجام نشده', 'در حال انجام', 'بررسی', 'تکمیل'],
            datasets: [{
                data: [25, 40, 15, 20],
                backgroundColor: [
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(132, 90, 223, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(40, 167, 69, 0.7)'
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
</script>


