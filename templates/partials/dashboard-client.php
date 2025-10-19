<?php
/**
 * Client Dashboard - Subscription-Based Personalized Portal
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get client's subscription/plan level
$subscription_level = get_user_meta($user_id, '_subscription_level', true) ?: 'basic';
$subscription_features = get_user_meta($user_id, '_subscription_features', true) ?: [];

// Subscription tiers
$subscriptions = [
    'basic' => [
        'name' => 'پایه',
        'color' => '#6c757d',
        'icon' => 'ri-vip-line',
        'features' => ['projects', 'tickets', 'contracts', 'invoices']
    ],
    'standard' => [
        'name' => 'استاندارد',
        'color' => '#17a2b8',
        'icon' => 'ri-vip-crown-line',
        'features' => ['projects', 'tickets', 'contracts', 'invoices', 'reports', 'file-manager']
    ],
    'premium' => [
        'name' => 'پریمیوم',
        'color' => '#ffc107',
        'icon' => 'ri-vip-crown-2-line',
        'features' => ['projects', 'tickets', 'contracts', 'invoices', 'reports', 'file-manager', 'api-access', 'priority-support']
    ],
    'enterprise' => [
        'name' => 'سازمانی',
        'color' => '#845adf',
        'icon' => 'ri-vip-diamond-line',
        'features' => ['projects', 'tickets', 'contracts', 'invoices', 'reports', 'file-manager', 'api-access', 'priority-support', 'custom-domain', 'white-label']
    ]
];

$current_subscription = $subscriptions[$subscription_level] ?? $subscriptions['basic'];

// Check if user has specific features
$has_feature = function($feature) use ($current_subscription, $subscription_features) {
    return in_array($feature, $current_subscription['features']) || in_array($feature, $subscription_features);
};

// --- Fetch Client's Data ---

// Projects
$my_projects = get_posts([
    'post_type' => 'project',
    'posts_per_page' => -1,
    'meta_query' => [
        ['key' => '_customer_id', 'value' => $user_id, 'compare' => '=']
    ],
]);

$total_projects = count($my_projects);
$active_projects = 0;
$completed_projects = 0;
$project_progress = [];

foreach ($my_projects as $project) {
    $status = get_post_meta($project->ID, '_project_status', true);
    if ($status === 'active' || $status === 'in-progress') {
        $active_projects++;
    } elseif ($status === 'completed') {
        $completed_projects++;
    }
    
    // Calculate project completion
    $tasks = get_posts([
        'post_type' => 'task',
        'posts_per_page' => -1,
        'meta_query' => [
            ['key' => '_project_id', 'value' => $project->ID, 'compare' => '=']
        ]
    ]);
    
    $done_tasks = 0;
    foreach ($tasks as $task) {
        $task_status = wp_get_post_terms($task->ID, 'task_status');
        if (!empty($task_status) && $task_status[0]->slug === 'done') {
            $done_tasks++;
        }
    }
    
    $project_progress[$project->ID] = [
        'total' => count($tasks),
        'done' => $done_tasks,
        'percentage' => count($tasks) > 0 ? ($done_tasks / count($tasks)) * 100 : 0
    ];
}

// Tickets
$my_tickets = get_posts([
    'post_type' => 'ticket',
    'posts_per_page' => -1,
    'author' => $user_id,
]);

$total_tickets = count($my_tickets);
$open_tickets = 0;
$resolved_tickets = 0;
$avg_response_time = 0;

foreach ($my_tickets as $ticket) {
    $status = get_post_meta($ticket->ID, '_ticket_status', true) ?: 'open';
    if (in_array($status, ['open', 'pending'])) {
        $open_tickets++;
    } else {
        $resolved_tickets++;
    }
}

// Contracts & Payments
$my_contracts = get_posts([
    'post_type' => 'contract',
    'posts_per_page' => -1,
    'meta_query' => [
        ['key' => '_customer_id', 'value' => $user_id, 'compare' => '=']
    ],
]);

$total_contracts = count($my_contracts);
$total_value = 0;
$paid_amount = 0;
$pending_amount = 0;
$overdue_payments = 0;

foreach ($my_contracts as $contract) {
    $amount = (float) get_post_meta($contract->ID, '_total_amount', true);
    $total_value += $amount;
    
    $installments = get_post_meta($contract->ID, '_installments', true);
    if (is_array($installments)) {
        foreach ($installments as $inst) {
            if (($inst['status'] ?? 'pending') === 'paid') {
                $paid_amount += (int)($inst['amount'] ?? 0);
            } else {
                $pending_amount += (int)($inst['amount'] ?? 0);
                
                // Check if overdue
                if (isset($inst['due_date']) && strtotime($inst['due_date']) < time()) {
                    $overdue_payments++;
                }
            }
        }
    }
}

$payment_progress = $total_value > 0 ? ($paid_amount / $total_value) * 100 : 0;

// Recent activities
$recent_projects = array_slice($my_projects, 0, 3);
$recent_tickets = array_slice($my_tickets, 0, 3);

// Subscription limits
$subscription_limits = [
    'basic' => ['projects' => 5, 'storage' => '5GB', 'users' => 1],
    'standard' => ['projects' => 20, 'storage' => '50GB', 'users' => 5],
    'premium' => ['projects' => 100, 'storage' => '200GB', 'users' => 20],
    'enterprise' => ['projects' => -1, 'storage' => 'Unlimited', 'users' => -1]
];

$current_limits = $subscription_limits[$subscription_level];
$projects_limit_reached = $current_limits['projects'] > 0 && $total_projects >= $current_limits['projects'];
?>

<!-- Welcome Banner with Subscription Info -->
<div class="row mb-4">
    <div class="col-xl-12">
        <div class="card custom-card overflow-hidden" style="background: linear-gradient(135deg, <?php echo $current_subscription['color']; ?> 0%, <?php echo $current_subscription['color']; ?>cc 100%);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between text-white">
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar avatar-md bg-white-transparent me-3">
                                <i class="<?php echo $current_subscription['icon']; ?> fs-20"></i>
                            </span>
                            <div>
                                <h3 class="text-white fw-bold mb-1">
                                    سلام، <?php echo esc_html($current_user->display_name); ?> عزیز!
                                </h3>
                                <p class="mb-0 opacity-75 fs-14">
                                    اشتراک <?php echo $current_subscription['name']; ?> | <?php echo $active_projects; ?> پروژه فعال
                                </p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-white-transparent">
                                <i class="ri-folder-2-line me-1"></i><?php echo $total_projects; ?> پروژه
                                <?php if ($current_limits['projects'] > 0): ?>
                                    / <?php echo $current_limits['projects']; ?>
                                <?php endif; ?>
                            </span>
                            <?php if ($overdue_payments > 0): ?>
                            <span class="badge bg-danger ms-2">
                                <i class="ri-alarm-warning-line me-1"></i><?php echo $overdue_payments; ?> پرداخت معوق
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-none d-md-block">
                        <div class="btn-group-vertical">
                            <a href="?view=tickets" class="btn btn-light mb-2">
                                <i class="ri-customer-service-2-line me-1"></i>تیکت جدید
                            </a>
                            <?php if ($subscription_level !== 'enterprise'): ?>
                            <button class="btn btn-warning" onclick="upgradeSubscription()">
                                <i class="ri-arrow-up-line me-1"></i>ارتقا اشتراک
                            </button>
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
    <!-- Projects -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-primary">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-primary mb-2">
                            <i class="ri-folder-2-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">پروژه‌های من</p>
                        <h4 class="fw-bold mb-0"><?php echo $total_projects; ?></h4>
                        <small class="text-primary"><?php echo $active_projects; ?> فعال</small>
                    </div>
                    <?php if ($projects_limit_reached): ?>
                    <div>
                        <span class="badge bg-danger">حد مجاز</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contracts -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-success">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-success mb-2">
                            <i class="ri-file-text-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">قراردادها</p>
                        <h4 class="fw-bold mb-0"><?php echo $total_contracts; ?></h4>
                        <small class="text-success"><?php echo number_format($total_value / 1000000, 1); ?>M تومان</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Progress -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-warning mb-2">
                            <i class="ri-money-dollar-circle-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">پیشرفت پرداخت</p>
                        <h4 class="fw-bold mb-0"><?php echo number_format($payment_progress, 0); ?>%</h4>
                        <small class="text-warning">باقیمانده: <?php echo number_format($pending_amount / 1000000, 1); ?>M</small>
                    </div>
                    <div>
                        <div style="width: 50px; height: 50px;">
                            <svg viewBox="0 0 36 36" style="transform: rotate(-90deg);">
                                <circle cx="18" cy="18" r="16" fill="none" stroke="#e9ecef" stroke-width="3"></circle>
                                <circle cx="18" cy="18" r="16" fill="none" stroke="#ffc107" stroke-width="3" 
                                        stroke-dasharray="<?php echo $payment_progress; ?>, 100" stroke-linecap="round"></circle>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Support -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card border border-info">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="avatar avatar-lg avatar-rounded bg-info mb-2">
                            <i class="ri-customer-service-2-line fs-24"></i>
                        </span>
                        <p class="mb-1 text-muted">پشتیبانی</p>
                        <h4 class="fw-bold mb-0"><?php echo $total_tickets; ?></h4>
                        <small class="text-info">
                            <?php echo $has_feature('priority-support') ? '⚡ اولویت بالا' : $open_tickets . ' باز'; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Subscription Features Banner -->
<?php if ($projects_limit_reached || $overdue_payments > 0): ?>
<div class="row mb-4">
    <div class="col-xl-12">
        <?php if ($projects_limit_reached): ?>
        <div class="alert alert-warning border-start border-warning border-3">
            <div class="d-flex align-items-center">
                <i class="ri-error-warning-line fs-24 me-3"></i>
                <div>
                    <strong>محدودیت پروژه:</strong>
                    شما به حداکثر تعداد پروژه‌های مجاز رسیده‌اید (<?php echo $current_limits['projects']; ?> پروژه).
                    <a href="javascript:upgradeSubscription()" class="alert-link">ارتقا اشتراک</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($overdue_payments > 0): ?>
        <div class="alert alert-danger border-start border-danger border-3">
            <div class="d-flex align-items-center">
                <i class="ri-alarm-warning-line fs-24 me-3"></i>
                <div>
                    <strong>پرداخت معوق:</strong>
                    شما <?php echo $overdue_payments; ?> پرداخت معوق دارید.
                    <a href="?view=client-contracts" class="alert-link">مشاهده قراردادها</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Project Progress -->
    <div class="col-xl-<?php echo $has_feature('reports') ? '6' : '12'; ?>">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-bar-chart-box-line me-2 text-primary"></i>پیشرفت پروژه‌ها
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($my_projects)): ?>
                    <?php foreach (array_slice($my_projects, 0, 5) as $project):
                        $progress = $project_progress[$project->ID];
                        $color = $progress['percentage'] >= 75 ? 'success' : ($progress['percentage'] >= 50 ? 'warning' : 'danger');
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold"><?php echo esc_html($project->post_title); ?></span>
                            <span class="badge bg-<?php echo $color; ?>-transparent">
                                <?php echo number_format($progress['percentage'], 0); ?>%
                            </span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-<?php echo $color; ?>" 
                                 style="width: <?php echo $progress['percentage']; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo $progress['done']; ?> از <?php echo $progress['total']; ?> وظیفه</small>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="ri-folder-2-line fs-40 d-block mb-2 opacity-3"></i>
                        هیچ پروژه‌ای وجود ندارد
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Financial Chart (if has reports feature) -->
    <?php if ($has_feature('reports')): ?>
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-pie-chart-2-line me-2 text-success"></i>وضعیت مالی
                </div>
            </div>
            <div class="card-body">
                <canvas id="financeChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Projects and Tickets -->
<div class="row mb-4">
    <!-- My Projects -->
    <div class="col-xl-<?php echo $has_feature('file-manager') ? '6' : '8'; ?>">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-folder-2-line me-2 text-primary"></i>پروژه‌های من
                </div>
                <a href="?view=client-projects" class="btn btn-sm btn-primary-light">
                    مشاهده همه <i class="ri-arrow-left-s-line"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>عنوان</th>
                                <th>وضعیت</th>
                                <th>پیشرفت</th>
                                <th>تاریخ شروع</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_projects)): 
                                foreach ($recent_projects as $project):
                                    $status = get_post_meta($project->ID, '_project_status', true) ?: 'active';
                                    $start_date = get_post_meta($project->ID, '_start_date', true);
                                    $progress = $project_progress[$project->ID];
                                    
                                    $status_labels = [
                                        'active' => 'فعال',
                                        'in-progress' => 'در حال انجام',
                                        'completed' => 'تکمیل شده',
                                        'on-hold' => 'متوقف'
                                    ];
                                    
                                    $status_colors = [
                                        'active' => 'success',
                                        'in-progress' => 'primary',
                                        'completed' => 'info',
                                        'on-hold' => 'warning'
                                    ];
                            ?>
                            <tr>
                                <td>
                                    <a href="?view=client-project&id=<?php echo $project->ID; ?>" class="fw-semibold text-dark">
                                        <?php echo esc_html($project->post_title); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $status_colors[$status] ?? 'secondary'; ?>-transparent">
                                        <?php echo $status_labels[$status] ?? $status; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="width: 100px; height: 6px;">
                                        <div class="progress-bar bg-primary" style="width: <?php echo $progress['percentage']; ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $start_date ? date_i18n('Y/m/d', strtotime($start_date)) : '---'; ?>
                                </td>
                            </tr>
                            <?php endforeach;
                            else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="ri-folder-2-line fs-40 d-block mb-2 opacity-3"></i>
                                    هیچ پروژه‌ای ثبت نشده است
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- My Tickets / File Manager -->
    <div class="col-xl-<?php echo $has_feature('file-manager') ? '6' : '4'; ?>">
        <div class="card custom-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-customer-service-2-line me-2 text-success"></i>تیکت‌های اخیر
                </div>
                <a href="?view=tickets" class="btn btn-sm btn-success-light">
                    مشاهده همه <i class="ri-arrow-left-s-line"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($recent_tickets)): 
                        foreach ($recent_tickets as $ticket):
                            $status = get_post_meta($ticket->ID, '_ticket_status', true) ?: 'open';
                            
                            $status_colors = [
                                'open' => 'danger',
                                'pending' => 'warning',
                                'resolved' => 'info',
                                'closed' => 'success'
                            ];
                    ?>
                    <li class="list-group-item">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-fill">
                                <a href="?view=ticket&id=<?php echo $ticket->ID; ?>" class="fw-semibold text-dark d-block">
                                    <?php echo esc_html(wp_trim_words($ticket->post_title, 8)); ?>
                                </a>
                                <small class="text-muted">
                                    <i class="ri-time-line me-1"></i>
                                    <?php echo human_time_diff(strtotime($ticket->post_date), current_time('timestamp')); ?> پیش
                                </small>
                            </div>
                            <span class="badge bg-<?php echo $status_colors[$status]; ?>-transparent">
                                <?php echo $status; ?>
                            </span>
                        </div>
                    </li>
                    <?php endforeach;
                    else: ?>
                    <li class="list-group-item text-center text-muted">
                        <i class="ri-customer-service-2-line fs-3 mb-2 d-block opacity-3"></i>
                        هیچ تیکتی ثبت نشده است
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Subscription Info -->
        <div class="card custom-card">
            <div class="card-header" style="background: linear-gradient(135deg, <?php echo $current_subscription['color']; ?>22 0%, <?php echo $current_subscription['color']; ?>44 100%);">
                <div class="card-title" style="color: <?php echo $current_subscription['color']; ?>;">
                    <i class="<?php echo $current_subscription['icon']; ?> me-2"></i>اشتراک شما
                </div>
            </div>
            <div class="card-body">
                <h5 class="fw-bold mb-3"><?php echo $current_subscription['name']; ?></h5>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">پروژه‌ها</small>
                        <small><?php echo $total_projects; ?> / <?php echo $current_limits['projects'] > 0 ? $current_limits['projects'] : '∞'; ?></small>
                    </div>
                    <?php if ($current_limits['projects'] > 0): ?>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar" style="width: <?php echo ($total_projects / $current_limits['projects']) * 100; ?>%"></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">فضای ذخیره‌سازی</small>
                    <strong><?php echo $current_limits['storage']; ?></strong>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">کاربران</small>
                    <strong><?php echo $current_limits['users'] > 0 ? $current_limits['users'] : 'نامحدود'; ?></strong>
                </div>
                
                <?php if ($subscription_level !== 'enterprise'): ?>
                <button class="btn btn-primary w-100 btn-wave" onclick="upgradeSubscription()">
                    <i class="ri-arrow-up-line me-1"></i>ارتقا اشتراک
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header bg-info-transparent">
                <div class="card-title text-info">
                    <i class="ri-flash-line me-2"></i>دسترسی سریع
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="?view=client-projects" class="btn btn-primary-light btn-wave w-100">
                            <i class="ri-folder-2-line d-block fs-20 mb-1"></i>
                            <small>پروژه‌ها</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="?view=client-contracts" class="btn btn-success-light btn-wave w-100">
                            <i class="ri-file-text-line d-block fs-20 mb-1"></i>
                            <small>قراردادها</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="?view=tickets" class="btn btn-warning-light btn-wave w-100">
                            <i class="ri-customer-service-2-line d-block fs-20 mb-1"></i>
                            <small>پشتیبانی</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="?view=client-invoices" class="btn btn-info-light btn-wave w-100">
                            <i class="ri-bill-line d-block fs-20 mb-1"></i>
                            <small>پیش‌فاکتورها</small>
                        </a>
                    </div>
                    <?php if ($has_feature('reports')): ?>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="?view=client-reports" class="btn btn-secondary-light btn-wave w-100">
                            <i class="ri-bar-chart-box-line d-block fs-20 mb-1"></i>
                            <small>گزارش‌ها</small>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($has_feature('file-manager')): ?>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="?view=client-files" class="btn btn-danger-light btn-wave w-100">
                            <i class="ri-folder-open-line d-block fs-20 mb-1"></i>
                            <small>فایل‌ها</small>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
<?php if ($has_feature('reports')): ?>
// Finance Chart
const financeCtx = document.getElementById('financeChart');
if (financeCtx) {
    new Chart(financeCtx, {
        type: 'doughnut',
        data: {
            labels: ['پرداخت شده', 'باقیمانده'],
            datasets: [{
                data: [
                    <?php echo round($paid_amount / 1000000); ?>, 
                    <?php echo round($pending_amount / 1000000); ?>
                ],
                backgroundColor: ['#28a745', '#ffc107'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + ' میلیون تومان';
                        }
                    }
                }
            }
        }
    });
}
<?php endif; ?>

function upgradeSubscription() {
    Swal.fire({
        title: 'ارتقا اشتراک',
        html: '<p>برای ارتقا اشتراک خود، لطفاً با واحد فروش تماس بگیرید.</p>' +
              '<div class="mt-3">' +
              '<strong>تلفن:</strong> 021-12345678<br>' +
              '<strong>ایمیل:</strong> sales@puzzlingco.com' +
              '</div>',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'تماس با فروش',
        cancelButtonText: 'بستن'
    });
}
</script>
