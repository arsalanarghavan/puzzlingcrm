<?php
/**
 * System Manager Dashboard Template (Xintra Style)
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// --- Fetch Dashboard Stats (Cached for 1 hour) ---
if ( false === ( $stats = get_transient( 'puzzling_system_manager_stats_v2' ) ) ) {
    
    // Project & Task Stats
    $total_projects = wp_count_posts('project')->publish;
    $active_tasks_count = count(get_posts(['post_type' => 'task', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done', 'operator' => 'NOT IN']]]));

    // Customer Stats
    $customer_count = count_users()['avail_roles']['customer'] ?? 0;
    
    // Ticket Stats
    $open_tickets = count(get_posts(['post_type' => 'ticket', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'ticket_status', 'field' => 'slug', 'terms' => 'closed', 'operator' => 'NOT IN']]]));

    // Subscription Stats
    $active_subscriptions = 0;
    if ( function_exists('wcs_get_subscription_count') ) {
        $active_subscriptions = wcs_get_subscription_count( 'active' );
    }

    // Financial Stats (Income This Month)
    $income_this_month = 0;
    $current_month_start = date('Y-m-01');
    $current_month_end = date('Y-m-t');
    $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);
    foreach ($contracts as $contract) {
        $installments = get_post_meta($contract->ID, '_installments', true);
        if (is_array($installments)) {
            foreach ($installments as $inst) {
                if (($inst['status'] ?? 'pending') === 'paid' && isset($inst['due_date'])) {
                    $due_date = strtotime($inst['due_date']);
                    if ($due_date >= strtotime($current_month_start) && $due_date <= strtotime($current_month_end)) {
                        $income_this_month += (int)($inst['amount'] ?? 0);
                    }
                }
            }
        }
    }

    $stats = [
        'total_projects' => $total_projects,
        'active_tasks_count' => $active_tasks_count,
        'customer_count' => $customer_count,
        'open_tickets' => $open_tickets,
        'active_subscriptions' => $active_subscriptions,
        'income_this_month' => $income_this_month,
    ];
    set_transient( 'puzzling_system_manager_stats_v2', $stats, HOUR_IN_SECONDS );
}

// Recent activities
$recent_projects = get_posts(['post_type' => 'project', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);
$recent_tasks = get_posts(['post_type' => 'task', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);
$recent_tickets = get_posts(['post_type' => 'ticket', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);
?>

<!-- Stats Cards Row -->
<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-primary">
                            <i class="ri-folder-2-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">پروژه‌های کل</p>
                                <h4 class="fw-semibold mt-1"><?php echo esc_html($stats['total_projects']); ?></h4>
                            </div>
                            <div id="crm-total-customers"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-secondary">
                            <i class="ri-task-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">وظایف فعال</p>
                                <h4 class="fw-semibold mt-1"><?php echo esc_html($stats['active_tasks_count']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-success">
                            <i class="ri-group-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">مشتریان</p>
                                <h4 class="fw-semibold mt-1"><?php echo esc_html($stats['customer_count']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-warning">
                            <i class="ri-money-dollar-circle-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">درآمد ماه جاری</p>
                                <h4 class="fw-semibold mt-1"><?php echo esc_html(number_format($stats['income_this_month'])); ?> <small class="fs-11 text-muted">تومان</small></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities Row -->
<div class="row">
    <!-- Recent Projects -->
    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    پروژه‌های اخیر
                </div>
                <a href="<?php echo esc_url(home_url('/dashboard/projects')); ?>" class="btn btn-sm btn-primary-light">
                    مشاهده همه <i class="ri-arrow-left-s-line align-middle"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($recent_projects)): ?>
                        <?php foreach ($recent_projects as $project): ?>
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-primary-transparent">
                                        <i class="ri-folder-2-line"></i>
                                    </span>
                                    <div class="ms-2 flex-fill">
                                        <p class="fw-semibold mb-0"><?php echo esc_html($project->post_title); ?></p>
                                        <p class="fs-12 text-muted mb-0">
                                            <i class="ri-time-line me-1"></i>
                                            <?php echo esc_html(human_time_diff(strtotime($project->post_date), current_time('timestamp'))); ?> پیش
                                        </p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted">
                            <i class="ri-folder-2-line fs-3 mb-2 d-block opacity-3"></i>
                            پروژه‌ای وجود ندارد
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Recent Tasks -->
    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    وظایف اخیر
                </div>
                <a href="<?php echo esc_url(home_url('/dashboard/tasks')); ?>" class="btn btn-sm btn-secondary-light">
                    مشاهده همه <i class="ri-arrow-left-s-line align-middle"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($recent_tasks)): ?>
                        <?php foreach ($recent_tasks as $task): ?>
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-secondary-transparent">
                                        <i class="ri-task-line"></i>
                                    </span>
                                    <div class="ms-2 flex-fill">
                                        <p class="fw-semibold mb-0"><?php echo esc_html($task->post_title); ?></p>
                                        <p class="fs-12 text-muted mb-0">
                                            <i class="ri-time-line me-1"></i>
                                            <?php echo esc_html(human_time_diff(strtotime($task->post_date), current_time('timestamp'))); ?> پیش
                                        </p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted">
                            <i class="ri-task-line fs-3 mb-2 d-block opacity-3"></i>
                            وظیفه‌ای وجود ندارد
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Recent Tickets -->
    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    تیکت‌های اخیر
                </div>
                <a href="<?php echo esc_url(home_url('/dashboard/tickets')); ?>" class="btn btn-sm btn-success-light">
                    مشاهده همه <i class="ri-arrow-left-s-line align-middle"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($recent_tickets)): ?>
                        <?php foreach ($recent_tickets as $ticket): ?>
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm bg-success-transparent">
                                        <i class="ri-customer-service-2-line"></i>
                                    </span>
                                    <div class="ms-2 flex-fill">
                                        <p class="fw-semibold mb-0"><?php echo esc_html($ticket->post_title); ?></p>
                                        <p class="fs-12 text-muted mb-0">
                                            <i class="ri-time-line me-1"></i>
                                            <?php echo esc_html(human_time_diff(strtotime($ticket->post_date), current_time('timestamp'))); ?> پیش
                                        </p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted">
                            <i class="ri-customer-service-2-line fs-3 mb-2 d-block opacity-3"></i>
                            تیکتی وجود ندارد
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Additional Info Cards -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-information-line me-2"></i>
                    خوش آمدید
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-primary mb-0" role="alert">
                    <strong>به سیستم مدیریت خوش آمدید!</strong>
                    <p class="mb-0 mt-2">از منوی سمت راست می‌توانید به تمام بخش‌های سیستم دسترسی داشته باشید.</p>
                </div>
            </div>
        </div>
    </div>
</div>
