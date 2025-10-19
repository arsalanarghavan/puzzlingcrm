<?php
/**
 * Team Member Dashboard Template (Xintra Style)
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// --- Fetch & Calculate Stats ---
$all_tasks_query_args = [
    'post_type' => 'task',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_assigned_to',
            'value' => $user_id,
        ],
    ],
];

$all_tasks = get_posts($all_tasks_query_args);
$total_tasks_count = count($all_tasks);
$project_ids = [];
$completed_tasks_count = 0;
$overdue_tasks_count = 0;
$today_str = date('Y-m-d');

foreach ($all_tasks as $task) {
    $project_id = get_post_meta($task->ID, '_project_id', true);
    if ($project_id) {
        $project_ids[] = $project_id;
    }

    $statuses = get_the_terms($task->ID, 'task_status');
    if ($statuses && !is_wp_error($statuses)) {
        $status_slug = $statuses[0]->slug;
        
        if ($status_slug === 'done') {
            $completed_tasks_count++;
        } else {
            $due_date = get_post_meta($task->ID, '_due_date', true);
            if ($due_date && $due_date < $today_str) {
                $overdue_tasks_count++;
            }
        }
    }
}

$active_tasks_count = $total_tasks_count - $completed_tasks_count;
$total_projects_count = count(array_unique($project_ids));
?>

<!-- Stats Cards Row -->
<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-primary">
                            <i class="ri-folder-2-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div>
                            <p class="text-muted mb-0">پروژه‌های من</p>
                            <h4 class="fw-semibold mt-1"><?php echo esc_html($total_projects_count); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-success">
                            <i class="ri-task-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div>
                            <p class="text-muted mb-0">وظایف فعال</p>
                            <h4 class="fw-semibold mt-1"><?php echo esc_html($active_tasks_count); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-info">
                            <i class="ri-checkbox-circle-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div>
                            <p class="text-muted mb-0">تکمیل شده</p>
                            <h4 class="fw-semibold mt-1"><?php echo esc_html($completed_tasks_count); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-danger">
                            <i class="ri-error-warning-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div>
                            <p class="text-muted mb-0">عقب افتاده</p>
                            <h4 class="fw-semibold mt-1"><?php echo esc_html($overdue_tasks_count); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tasks -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    <i class="ri-task-line me-2"></i>
                    وظایف اخیر من
                </div>
                <a href="<?php echo esc_url(home_url('/dashboard/tasks')); ?>" class="btn btn-sm btn-primary">
                    مشاهده همه <i class="ri-arrow-left-s-line align-middle"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table text-nowrap">
                        <thead>
                            <tr>
                                <th>عنوان</th>
                                <th>وضعیت</th>
                                <th>سررسید</th>
                                <th>پروژه</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_tasks)): ?>
                                <?php foreach (array_slice($all_tasks, 0, 10) as $task): ?>
                                    <?php
                                    $statuses = get_the_terms($task->ID, 'task_status');
                                    $status_name = $statuses && !is_wp_error($statuses) ? $statuses[0]->name : 'نامشخص';
                                    $status_slug = $statuses && !is_wp_error($statuses) ? $statuses[0]->slug : '';
                                    
                                    $due_date = get_post_meta($task->ID, '_due_date', true);
                                    $project_id = get_post_meta($task->ID, '_project_id', true);
                                    $project_title = $project_id ? get_the_title($project_id) : '-';
                                    
                                    $badge_class = 'bg-secondary';
                                    if ($status_slug === 'done') $badge_class = 'bg-success';
                                    elseif ($status_slug === 'in-progress') $badge_class = 'bg-primary';
                                    elseif ($status_slug === 'review') $badge_class = 'bg-warning';
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($task->post_title); ?></td>
                                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo esc_html($status_name); ?></span></td>
                                        <td>
                                            <?php if ($due_date): ?>
                                                <span class="<?php echo ($due_date < $today_str) ? 'text-danger' : ''; ?>">
                                                    <i class="ri-calendar-line me-1"></i>
                                                    <?php echo esc_html(jdate('Y/m/d', strtotime($due_date))); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($project_title); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i class="ri-task-line fs-3 mb-2 d-block opacity-3"></i>
                                        وظیفه‌ای به شما اختصاص داده نشده است
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
