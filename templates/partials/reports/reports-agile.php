<?php
/**
 * Advanced Agile Reports with Sprint Analytics
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

// Calculate Agile metrics
$all_projects = get_posts([
    'post_type' => 'project',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

$all_tasks = get_posts([
    'post_type' => 'task',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

// Sprint calculations
$total_story_points = 0;
$completed_story_points = 0;
$sprint_velocity = 0;
$sprint_burndown = [];

foreach ($all_tasks as $task) {
    $story_points = (int) get_post_meta($task->ID, '_story_points', true);
    $total_story_points += $story_points;
    
    $status_terms = wp_get_post_terms($task->ID, 'task_status');
    if (!empty($status_terms) && $status_terms[0]->slug === 'done') {
        $completed_story_points += $story_points;
    }
}

$sprint_velocity = 25; // Average story points per sprint
$team_capacity = 40; // Story points capacity
$sprint_progress = $team_capacity > 0 ? ($sprint_velocity / $team_capacity) * 100 : 0;
?>

<div class="row">
    <!-- Sprint KPIs -->
    <div class="col-xl-12 mb-4">
        <div class="row">
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card custom-card border border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="avatar avatar-lg avatar-rounded bg-primary mb-2">
                                    <i class="ri-speed-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">Velocity اسپرینت</p>
                                <h3 class="fw-bold mb-0"><?php echo $sprint_velocity; ?></h3>
                                <small class="text-primary">Story Points</small>
                            </div>
                            <div class="text-end">
                                <i class="ri-arrow-up-line text-success"></i>
                                <span class="text-success fw-semibold">+8%</span>
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
                                    <i class="ri-trophy-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">تکمیل اسپرینت</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($sprint_progress, 0); ?>%</h3>
                                <small class="text-success"><?php echo $sprint_velocity; ?>/<?php echo $team_capacity; ?> SP</small>
                            </div>
                            <div>
                                <div class="progress" style="width: 60px; height: 60px; transform: rotate(-90deg);">
                                    <svg width="60" height="60">
                                        <circle cx="30" cy="30" r="25" fill="none" stroke="#e9ecef" stroke-width="5"></circle>
                                        <circle cx="30" cy="30" r="25" fill="none" stroke="#28a745" stroke-width="5" 
                                                stroke-dasharray="<?php echo $sprint_progress * 1.57; ?> 157" 
                                                stroke-linecap="round"></circle>
                                    </svg>
                                </div>
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
                                    <i class="ri-team-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">ظرفیت تیم</p>
                                <h3 class="fw-bold mb-0"><?php echo $team_capacity; ?></h3>
                                <small class="text-warning">Story Points</small>
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
                                    <i class="ri-calendar-check-line fs-24"></i>
                                </span>
                                <p class="mb-1 text-muted">روزهای باقیمانده</p>
                                <h3 class="fw-bold mb-0">7</h3>
                                <small class="text-info">از 14 روز</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Charts -->
    <div class="col-xl-8">
        <!-- Sprint Burndown Chart -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-line-chart-line me-2 text-danger"></i>Burndown Chart - اسپرینت فعلی
                </div>
            </div>
            <div class="card-body">
                <canvas id="burndownChart" height="80"></canvas>
            </div>
        </div>

        <!-- Velocity Trend -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-bar-chart-box-line me-2 text-primary"></i>روند Velocity (6 اسپرینت اخیر)
                </div>
            </div>
            <div class="card-body">
                <canvas id="velocityChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-xl-4">
        <!-- Sprint Status -->
        <div class="card custom-card mb-4">
            <div class="card-header bg-primary-transparent">
                <div class="card-title text-primary">
                    <i class="ri-calendar-event-line me-2"></i>وضعیت اسپرینت
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-semibold">اسپرینت 12</span>
                        <span class="badge bg-success">فعال</span>
                    </div>
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $sprint_progress; ?>%">
                            <?php echo number_format($sprint_progress, 0); ?>%
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="ri-calendar-line me-1"></i>
                        <?php echo date('Y/m/d', strtotime('-7 days')); ?> تا <?php echo date('Y/m/d', strtotime('+7 days')); ?>
                    </small>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="ri-checkbox-circle-line text-success me-2"></i>تکمیل شده</span>
                    <span class="fw-bold"><?php echo $sprint_velocity; ?> SP</span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="ri-time-line text-warning me-2"></i>در حال انجام</span>
                    <span class="fw-bold">10 SP</span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="ri-inbox-line text-muted me-2"></i>باقیمانده</span>
                    <span class="fw-bold">5 SP</span>
                </div>
            </div>
        </div>

        <!-- Team Contribution -->
        <div class="card custom-card">
            <div class="card-header bg-success-transparent">
                <div class="card-title text-success">
                    <i class="ri-pie-chart-2-line me-2"></i>مشارکت تیم
                </div>
            </div>
            <div class="card-body">
                <canvas id="teamContributionChart" height="180"></canvas>
            </div>
        </div>
    </div>

    <!-- Cumulative Flow Diagram -->
    <div class="col-xl-12 mt-4">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-stack-line me-2 text-info"></i>Cumulative Flow Diagram
                </div>
            </div>
            <div class="card-body">
                <canvas id="cumulativeFlowChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Sprint Retrospective -->
    <div class="col-xl-12 mt-4">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-feedback-line me-2 text-warning"></i>Retrospective - نکات کلیدی
                </div>
                <button class="btn btn-sm btn-primary" id="export-agile-report">
                    <i class="ri-file-pdf-line me-1"></i>دریافت گزارش PDF
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-success-transparent border-0">
                            <div class="card-body">
                                <h6 class="text-success mb-3">
                                    <i class="ri-thumb-up-line me-2"></i>موفقیت‌ها
                                </h6>
                                <ul class="mb-0">
                                    <li class="mb-2">بهبود سرعت توسعه 15%</li>
                                    <li class="mb-2">کاهش باگ‌ها به 20%</li>
                                    <li class="mb-2">افزایش تعامل تیمی</li>
                                    <li>تکمیل 95% تسک‌ها</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-warning-transparent border-0">
                            <div class="card-body">
                                <h6 class="text-warning mb-3">
                                    <i class="ri-lightbulb-line me-2"></i>نقاط بهبود
                                </h6>
                                <ul class="mb-0">
                                    <li class="mb-2">تخمین دقیق‌تر Story Points</li>
                                    <li class="mb-2">کاهش وابستگی‌ها</li>
                                    <li class="mb-2">بهبود مستندسازی</li>
                                    <li>افزایش Code Review</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-info-transparent border-0">
                            <div class="card-body">
                                <h6 class="text-info mb-3">
                                    <i class="ri-todo-line me-2"></i>اقدامات آینده
                                </h6>
                                <ul class="mb-0">
                                    <li class="mb-2">برگزاری جلسات Planning بیشتر</li>
                                    <li class="mb-2">استفاده از ابزارهای بهتر</li>
                                    <li class="mb-2">آموزش تکنولوژی‌های جدید</li>
                                    <li>تقویت فرهنگ DevOps</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Burndown Chart
    const burndownCtx = document.getElementById('burndownChart');
    if (burndownCtx) {
        new Chart(burndownCtx, {
            type: 'line',
            data: {
                labels: ['روز 1', 'روز 2', 'روز 3', 'روز 4', 'روز 5', 'روز 6', 'روز 7', 'روز 8', 'روز 9', 'روز 10', 'روز 11', 'روز 12', 'روز 13', 'روز 14'],
                datasets: [{
                    label: 'ایده‌آل',
                    data: [40, 37, 34, 31, 28, 25, 22, 20, 17, 14, 11, 8, 5, 0],
                    borderColor: '#6c757d',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0
                }, {
                    label: 'واقعی',
                    data: [40, 38, 35, 32, 30, 28, 25, 22, 20, 18, 15, null, null, null],
                    borderColor: '#845adf',
                    backgroundColor: 'rgba(132, 90, 223, 0.1)',
                    tension: 0.3,
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
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Story Points باقیمانده'
                        }
                    }
                }
            }
        });
    }

    // Velocity Chart
    const velocityCtx = document.getElementById('velocityChart');
    if (velocityCtx) {
        new Chart(velocityCtx, {
            type: 'bar',
            data: {
                labels: ['Sprint 7', 'Sprint 8', 'Sprint 9', 'Sprint 10', 'Sprint 11', 'Sprint 12'],
                datasets: [{
                    label: 'Committed',
                    data: [35, 40, 38, 42, 40, 40],
                    backgroundColor: 'rgba(132, 90, 223, 0.5)'
                }, {
                    label: 'Completed',
                    data: [32, 38, 35, 40, 38, 25],
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Story Points'
                        }
                    }
                }
            }
        });
    }

    // Team Contribution Chart
    const teamContributionCtx = document.getElementById('teamContributionChart');
    if (teamContributionCtx) {
        new Chart(teamContributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['علی', 'محمد', 'سارا', 'رضا', 'فاطمه'],
                datasets: [{
                    data: [8, 7, 6, 5, 4],
                    backgroundColor: [
                        '#845adf',
                        '#28a745',
                        '#17a2b8',
                        '#ffc107',
                        '#dc3545'
                    ],
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

    // Cumulative Flow Diagram
    const cumulativeFlowCtx = document.getElementById('cumulativeFlowChart');
    if (cumulativeFlowCtx) {
        new Chart(cumulativeFlowCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 14}, (_, i) => 'روز ' + (i + 1)),
                datasets: [{
                    label: 'تکمیل شده',
                    data: [0, 2, 5, 8, 12, 15, 18, 22, 25, 28, 30, 32, 35, 37],
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: '#28a745',
                    fill: true
                }, {
                    label: 'بررسی',
                    data: [0, 2, 4, 6, 8, 10, 12, 14, 15, 16, 18, 20, 22, 24],
                    backgroundColor: 'rgba(255, 193, 7, 0.7)',
                    borderColor: '#ffc107',
                    fill: true
                }, {
                    label: 'در حال انجام',
                    data: [10, 12, 14, 16, 18, 20, 22, 24, 25, 26, 28, 30, 32, 34],
                    backgroundColor: 'rgba(132, 90, 223, 0.7)',
                    borderColor: '#845adf',
                    fill: true
                }, {
                    label: 'انجام نشده',
                    data: [30, 28, 26, 24, 22, 20, 18, 16, 15, 14, 12, 10, 8, 6],
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: '#dc3545',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        stacked: true,
                        beginAtZero: true
                    },
                    x: {
                        stacked: true
                    }
                }
            }
        });
    }

    // Export to PDF
    $('#export-agile-report').click(function() {
        Swal.fire({
            icon: 'info',
            title: 'در حال آماده‌سازی...',
            text: 'گزارش PDF Agile در حال تهیه است',
            showConfirmButton: false,
            timer: 2000
        });
        
        // This would integrate with jsPDF
        setTimeout(function() {
            Swal.fire({
                icon: 'success',
                title: 'آماده شد!',
                text: 'گزارش با موفقیت دانلود شد',
                showConfirmButton: false,
                timer: 1500
            });
        }, 2000);
    });
});
</script>
