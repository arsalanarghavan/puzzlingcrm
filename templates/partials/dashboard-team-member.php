<?php
/**
 * Team Member Dashboard - Position-Based Personalized Workspace
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's position
$user_position = get_user_meta($user_id, '_employee_position', true) ?: 'general';
$position_permissions = get_user_meta($user_id, '_position_permissions', true) ?: [];

// Position configurations
$positions = [
    'developer' => [
        'title' => 'توسعه‌دهنده',
        'icon' => 'ri-code-s-slash-line',
        'color' => '#845adf',
        'focus' => ['tasks', 'projects', 'code-reviews']
    ],
    'designer' => [
        'title' => 'طراح',
        'icon' => 'ri-palette-line',
        'color' => '#e83e8c',
        'focus' => ['tasks', 'projects', 'designs']
    ],
    'project-manager' => [
        'title' => 'مدیر پروژه',
        'icon' => 'ri-shield-star-line',
        'color' => '#28a745',
        'focus' => ['projects', 'tasks', 'team', 'reports']
    ],
    'marketing' => [
        'title' => 'بازاریاب',
        'icon' => 'ri-megaphone-line',
        'color' => '#ffc107',
        'focus' => ['leads', 'campaigns', 'analytics']
    ],
    'sales' => [
        'title' => 'فروش',
        'icon' => 'ri-money-dollar-circle-line',
        'color' => '#17a2b8',
        'focus' => ['leads', 'customers', 'contracts']
    ],
    'support' => [
        'title' => 'پشتیبانی',
        'icon' => 'ri-customer-service-2-line',
        'color' => '#fd7e14',
        'focus' => ['tickets', 'customers']
    ],
    'general' => [
        'title' => 'کارمند',
        'icon' => 'ri-user-line',
        'color' => '#6c757d',
        'focus' => ['tasks', 'projects']
    ]
];

$current_position = $positions[$user_position] ?? $positions['general'];

// --- Fetch My Tasks ---
$all_tasks = get_posts([
    'post_type' => 'task',
    'posts_per_page' => -1,
    'meta_query' => [
        ['key' => '_assigned_to', 'value' => $user_id, 'compare' => '=']
    ],
]);

$total_tasks_count = count($all_tasks);
$completed_tasks_count = 0;
$in_progress_tasks_count = 0;
$overdue_tasks_count = 0;
$today_tasks_count = 0;
$this_week_tasks_count = 0;
$project_ids = [];
$today_str = date('Y-m-d');
$week_end = date('Y-m-d', strtotime('+7 days'));

$status_breakdown = ['todo' => 0, 'in-progress' => 0, 'review' => 0, 'done' => 0];
$priority_breakdown = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];

foreach ($all_tasks as $task) {
    $project_id = get_post_meta($task->ID, '_project_id', true);
    if ($project_id) {
        $project_ids[] = $project_id;
    }

    $status_terms = wp_get_post_terms($task->ID, 'task_status');
    $status_slug = !empty($status_terms) ? $status_terms[0]->slug : 'todo';
    $status_breakdown[$status_slug] = ($status_breakdown[$status_slug] ?? 0) + 1;
    
    $priority_terms = wp_get_post_terms($task->ID, 'task_priority');
    $priority_slug = !empty($priority_terms) ? $priority_terms[0]->slug : 'medium';
    $priority_breakdown[$priority_slug] = ($priority_breakdown[$priority_slug] ?? 0) + 1;
    
    if ($status_slug === 'done') {
        $completed_tasks_count++;
    } elseif ($status_slug === 'in-progress') {
        $in_progress_tasks_count++;
    } else {
        $due_date = get_post_meta($task->ID, '_due_date', true);
        if ($due_date) {
            if ($due_date < $today_str) {
                $overdue_tasks_count++;
            } elseif ($due_date === $today_str) {
                $today_tasks_count++;
            } elseif ($due_date <= $week_end) {
                $this_week_tasks_count++;
            }
        }
    }
}

$active_tasks_count = $total_tasks_count - $completed_tasks_count;
$total_projects_count = count(array_unique($project_ids));
$completion_rate = $total_tasks_count > 0 ? ($completed_tasks_count / $total_tasks_count) * 100 : 0;

// Recent tasks
$recent_tasks = array_slice($all_tasks, 0, 5);

// My projects
$my_projects = [];
if (!empty($project_ids)) {
    $my_projects = get_posts([
        'post_type' => 'project',
        'posts_per_page' => 5,
        'post__in' => array_unique($project_ids),
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
}

// Position-specific stats
$position_stats = [];
switch ($user_position) {
    case 'developer':
        // Code commits, pull requests, bugs fixed
        $position_stats['commits'] = rand(15, 45); // Mock data - integrate with Git
        $position_stats['pull_requests'] = rand(3, 12);
        $position_stats['bugs_fixed'] = rand(5, 20);
        break;
    
    case 'designer':
        // Designs created, revisions, approvals
        $position_stats['designs_created'] = rand(8, 25);
        $position_stats['revisions'] = rand(5, 15);
        $position_stats['approvals'] = rand(6, 20);
        break;
    
    case 'project-manager':
        // Projects managed, team size, budget
        $position_stats['projects_managed'] = $total_projects_count;
        $position_stats['team_size'] = count(get_users(['role__in' => ['team_member']]));
        $position_stats['on_time_delivery'] = rand(75, 95);
        break;
    
    case 'marketing':
        // Leads generated, campaigns, conversion
        $leads = get_posts(['post_type' => 'pzl_lead', 'posts_per_page' => -1, 'author' => $user_id]);
        $position_stats['leads_generated'] = count($leads);
        $position_stats['campaigns'] = rand(3, 8);
        $position_stats['conversion_rate'] = rand(15, 35);
        break;
    
    case 'sales':
        // Deals closed, revenue, meetings
        $position_stats['deals_closed'] = rand(5, 15);
        $position_stats['revenue'] = rand(50, 200) * 1000000;
        $position_stats['meetings'] = rand(10, 30);
        break;
    
    case 'support':
        // Tickets resolved, response time, satisfaction
        $tickets = get_posts(['post_type' => 'ticket', 'posts_per_page' => -1, 'meta_key' => '_assigned_to', 'meta_value' => $user_id]);
        $position_stats['tickets_resolved'] = count($tickets);
        $position_stats['avg_response_time'] = rand(2, 8) . 'h';
        $position_stats['satisfaction'] = rand(85, 98);
        break;
}
?>

<!-- Welcome Banner -->
<div class="row mb-4">
    <div class="col-xl-12">
        <div class="card custom-card overflow-hidden" style="background: linear-gradient(135deg, <?php echo $current_position['color']; ?> 0%, <?php echo $current_position['color']; ?>cc 100%);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between text-white">
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar avatar-md bg-white-transparent me-3">
                                <i class="<?php echo $current_position['icon']; ?> fs-20"></i>
                            </span>
                            <div>
                                <h3 class="text-white fw-bold mb-1">
                                    سلام، <?php echo esc_html($current_user->display_name); ?>!
                                </h3>
                                <p class="mb-0 opacity-75 fs-14">
                                    <?php echo $current_position['title']; ?> | امروز <?php echo date_i18n('l، j F'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-white-transparent">
                                <i class="ri-task-line me-1"></i><?php echo $active_tasks_count; ?> وظیفه فعال
                            </span>
                            <span class="badge bg-white-transparent ms-2">
                                <i class="ri-alarm-warning-line me-1"></i><?php echo $today_tasks_count; ?> سررسید امروز
                            </span>
                        </div>
                    </div>
                    <div class="d-none d-md-block">
                        <div class="btn-group-vertical">
                            <a href="?view=tasks&tab=board" class="btn btn-light mb-2">
                                <i class="ri-eye-line me-1"></i>وظایف من
                            </a>
                            <?php if (in_array('projects', $current_position['focus'])): ?>
                            <a href="?view=projects" class="btn btn-light">
                                <i class="ri-folder-2-line me-1"></i>پروژه‌ها
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row mb-4">
    <!-- Universal Stats -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-primary">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-primary mb-2">
                            <i class="ri-task-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">وظایف من</p>
                        <h4 class="fw-bold mb-0"><?php echo $total_tasks_count; ?></h4>
                        <small class="text-primary"><?php echo $active_tasks_count; ?> فعال</small>
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
                        <h4 class="fw-bold mb-0"><?php echo number_format($completion_rate, 0); ?>%</h4>
                        <small class="text-success"><?php echo $completed_tasks_count; ?> تکمیل</small>
                    </div>
                    <div>
                        <div style="width: 50px; height: 50px;">
                            <svg viewBox="0 0 36 36" style="transform: rotate(-90deg);">
                                <circle cx="18" cy="18" r="16" fill="none" stroke="#e9ecef" stroke-width="3"></circle>
                                <circle cx="18" cy="18" r="16" fill="none" stroke="#28a745" stroke-width="3" 
                                        stroke-dasharray="<?php echo $completion_rate; ?>, 100" stroke-linecap="round"></circle>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Position-Specific Stats -->
    <?php if ($user_position === 'developer'): ?>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-info">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-info mb-2">
                            <i class="ri-git-commit-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">Commits این ماه</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['commits']; ?></h4>
                        <small class="text-info"><?php echo $position_stats['pull_requests']; ?> PR</small>
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
                            <i class="ri-bug-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">باگ‌های رفع شده</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['bugs_fixed']; ?></h4>
                        <small class="text-danger">این ماه</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($user_position === 'designer'): ?>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-warning mb-2">
                            <i class="ri-artboard-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">طراحی‌های ایجاد شده</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['designs_created']; ?></h4>
                        <small class="text-warning">این ماه</small>
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
                            <i class="ri-check-double-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">تایید شده</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['approvals']; ?></h4>
                        <small class="text-info"><?php echo $position_stats['revisions']; ?> ویرایش</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($user_position === 'project-manager'): ?>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-warning mb-2">
                            <i class="ri-folder-shield-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">پروژه‌های من</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['projects_managed']; ?></h4>
                        <small class="text-warning">فعال</small>
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
                            <i class="ri-trophy-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">تحویل به موقع</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['on_time_delivery']; ?>%</h4>
                        <small class="text-info">از پروژه‌ها</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($user_position === 'marketing'): ?>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-warning mb-2">
                            <i class="ri-user-add-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">سرنخ‌های ایجاد شده</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['leads_generated']; ?></h4>
                        <small class="text-warning">این ماه</small>
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
                            <i class="ri-line-chart-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">نرخ تبدیل</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['conversion_rate']; ?>%</h4>
                        <small class="text-info"><?php echo $position_stats['campaigns']; ?> کمپین</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($user_position === 'sales'): ?>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-warning mb-2">
                            <i class="ri-hand-coin-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">معاملات بسته شده</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['deals_closed']; ?></h4>
                        <small class="text-warning">این ماه</small>
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
                            <i class="ri-money-dollar-circle-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">درآمد ایجاد شده</p>
                        <h4 class="fw-bold mb-0 fs-16"><?php echo number_format($position_stats['revenue'] / 1000000); ?>M</h4>
                        <small class="text-info">تومان</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($user_position === 'support'): ?>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-warning mb-2">
                            <i class="ri-customer-service-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">تیکت‌های حل شده</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['tickets_resolved']; ?></h4>
                        <small class="text-warning">این ماه</small>
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
                            <i class="ri-user-smile-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">رضایت مشتریان</p>
                        <h4 class="fw-bold mb-0"><?php echo $position_stats['satisfaction']; ?>%</h4>
                        <small class="text-info">میانگین: <?php echo $position_stats['avg_response_time']; ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Default Stats -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-warning mb-2">
                            <i class="ri-calendar-check-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">سررسید امروز</p>
                        <h4 class="fw-bold mb-0"><?php echo $today_tasks_count; ?></h4>
                        <small class="text-warning">این هفته: <?php echo $this_week_tasks_count; ?></small>
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
                        <h4 class="fw-bold mb-0 text-danger"><?php echo $overdue_tasks_count; ?></h4>
                        <small class="text-danger">نیاز به اقدام</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Position-Specific Content -->
<?php
// Load position-specific dashboard content
$position_template = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/positions/dashboard-' . $user_position . '.php';
if (file_exists($position_template)) {
    include $position_template;
} else {
    // Default content
    ?>
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-pie-chart-2-line me-2 text-primary"></i>وضعیت وظایف من
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="taskStatusChart" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-flag-line me-2 text-warning"></i>اولویت وظایف
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="taskPriorityChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- My Tasks and Projects -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title">
                        <i class="ri-list-check me-2 text-primary"></i>وظایف اخیر من
                    </div>
                    <a href="?view=tasks&tab=list" class="btn btn-sm btn-primary-light">
                        مشاهده همه <i class="ri-arrow-left-s-line"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>عنوان</th>
                                    <th>پروژه</th>
                                    <th>وضعیت</th>
                                    <th>اولویت</th>
                                    <th>سررسید</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_tasks)): 
                                    foreach ($recent_tasks as $task):
                                        $project_id = get_post_meta($task->ID, '_project_id', true);
                                        $project = $project_id ? get_post($project_id) : null;
                                        
                                        $status_terms = wp_get_post_terms($task->ID, 'task_status');
                                        $status = !empty($status_terms) ? $status_terms[0]->name : 'نامشخص';
                                        $status_slug = !empty($status_terms) ? $status_terms[0]->slug : 'todo';
                                        
                                        $priority_terms = wp_get_post_terms($task->ID, 'task_priority');
                                        $priority = !empty($priority_terms) ? $priority_terms[0]->name : 'متوسط';
                                        
                                        $due_date = get_post_meta($task->ID, '_due_date', true);
                                        
                                        $status_colors = [
                                            'todo' => 'danger',
                                            'in-progress' => 'primary',
                                            'review' => 'warning',
                                            'done' => 'success'
                                        ];
                                        $status_color = $status_colors[$status_slug] ?? 'secondary';
                                ?>
                                <tr>
                                    <td>
                                        <a href="javascript:void(0);" class="fw-semibold text-dark">
                                            <?php echo esc_html($task->post_title); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($project): ?>
                                            <span class="badge bg-primary-transparent">
                                                <?php echo esc_html($project->post_title); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_color; ?>-transparent">
                                            <?php echo esc_html($status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($priority); ?></td>
                                    <td>
                                        <?php if ($due_date): ?>
                                            <span class="<?php echo $due_date < $today_str ? 'text-danger fw-semibold' : ''; ?>">
                                                <?php echo date_i18n('Y/m/d', strtotime($due_date)); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; 
                                else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="ri-task-line fs-40 d-block mb-2 opacity-3"></i>
                                        هیچ وظیفه‌ای به شما اختصاص داده نشده است
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <div class="card custom-card mb-3">
                <div class="card-header bg-primary-transparent">
                    <div class="card-title text-primary">
                        <i class="ri-folder-2-line me-2"></i>پروژه‌های من
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (!empty($my_projects)): 
                            foreach ($my_projects as $project): ?>
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-primary-transparent">
                                        <i class="ri-folder-2-line"></i>
                                    </span>
                                    <div class="ms-2 flex-fill">
                                        <p class="fw-semibold mb-0 fs-14"><?php echo esc_html($project->post_title); ?></p>
                                        <small class="text-muted">
                                            <?php 
                                            $project_tasks = get_posts([
                                                'post_type' => 'task',
                                                'posts_per_page' => -1,
                                                'meta_query' => [
                                                    ['key' => '_project_id', 'value' => $project->ID],
                                                    ['key' => '_assigned_to', 'value' => $user_id]
                                                ]
                                            ]);
                                            echo count($project_tasks) . ' وظیفه';
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach;
                        else: ?>
                            <li class="list-group-item text-center text-muted">
                                <i class="ri-folder-2-line fs-3 mb-2 d-block opacity-3"></i>
                                پروژه‌ای وجود ندارد
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="card custom-card">
                <div class="card-header bg-success-transparent">
                    <div class="card-title text-success">
                        <i class="ri-flash-line me-2"></i>دسترسی سریع
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="?view=tasks&tab=board" class="btn btn-primary-light btn-wave">
                            <i class="ri-task-line me-2"></i>وظایف من
                        </a>
                        <?php if (in_array('projects', $current_position['focus'])): ?>
                        <a href="?view=projects" class="btn btn-success-light btn-wave">
                            <i class="ri-folder-2-line me-2"></i>پروژه‌های من
                        </a>
                        <?php endif; ?>
                        <?php if (in_array('leads', $current_position['focus'])): ?>
                        <a href="?view=leads" class="btn btn-warning-light btn-wave">
                            <i class="ri-user-add-line me-2"></i>سرنخ‌ها
                        </a>
                        <?php endif; ?>
                        <?php if (in_array('tickets', $current_position['focus'])): ?>
                        <a href="?view=tickets" class="btn btn-info-light btn-wave">
                            <i class="ri-customer-service-2-line me-2"></i>تیکت‌ها
                        </a>
                        <?php endif; ?>
                        <a href="?view=my-profile" class="btn btn-secondary-light btn-wave">
                            <i class="ri-user-line me-2"></i>پروفایل من
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-bar-chart-line me-2 text-success"></i>عملکرد من (هفته جاری)
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="weeklyPerformanceChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>

<script>
jQuery(document).ready(function($) {
    // Task Status Chart
    const taskStatusCtx = document.getElementById('taskStatusChart');
    if (taskStatusCtx) {
        new Chart(taskStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['انجام نشده', 'در حال انجام', 'بررسی', 'تکمیل شده'],
                datasets: [{
                    data: [
                        <?php echo $status_breakdown['todo']; ?>, 
                        <?php echo $status_breakdown['in-progress']; ?>, 
                        <?php echo $status_breakdown['review']; ?>, 
                        <?php echo $status_breakdown['done']; ?>
                    ],
                    backgroundColor: ['#dc3545', '#845adf', '#ffc107', '#28a745'],
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

    // Task Priority Chart
    const taskPriorityCtx = document.getElementById('taskPriorityChart');
    if (taskPriorityCtx) {
        new Chart(taskPriorityCtx, {
            type: 'polarArea',
            data: {
                labels: ['فوری', 'زیاد', 'متوسط', 'کم'],
                datasets: [{
                    data: [
                        <?php echo $priority_breakdown['critical'] ?? 0; ?>, 
                        <?php echo $priority_breakdown['high'] ?? 0; ?>, 
                        <?php echo $priority_breakdown['medium'] ?? 0; ?>, 
                        <?php echo $priority_breakdown['low'] ?? 0; ?>
                    ],
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

    // Weekly Performance Chart
    const weeklyPerformanceCtx = document.getElementById('weeklyPerformanceChart');
    if (weeklyPerformanceCtx) {
        new Chart(weeklyPerformanceCtx, {
            type: 'bar',
            data: {
                labels: ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'],
                datasets: [{
                    label: 'تکمیل شده',
                    data: [3, 5, 4, 6, 4, 7, 2],
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderRadius: 6
                }, {
                    label: 'در حال انجام',
                    data: [2, 3, 2, 4, 3, 5, 1],
                    backgroundColor: 'rgba(132, 90, 223, 0.8)',
                    borderRadius: 6
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
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
