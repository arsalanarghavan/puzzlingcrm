<?php
/**
 * Tasks Management Page (Xintra Clean Style)
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$user_roles = (array)$current_user->roles;

$is_manager = in_array('administrator', $user_roles) || in_array('system_manager', $user_roles);
$is_team_member = in_array('team_member', $user_roles);
$is_customer = in_array('customer', $user_roles);

if (!current_user_can('edit_tasks') && !$is_customer) {
    echo '<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>شما دسترسی لازم برای مشاهده این بخش را ندارید.</div>';
    return;
}

// Get active view
$active_view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'board';

// Pre-fetch data
$all_staff = $is_manager ? get_users(['role__in' => ['system_manager', 'team_member', 'administrator'], 'orderby' => 'display_name']) : [];
$all_projects = $is_manager ? get_posts(['post_type' => 'project', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']) : [];
$all_statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false, 'orderby' => 'term_order', 'order' => 'ASC']);
$priorities = get_terms(['taxonomy' => 'task_priority', 'hide_empty' => false]);
$labels = get_terms(['taxonomy' => 'task_label', 'hide_empty' => false]);

// Calculate Stats
$total_projects = $is_manager ? wp_count_posts('project')->publish : 0;
$total_tasks = wp_count_posts('task')->publish;
$active_tasks = $total_tasks;
$completed_tasks = 0;

if (!empty($all_statuses)) {
    foreach ($all_statuses as $status) {
        if ($status->slug === 'done') {
            $completed_tasks = $status->count;
            $active_tasks = $total_tasks - $completed_tasks;
        }
    }
}
?>

<!-- Stats Cards -->
<div class="row mb-4">
    <?php if ($is_manager): ?>
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
                        <p class="text-muted mb-0">کل پروژه‌ها</p>
                        <h4 class="fw-semibold mt-1"><?php echo esc_html($total_projects); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-secondary">
                            <i class="ri-task-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <p class="text-muted mb-0">کل وظایف</p>
                        <h4 class="fw-semibold mt-1"><?php echo esc_html($total_tasks); ?></h4>
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
                            <i class="ri-checkbox-circle-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <p class="text-muted mb-0">وظایف فعال</p>
                        <h4 class="fw-semibold mt-1"><?php echo esc_html($active_tasks); ?></h4>
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
                            <i class="ri-check-double-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <p class="text-muted mb-0">تکمیل‌شده</p>
                        <h4 class="fw-semibold mt-1"><?php echo esc_html($completed_tasks); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Views Navigation -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo $active_view === 'board' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('view', 'board', remove_query_arg(['paged']))); ?>">
            <i class="ri-layout-grid-line me-1"></i> نمای بُرد
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo $active_view === 'list' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('view', 'list', remove_query_arg(['paged']))); ?>">
            <i class="ri-list-check me-1"></i> نمای لیست
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo $active_view === 'calendar' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('view', 'calendar', remove_query_arg(['paged']))); ?>">
            <i class="ri-calendar-line me-1"></i> نمای تقویم
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo $active_view === 'timeline' ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('view', 'timeline', remove_query_arg(['paged']))); ?>">
            <i class="ri-time-line me-1"></i> نمای تایم‌لاین
        </a>
    </li>
    <li class="nav-item ms-auto">
        <div class="btn-group">
            <button class="btn btn-primary btn-sm" id="btn-new-task">
                <i class="ri-add-line me-1"></i> افزودن وظیفه
            </button>
            <button type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" id="btn-create-from-template"><i class="ri-file-copy-line me-2"></i>ایجاد از قالب</a></li>
                <li><a class="dropdown-item" href="<?php echo admin_url('admin.php?page=puzzling-task-templates'); ?>"><i class="ri-settings-3-line me-2"></i>مدیریت قالب‌ها</a></li>
            </ul>
        </div>
    </li>
</ul>

<!-- Filters Card -->
<div class="card custom-card mb-4">
    <div class="card-header">
        <div class="card-title">
            <i class="ri-filter-3-line me-2"></i> فیلترها
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3">
            <input type="hidden" name="view" value="<?php echo esc_attr($active_view); ?>">
            
            <div class="col-md-3">
                <label class="form-label">جستجو</label>
                <input type="text" name="s" class="form-control" placeholder="عنوان وظیفه..." value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
            </div>
            
            <?php if ($is_manager && !empty($all_projects)): ?>
            <div class="col-md-3">
                <label class="form-label">پروژه</label>
                <select name="project_filter" class="form-select">
                    <option value="">همه پروژه‌ها</option>
                    <?php
                    $project_filter = isset($_GET['project_filter']) ? intval($_GET['project_filter']) : 0;
                    foreach ($all_projects as $project) {
                        echo '<option value="' . esc_attr($project->ID) . '" ' . selected($project_filter, $project->ID, false) . '>' . esc_html($project->post_title) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($is_manager && !empty($all_staff)): ?>
            <div class="col-md-3">
                <label class="form-label">کارمند</label>
                <select name="staff_filter" class="form-select">
                    <option value="">همه</option>
                    <?php
                    $staff_filter = isset($_GET['staff_filter']) ? intval($_GET['staff_filter']) : 0;
                    foreach ($all_staff as $staff) {
                        echo '<option value="' . esc_attr($staff->ID) . '" ' . selected($staff_filter, $staff->ID, false) . '>' . esc_html($staff->display_name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($priorities)): ?>
            <div class="col-md-2">
                <label class="form-label">اولویت</label>
                <select name="priority_filter" class="form-select">
                    <option value="">همه</option>
                    <?php
                    $priority_filter = isset($_GET['priority_filter']) ? sanitize_key($_GET['priority_filter']) : '';
                    foreach ($priorities as $priority) {
                        echo '<option value="' . esc_attr($priority->slug) . '" ' . selected($priority_filter, $priority->slug, false) . '>' . esc_html($priority->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($labels)): ?>
            <div class="col-md-2">
                <label class="form-label">برچسب</label>
                <select name="label_filter" class="form-select">
                    <option value="">همه</option>
                    <?php
                    $label_filter = isset($_GET['label_filter']) ? sanitize_key($_GET['label_filter']) : '';
                    foreach ($labels as $label) {
                        echo '<option value="' . esc_attr($label->slug) . '" ' . selected($label_filter, $label->slug, false) . '>' . esc_html($label->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ri-filter-3-line"></i> اعمال فیلتر
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Main Content Based on View -->
<?php
// Build query args
$query_args = ['post_type' => 'task', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC'];

// Apply filters
if (!empty($search_query)) {
    $query_args['s'] = $search_query;
}

if ($project_filter > 0) {
    $query_args['meta_query'][] = ['key' => '_project_id', 'value' => $project_filter];
}

if ($staff_filter > 0) {
    $query_args['meta_query'][] = ['key' => '_assigned_to', 'value' => $staff_filter];
}

if (!empty($priority_filter)) {
    $query_args['tax_query'][] = ['taxonomy' => 'task_priority', 'field' => 'slug', 'terms' => $priority_filter];
}

if (!empty($label_filter)) {
    $query_args['tax_query'][] = ['taxonomy' => 'task_label', 'field' => 'slug', 'terms' => $label_filter];
}

$tasks_query = new WP_Query($query_args);

if ($active_view === 'board'): 
    // Kanban Board View
    ?>
    <div class="pzl-kanban-board">
        <?php foreach ($all_statuses as $status): ?>
        <div class="pzl-kanban-column">
            <div class="pzl-kanban-column-header">
                <span><?php echo esc_html($status->name); ?></span>
                <span class="count"><?php echo esc_html($status->count); ?></span>
            </div>
            <div class="pzl-task-list" data-status="<?php echo esc_attr($status->slug); ?>">
                <?php
                $status_tasks = new WP_Query(array_merge($query_args, [
                    'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => $status->slug]]
                ]));
                
                if ($status_tasks->have_posts()):
                    while ($status_tasks->have_posts()): $status_tasks->the_post();
                        $task_id = get_the_ID();
                        $due_date = get_post_meta($task_id, '_due_date', true);
                        $project_id = get_post_meta($task_id, '_project_id', true);
                        $assigned_to = get_post_meta($task_id, '_assigned_to', true);
                        ?>
                        <div class="pzl-task-item" data-task-id="<?php echo esc_attr($task_id); ?>" style="cursor: pointer;">
                            <div class="pzl-task-title"><?php the_title(); ?></div>
                            <div class="pzl-task-meta">
                                <?php if ($due_date): ?>
                                <span><i class="ri-calendar-line"></i> <?php echo jdate('Y/m/d', strtotime($due_date)); ?></span>
                                <?php endif; ?>
                                <?php if ($project_id): ?>
                                <span><i class="ri-folder-line"></i> <?php echo esc_html(get_the_title($project_id)); ?></span>
                                <?php endif; ?>
                                <?php if ($assigned_to): ?>
                                <span><?php echo get_avatar($assigned_to, 20, '', '', ['class' => 'rounded-circle']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    endwhile;
                endif;
                wp_reset_postdata();
                ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
<?php elseif ($active_view === 'list'): 
    // List View
    ?>
    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap">
                    <thead>
                        <tr>
                            <th>عنوان</th>
                            <th>وضعیت</th>
                            <th>اولویت</th>
                            <th>مسئول</th>
                            <th>سررسید</th>
                            <th>پروژه</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($tasks_query->have_posts()):
                            while ($tasks_query->have_posts()): $tasks_query->the_post();
                                $task_id = get_the_ID();
                                $status_terms = get_the_terms($task_id, 'task_status');
                                $priority_terms = get_the_terms($task_id, 'task_priority');
                                $due_date = get_post_meta($task_id, '_due_date', true);
                                $project_id = get_post_meta($task_id, '_project_id', true);
                                $assigned_to = get_post_meta($task_id, '_assigned_to', true);
                                
                                $status_name = $status_terms && !is_wp_error($status_terms) ? $status_terms[0]->name : '-';
                                $status_slug = $status_terms && !is_wp_error($status_terms) ? $status_terms[0]->slug : '';
                                $priority_name = $priority_terms && !is_wp_error($priority_terms) ? $priority_terms[0]->name : '-';
                                
                                $badge_class = 'bg-secondary';
                                if ($status_slug === 'done') $badge_class = 'bg-success';
                                elseif ($status_slug === 'in-progress') $badge_class = 'bg-primary';
                                elseif ($status_slug === 'review') $badge_class = 'bg-warning';
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php the_title(); ?></td>
                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo esc_html($status_name); ?></span></td>
                                    <td><?php echo esc_html($priority_name); ?></td>
                                    <td>
                                        <?php if ($assigned_to): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php echo get_avatar($assigned_to, 24); ?>
                                            <span><?php echo esc_html(get_userdata($assigned_to)->display_name); ?></span>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($due_date): ?>
                                        <span><i class="ri-calendar-line me-1"></i><?php echo jdate('Y/m/d', strtotime($due_date)); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $project_id ? esc_html(get_the_title($project_id)) : '-'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-icon btn-primary-light btn-task-view" data-task-id="<?php echo esc_attr($task_id); ?>" title="مشاهده">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <button class="btn btn-sm btn-icon btn-secondary-light btn-task-edit" data-task-id="<?php echo esc_attr($task_id); ?>" title="ویرایش">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            endwhile;
                        else:
                            ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="ri-task-line fs-3 mb-3 d-block opacity-3"></i>
                                    <p class="text-muted">هیچ وظیفه‌ای یافت نشد</p>
                                </td>
                            </tr>
                            <?php
                        endif;
                        wp_reset_postdata();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
<?php elseif ($active_view === 'calendar'): ?>
    <div class="card custom-card">
        <div class="card-body">
            <div id="task-calendar"></div>
        </div>
    </div>
    
<?php elseif ($active_view === 'timeline'): ?>
    <div class="card custom-card">
        <div class="card-body">
            <div id="task-timeline"></div>
        </div>
    </div>
    
<?php endif; ?>
