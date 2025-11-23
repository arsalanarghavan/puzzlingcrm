<?php
/**
 * Single Project View Template - Redesigned based on Xintra projects-overview.html
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $puzzling_project_id;
$project_id = $puzzling_project_id ? $puzzling_project_id : (isset($_GET['project_id']) ? intval($_GET['project_id']) : 0);
$project = $project_id > 0 ? get_post($project_id) : null;

if (!$project || $project->post_type !== 'project') {
    echo '<div class="alert alert-danger">پروژه مورد نظر یافت نشد.</div>';
    return;
}

$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;
$is_manager = current_user_can('manage_options');
$is_team_member = in_array('team_member', (array)$current_user->roles);
$is_customer = in_array('customer', (array)$current_user->roles);

// Security Check: Allow access if:
// 1. User is manager/system_manager
// 2. User is team member assigned to project or has tasks in project
// 3. User is the project owner (customer)
$has_access = false;

if ($is_manager) {
    $has_access = true;
} elseif ($is_team_member) {
    // Check if team member is assigned to project
    $assigned_members = get_post_meta($project_id, '_assigned_team_members', true);
    if (is_array($assigned_members) && in_array($current_user_id, $assigned_members)) {
        $has_access = true;
    } else {
        // Check if team member has tasks in this project
        $user_tasks = get_posts([
            'post_type' => 'task',
            'posts_per_page' => 1,
            'meta_query' => [
                ['key' => '_project_id', 'value' => $project_id, 'compare' => '='],
                ['key' => '_assigned_to', 'value' => $current_user_id, 'compare' => '=']
            ]
        ]);
        if (!empty($user_tasks)) {
            $has_access = true;
        }
    }
} elseif ($is_customer && $project->post_author == $current_user_id) {
    $has_access = true;
}

if (!$has_access) {
    echo '<div class="alert alert-danger">شما دسترسی لازم برای مشاهده این پروژه را ندارید.</div>';
    return;
}

// Get project data
$contract_id = get_post_meta($project_id, '_contract_id', true);
$customer = $project->post_author ? get_userdata($project->post_author) : null;
$project_manager_id = get_post_meta($project_id, '_project_manager', true);
$project_manager = $project_manager_id ? get_userdata($project_manager_id) : null;
$start_date = get_post_meta($project_id, '_project_start_date', true);
$end_date = get_post_meta($project_id, '_project_end_date', true);
if (!$start_date && $contract_id) {
    $start_date = get_post_meta($contract_id, '_project_start_date', true);
}
if (!$end_date && $contract_id) {
    $end_date = get_post_meta($contract_id, '_project_end_date', true);
}

$status_terms = get_the_terms($project_id, 'project_status');
$status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : '---';
$status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';

$priority = get_post_meta($project_id, '_project_priority', true);
$priority_map = ['high' => 'زیاد', 'medium' => 'متوسط', 'low' => 'کم'];
$priority_name = $priority_map[$priority] ?? 'متوسط';
$priority_badges = ['high' => 'bg-danger-transparent', 'medium' => 'bg-warning-transparent', 'low' => 'bg-success-transparent'];
$priority_badge_class = $priority_badges[$priority] ?? 'bg-warning-transparent';

$assigned_members = get_post_meta($project_id, '_assigned_team_members', true);
$assigned_members = is_array($assigned_members) ? $assigned_members : [];

$project_tags = wp_get_post_terms($project_id, 'project_tag', ['fields' => 'names']);

// Get project tasks
$project_tasks_args = [
    'post_type' => 'task',
    'posts_per_page' => -1,
    'meta_query' => [
        ['key' => '_project_id', 'value' => $project_id, 'compare' => '=']
    ],
    'orderby' => 'post_date',
    'order' => 'DESC',
];

// If user is not a manager, only show tasks assigned to them
if (!$is_manager) {
    $project_tasks_args['meta_query'][] = [
        'key' => '_assigned_to',
        'value' => $current_user_id,
        'compare' => '='
    ];
}

$project_tasks = get_posts($project_tasks_args);

// Calculate completion
$total_tasks = count($project_tasks);
$completed_tasks = 0;
foreach ($project_tasks as $task) {
    $task_status_terms = wp_get_post_terms($task->ID, 'task_status');
    if (!empty($task_status_terms) && $task_status_terms[0]->slug === 'done') {
        $completed_tasks++;
    }
}
$completion_percentage = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

// Get key tasks (first 6 tasks)
$key_tasks = array_slice($project_tasks, 0, 6);
$other_tasks = array_slice($project_tasks, 6);

$dashboard_url = puzzling_get_dashboard_url();
$edit_url = add_query_arg(['view' => 'projects', 'action' => 'edit', 'project_id' => $project_id], $dashboard_url);
$back_url = remove_query_arg(['project_id', 'action'], $dashboard_url);
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2 mb-4">
    <div>
        <nav>
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?php echo esc_url($back_url); ?>">برنامه‌ها</a></li>
                <li class="breadcrumb-item"><a href="<?php echo esc_url(add_query_arg('view', 'projects', $dashboard_url)); ?>">پروژه‌ها</a></li>
                <li class="breadcrumb-item active" aria-current="page">نمای کلی پروژه‌ها</li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0">نمای کلی پروژه‌ها</h1>
    </div>
    <div class="btn-list">
        <button class="btn btn-white btn-wave">
            <i class="ri-filter-3-line align-middle me-1 lh-1"></i> فیلتر
        </button>
        <button class="btn btn-primary btn-wave me-0">
            <i class="ri-share-forward-line me-1"></i> اشتراک‌گذاری
        </button>
    </div>
</div>
<!-- Page Header Close -->

<!-- Start::row-1 -->
<div class="row">
    <div class="col-xxl-8">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    جزئیات پروژه
                </div> 
                <div>
                    <?php if ($is_manager): ?>
                    <a href="<?php echo esc_url(add_query_arg(['view' => 'projects', 'action' => 'new'], $dashboard_url)); ?>" class="btn btn-sm btn-primary btn-wave"><i class="ri-add-line align-middle me-1 fw-medium"></i>ایجاد پروژه</a>
                    <a href="<?php echo esc_url($edit_url); ?>" class="btn btn-sm btn-primary1 btn-wave"><i class="ri-edit-line align-middle fw-medium me-1"></i>ویرایش</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-4 gap-2 flex-wrap">
                    <?php if (has_post_thumbnail($project_id)): ?>
                        <span class="avatar avatar-lg me-1 bg-primary-gradient">
                            <?php echo get_the_post_thumbnail($project_id, 'thumbnail', ['style' => 'width: 48px; height: 48px; object-fit: cover;']); ?>
                        </span>
                    <?php else: ?>
                        <span class="avatar avatar-lg me-1 bg-primary-gradient"><i class="ri-stack-line fs-24 lh-1"></i></span>
                    <?php endif; ?>
                    <div>
                        <h6 class="fw-medium mb-2 task-title"><?php echo esc_html($project->post_title); ?></h6>
                        <span class="badge bg-<?php echo $status_slug === 'active' ? 'success' : ($status_slug === 'completed' ? 'primary' : ($status_slug === 'on-hold' ? 'warning' : 'secondary')); ?>-transparent"><?php echo esc_html($status_name); ?></span>
                        <span class="text-muted fs-12"><i class="ri-circle-fill text-<?php echo $status_slug === 'active' ? 'success' : 'secondary'; ?> mx-2 fs-9"></i>آخرین بروزرسانی <?php echo human_time_diff(strtotime($project->post_modified), current_time('timestamp')); ?> پیش</span>
                    </div>
                    <?php if ($is_manager): ?>
                    <div class="ms-auto align-self-start">
                        <div class="dropdown">
                            <a aria-label="anchor" href="javascript:void(0);" class="btn btn-icon btn-sm btn-primary-light" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fe fe-more-vertical"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg(['view' => 'projects', 'action' => 'view', 'project_id' => $project_id], $dashboard_url)); ?>"><i class="ri-eye-line align-middle me-1 d-inline-block"></i>مشاهده</a></li>
                                <li><a class="dropdown-item" href="<?php echo esc_url($edit_url); ?>"><i class="ri-edit-line align-middle me-1 d-inline-block"></i>ویرایش</a></li>
                                <li><a class="dropdown-item delete-project" href="javascript:void(0);" data-project-id="<?php echo esc_attr($project_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('puzzling_delete_project_' . $project_id)); ?>"><i class="ri-delete-bin-line me-1 align-middle d-inline-block"></i>حذف</a></li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="fs-15 fw-medium mb-2">توضیحات پروژه :</div>
                <p class="text-muted mb-4"><?php echo $project->post_content ? wp_kses_post($project->post_content) : 'توضیحاتی برای این پروژه ثبت نشده است.'; ?></p>
                <div class="d-flex gap-5 mb-4 flex-wrap">
                    <?php if ($start_date): ?>
                    <div class="d-flex align-items-center gap-2 me-3">
                        <span class="avatar avatar-md avatar-rounded me-1 bg-primary1-transparent"><i class="ri-calendar-event-line fs-18 lh-1 align-middle"></i></span>
                        <div>
                            <div class="fw-medium mb-0 task-title">تاریخ شروع</div>
                            <span class="fs-12 text-muted"><?php echo function_exists('jdate') ? jdate('Y/m/d', strtotime($start_date)) : date('Y/m/d', strtotime($start_date)); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($end_date): ?>
                    <div class="d-flex align-items-center gap-2 me-3">
                        <span class="avatar avatar-md avatar-rounded me-1 bg-primary2-transparent"><i class="ri-time-line fs-18 lh-1 align-middle"></i></span>
                        <div>
                            <div class="fw-medium mb-0 task-title">تاریخ پایان</div>
                            <span class="fs-12 text-muted"><?php echo function_exists('jdate') ? jdate('Y/m/d', strtotime($end_date)) : date('Y/m/d', strtotime($end_date)); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($project_manager): ?>
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-md p-1 avatar-rounded me-1 bg-primary-transparent">
                            <?php echo get_avatar($project_manager->ID, 32); ?>
                        </span>
                        <div>
                            <span class="d-block fs-14 fw-medium"><?php echo esc_html($project_manager->display_name); ?></span>
                            <span class="fs-12 text-muted">مدیر پروژه</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="fs-15 fw-medium mb-2">کارهای کلیدی :</div>
                            <ul class="task-details-key-tasks mb-0">
                                <?php if (!empty($key_tasks)): ?>
                                    <?php foreach ($key_tasks as $task): ?>
                                        <li><?php echo esc_html($task->post_title); ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="text-muted">هیچ کاری ثبت نشده است.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="col-xl-6">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="fs-15 fw-medium">سایر کارها:</div>
                                <a href="<?php echo esc_url(add_query_arg(['view' => 'tasks', 'project' => $project_id], $dashboard_url)); ?>" class="btn btn-primary-light btn-wave btn-sm waves-effect waves-light">مشاهده بیشتر</a>
                            </div>
                            <ul class="list-group">
                                <?php if (!empty($other_tasks)): ?>
                                    <?php foreach (array_slice($other_tasks, 0, 3) as $task): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2"><i class="ri-link fs-15 text-secondary lh-1 p-1 bg-secondary-transparent rounded-circle"></i></div>
                                                <div class="fw-medium"><?php echo esc_html($task->post_title); ?></div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-muted">هیچ کار دیگری ثبت نشده است.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php if (!empty($project_tags)): ?>
                <div class="fs-15 fw-medium mb-2">مهارت‌ها :</div>
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($project_tags as $tag): ?>
                        <span class="badge bg-light text-default border"><?php echo esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div class="d-flex gap-3 align-items-center">
                        <span class="fs-12">اختصاص یافته به :</span>
                        <div class="avatar-list-stacked">
                            <?php 
                            $displayed_count = 0;
                            foreach ($assigned_members as $member_id): 
                                if ($displayed_count >= 5) break;
                                $member = get_userdata($member_id);
                                if (!$member) continue;
                                $avatar_url = get_avatar_url($member_id, ['size' => 32]);
                            ?>
                                <span class="avatar avatar-sm avatar-rounded" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?php echo esc_attr($member->display_name); ?>">
                                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($member->display_name); ?>">
                                </span>
                            <?php 
                                $displayed_count++;
                            endforeach; 
                            $remaining = count($assigned_members) - $displayed_count;
                            if ($remaining > 0):
                            ?>
                                <span class="avatar avatar-sm bg-primary avatar-rounded text-fixed-white" data-bs-toggle="tooltip" data-bs-original-title="<?php echo esc_attr($remaining); ?> نفر دیگر">
                                    +<?php echo esc_html($remaining); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (empty($assigned_members)): ?>
                                <span class="text-muted fs-12">هیچ کس اختصاص نیافته</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-3 align-items-center">
                        <span class="fs-12">وضعیت:</span>
                        <span class="d-block"><span class="badge bg-<?php echo $status_slug === 'active' ? 'info' : ($status_slug === 'completed' ? 'success' : ($status_slug === 'on-hold' ? 'warning' : 'secondary')); ?>"><?php echo esc_html($status_name); ?></span></span>
                    </div>
                    <div class="d-flex gap-3 align-items-center">
                        <span class="fs-12">اولویت:</span>
                        <span class="d-block fs-14 fw-medium"><span class="badge <?php echo esc_attr($priority_badge_class); ?>"><?php echo esc_html($priority_name); ?></span></span>
                    </div>
                </div>
            </div>                            
        </div>

        <div class="card custom-card overflow-hidden">
            <div class="card-header justify-content-between">
                <div class="card-title">کارهای برای انجام</div>
                <?php if ($is_manager): ?>
                <div class="btn btn-sm btn-primary-light btn-wave"><i class="ri-add-line align-middle me-1 fw-medium"></i>افزودن کار</div>
                <?php endif; ?>
            </div>
            <div class="card-body p-0 position-relative" id="todo-content">
                <div>
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            <thead>
                                <tr>
                                    <?php if ($is_manager): ?>
                                    <th>
                                        <input class="form-check-input check-all" type="checkbox" id="all-tasks" value="" aria-label="...">
                                    </th>
                                    <?php endif; ?>
                                    <th scope="col">عنوان کار</th>
                                    <th scope="col">وضعیت</th>
                                    <th scope="col">تاریخ پایان</th>
                                    <th scope="col">عملیات</th>
                                </tr>
                            </thead>
                            <tbody id="todo-drag">
                                <?php if (!empty($project_tasks)): ?>
                                    <?php foreach ($project_tasks as $task): 
                                        $task_status_terms = wp_get_post_terms($task->ID, 'task_status');
                                        $task_status_slug = !empty($task_status_terms) ? $task_status_terms[0]->slug : 'todo';
                                        $task_status_name = !empty($task_status_terms) ? esc_html($task_status_terms[0]->name) : 'شروع نشده';
                                        $due_date = get_post_meta($task->ID, '_due_date', true);
                                        
                                        $status_colors = [
                                            'done' => 'text-success',
                                            'in-progress' => 'text-primary',
                                            'todo' => 'text-primary2',
                                            'review' => 'text-warning'
                                        ];
                                        $status_color = $status_colors[$task_status_slug] ?? 'text-primary2';
                                    ?>
                                    <tr class="todo-box">
                                        <?php if ($is_manager): ?>
                                        <td class="task-checkbox">
                                            <input class="form-check-input" type="checkbox" value="" aria-label="..." <?php echo $task_status_slug === 'done' ? 'checked' : ''; ?>>
                                        </td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="fw-medium"><?php echo esc_html($task->post_title); ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-medium <?php echo esc_attr($status_color); ?> fs-12"><i class="ri-circle-line fw-semibold fs-7 me-2 lh-1 align-middle"></i><?php echo esc_html($task_status_name); ?></span>
                                        </td>
                                        <td>
                                            <?php echo $due_date ? (function_exists('jdate') ? jdate('Y/m/d', strtotime($due_date)) : date('Y/m/d', strtotime($due_date))) : '---'; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex gap-2">
                                                <a href="<?php echo esc_url(add_query_arg(['view' => 'tasks', 'task_id' => $task->ID], $dashboard_url)); ?>" class="btn btn-icon btn-sm btn-info-light btn-wave waves-effect waves-light">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <?php if ($is_manager): ?>
                                                <a href="<?php echo esc_url(add_query_arg(['view' => 'tasks', 'action' => 'edit', 'task_id' => $task->ID], $dashboard_url)); ?>" class="btn btn-icon btn-sm btn-info-light btn-wave waves-effect waves-light">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-danger-light btn-wave waves-effect waves-light delete-task" data-task-id="<?php echo esc_attr($task->ID); ?>">
                                                    <i class="ri-delete-bin-line"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo $is_manager ? '5' : '4'; ?>" class="text-center py-5">
                                            <div class="text-muted">هیچ کاری برای این پروژه ثبت نشده است.</div>
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
    <div class="col-xxl-4">
        <div class="card custom-card justify-content-between">
            <div class="card-header">
                <div class="card-title">بحث‌های پروژه</div>
            </div>
            <div class="card-body">
                <ul class="list-unstyled profile-timeline">
                    <li>
                        <div>
                            <span class="avatar avatar-sm shadow-sm bg-primary avatar-rounded profile-timeline-avatar">
                                <?php echo esc_html(mb_substr($project->post_title, 0, 1)); ?>
                            </span>
                            <div class="mb-2 d-flex align-items-start gap-2">
                                <div>
                                    <span class="fw-medium">شروع پروژه</span>
                                </div>
                                <span class="ms-auto bg-light text-muted badge"><?php echo function_exists('jdate') ? jdate('Y/m/d - H:i', strtotime($project->post_date)) : date('Y/m/d - H:i', strtotime($project->post_date)); ?></span>
                            </div>
                            <p class="text-muted mb-0">
                                پروژه "<?php echo esc_html($project->post_title); ?>" ایجاد شد.
                            </p>
                        </div>
                    </li>
                    <?php if ($project->post_modified !== $project->post_date): ?>
                    <li>
                        <div>
                            <span class="avatar avatar-sm shadow-sm bg-primary2 avatar-rounded profile-timeline-avatar">
                                <i class="ri-edit-line"></i>
                            </span>
                            <div class="mb-2 d-flex align-items-start gap-2">
                                <div>
                                    <span class="fw-medium">به‌روزرسانی پروژه</span>
                                </div>
                                <span class="ms-auto bg-light text-muted badge"><?php echo function_exists('jdate') ? jdate('Y/m/d - H:i', strtotime($project->post_modified)) : date('Y/m/d - H:i', strtotime($project->post_modified)); ?></span>
                            </div>
                            <p class="text-muted mb-0">
                                آخرین تغییرات در پروژه اعمال شد.
                            </p>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if ($completed_tasks > 0): ?>
                    <li>
                        <div>
                            <span class="avatar avatar-sm shadow-sm bg-success avatar-rounded profile-timeline-avatar">
                                <i class="ri-check-line"></i>
                            </span>
                            <div class="mb-2 d-flex align-items-start gap-2">
                                <div>
                                    <span class="fw-medium">پیشرفت پروژه</span>
                                </div>
                                <span class="ms-auto bg-light text-muted badge"><?php echo function_exists('jdate') ? jdate('Y/m/d', current_time('timestamp')) : date('Y/m/d'); ?></span>
                            </div>
                            <p class="text-muted mb-0">
                                <?php echo esc_html($completed_tasks); ?> از <?php echo esc_html($total_tasks); ?> کار تکمیل شده است (<?php echo esc_html($completion_percentage); ?>%).
                            </p>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-footer">
                <div class="d-sm-flex align-items-center lh-1">
                    <div class="me-sm-2 mb-2 mb-sm-0 p-1 rounded-circle bg-primary-transparent d-inline-block">
                        <?php echo get_avatar($current_user_id, 32, '', '', ['class' => 'avatar avatar-sm avatar-rounded']); ?>
                    </div>
                    <div class="flex-fill">
                        <div class="input-group flex-nowrap">
                            <input type="text" class="form-control w-sm-50 border shadow-none" placeholder="افکار خود را به اشتراک بگذارید" aria-label="Comment">
                            <button class="btn btn-primary-light btn-wave waves-effect waves-light" type="button"><i class="bi bi-emoji-smile"></i></button>
                            <button class="btn btn-primary-light btn-wave waves-effect waves-light" type="button"><i class="bi bi-paperclip"></i></button>
                            <button class="btn btn-primary-light btn-wave waves-effect waves-light" type="button"><i class="bi bi-camera"></i></button>
                            <button class="btn btn-primary btn-wave waves-effect waves-light text-nowrap" type="button">ارسال</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card custom-card overflow-hidden">
            <div class="card-header justify-content-between">
                <div class="card-title">اسناد پروژه</div>
                <div class="dropdown">
                    <div class="btn btn-light btn-full btn-sm" data-bs-toggle="dropdown">مشاهده همه<i class="ti ti-chevron-down ms-1"></i></div>
                    <ul class="dropdown-menu" role="menu">
                        <li><a class="dropdown-item" href="javascript:void(0);">دانلود</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">وارد کردن</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">صادرات</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php
                    // Get project attachments
                    $attachments = get_attached_media('', $project_id);
                    if (!empty($attachments)):
                        foreach (array_slice($attachments, 0, 4) as $attachment):
                            $file_url = wp_get_attachment_url($attachment->ID);
                            $file_size = size_format(filesize(get_attached_file($attachment->ID)), 2);
                            $file_type = wp_check_filetype($file_url);
                    ?>
                    <li class="list-group-item">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <span class="avatar avatar-md avatar-rounded p-2 bg-light lh-1">
                                <i class="ri-file-<?php echo $file_type['ext'] === 'pdf' ? 'pdf' : ($file_type['ext'] === 'doc' || $file_type['ext'] === 'docx' ? 'word' : 'line'); ?>-line fs-18"></i>
                            </span>
                            <div class="flex-fill">
                                <a href="<?php echo esc_url($file_url); ?>" target="_blank"><span class="d-block fw-medium"><?php echo esc_html($attachment->post_title); ?></span></a>
                                <span class="d-block text-muted fs-12 fw-normal"><?php echo esc_html($file_size); ?></span>
                            </div>
                            <div class="btn-list">
                                <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="btn btn-sm btn-icon btn-info-light btn-wave"><i class="ri-download-line"></i></a>
                                <?php if ($is_manager): ?>
                                <button class="btn btn-sm btn-icon btn-danger-light btn-wave delete-attachment" data-attachment-id="<?php echo esc_attr($attachment->ID); ?>"><i class="ri-delete-bin-line"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <li class="list-group-item text-center py-4">
                        <div class="text-muted">هیچ سندی برای این پروژه ثبت نشده است.</div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<!--End::row-1 -->

<style>
/* Single Project View Styles */
.task-details-key-tasks {
    list-style: none;
    padding: 0;
    margin: 0;
}

.task-details-key-tasks li {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--default-border);
    position: relative;
    padding-right: 1.5rem;
}

.task-details-key-tasks li:last-child {
    border-bottom: none;
}

.task-details-key-tasks li:before {
    content: "•";
    position: absolute;
    right: 0;
    color: var(--primary-color);
    font-weight: bold;
}

.profile-timeline {
    position: relative;
    padding: 0;
    margin: 0;
}

.profile-timeline li {
    position: relative;
    padding-bottom: 1.5rem;
    padding-right: 2.5rem;
}

.profile-timeline li:not(:last-child):before {
    content: "";
    position: absolute;
    right: 0.75rem;
    top: 2rem;
    bottom: -1.5rem;
    width: 2px;
    background: var(--default-border);
}

.profile-timeline-avatar {
    position: absolute;
    right: 0;
    top: 0;
}

.profile-activity-media {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.profile-activity-media img {
    max-width: 100px;
    max-height: 100px;
    border-radius: 0.25rem;
    object-fit: cover;
}

@media (max-width: 1200px) {
    .col-xxl-8, .col-xxl-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>
