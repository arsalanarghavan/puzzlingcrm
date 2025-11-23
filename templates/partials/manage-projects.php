<?php
/**
 * Template for System Manager to Manage Projects (with Search, Filter, Edit, Delete) - Card View
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Allow access for managers and team members
$current_user = wp_get_current_user();
$is_manager = current_user_can('manage_options');
$is_team_member = in_array('team_member', (array)$current_user->roles);

if ( !$is_manager && !$is_team_member ) {
    return;
}

// Determine action
$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$project_to_edit = ($project_id > 0) ? get_post($project_id) : null;

// If project_id is set and action is 'view', show single project view
if ($project_id > 0 && $action === 'view') {
    // Set global for single-project.php
    global $puzzling_project_id;
    $puzzling_project_id = $project_id;
    include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/single-project.php';
    return;
}

// Robust check to ensure default project statuses exist.
$project_statuses = get_terms(['taxonomy' => 'project_status', 'hide_empty' => false]);
if (empty($project_statuses)) {
    PuzzlingCRM_CPT_Manager::create_default_terms();
    $project_statuses = get_terms(['taxonomy' => 'project_status', 'hide_empty' => false]);
}
?>

<div class="pzl-projects-manager-wrapper">

    <?php if (($action === 'new' || $action === 'edit') && $is_manager): ?>
        <?php
        $dashboard_url = puzzling_get_dashboard_url();
        $projects_list_url = add_query_arg('view', 'projects', $dashboard_url);
        ?>
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url($dashboard_url); ?>">برنامه‌ها</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo esc_url($projects_list_url); ?>">پروژه‌ها</a></li>
                        <li class="breadcrumb-item active" aria-current="page">ایجاد پروژه</li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0">ایجاد پروژه</h1>
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
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            ایجاد پروژه
                        </div>
            </div>
            <div class="card-body">
                        <form method="post" class="pzl-ajax-form" id="pzl-project-form" data-action="puzzling_manage_project" enctype="multipart/form-data">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="project_id" value="<?php echo esc_attr($project_id); ?>">

                            <div class="row gy-3">
                                <div class="col-xl-4">
                                    <label for="project_title" class="form-label">نام پروژه :</label>
                                    <input type="text" class="form-control" id="project_title" name="project_title" placeholder="نام پروژه را وارد کنید" value="<?php echo $project_to_edit ? esc_attr($project_to_edit->post_title) : ''; ?>" required>
                                </div>
                                
                                <?php
                                // Get project manager (if exists)
                                $project_manager_id = $project_to_edit ? get_post_meta($project_id, '_project_manager', true) : 0;
                                $project_manager = $project_manager_id ? get_userdata($project_manager_id) : null;
                                ?>
                                <div class="col-xl-4">
                                    <label for="project_manager" class="form-label">مدیر پروژه :</label>
                                    <select class="form-control" id="project_manager" name="project_manager" data-trigger="">
                                        <option value="">-- انتخاب مدیر پروژه --</option>
                                        <?php
                                        $managers = get_users(['role__in' => ['system_manager', 'administrator'], 'orderby' => 'display_name']);
                                        $project_manager_name = $project_manager ? $project_manager->display_name : '';
                                        foreach ($managers as $manager) {
                                            echo '<option value="' . esc_attr($manager->ID) . '" ' . selected($project_manager_id, $manager->ID, false) . '>' . esc_html($manager->display_name) . '</option>';
                                        }
                                        ?>
                                    </select>
                </div>
                
                                <?php
                                // Get current contract ID
                                $current_contract_id = 0;
                                if ($project_to_edit && $project_id > 0) {
                                    $current_contract_id = get_post_meta($project_id, '_contract_id', true);
                                }
                                ?>
                                <div class="col-xl-4">
                                    <label for="contract_id" class="form-label">مشتری / سهام‌دار :</label>
                                    <select class="form-control" id="contract_id" name="contract_id" data-trigger="" required>
                                        <option value="">-- انتخاب قرارداد --</option>
                                        <?php
                                        $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'DESC']);
                                        foreach ($contracts as $contract) {
                                            $customer = get_userdata($contract->post_author);
                                            $label = sprintf('%s (%s)', $contract->post_title, $customer ? $customer->display_name : '---');
                                            echo '<option value="' . esc_attr($contract->ID) . '" ' . selected($current_contract_id, $contract->ID, false) . '>' . esc_html($label) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="col-xl-12">
                                    <label class="form-label">توضیحات پروژه :</label>
                                    <div id="project-descriptioin-editor">
                                        <?php 
                                        $content = ($project_to_edit && $project_id > 0) ? $project_to_edit->post_content : '';
                                        // Ensure content is properly formatted for Quill
                                        if (!empty($content)) {
                                            echo wp_kses_post($content);
                                        } else {
                                            echo '<p><br></p>';
                                        }
                                        ?>
                                    </div>
                                    <textarea name="project_content" id="project_content" style="display: none;"><?php echo esc_textarea($content); ?></textarea>
                                </div>
                                
                                <?php
                                // Get dates from contract or project
                                $start_date = '';
                                $end_date = '';
                                
                                // First try to get dates from project
                                if ($project_to_edit && $project_id > 0) {
                                    $project_start = get_post_meta($project_id, '_project_start_date', true);
                                    $project_end = get_post_meta($project_id, '_project_end_date', true);
                                    if ($project_start) $start_date = $project_start;
                                    if ($project_end) $end_date = $project_end;
                                }
                                
                                // If project doesn't have dates, get from contract
                                if (empty($start_date) && $current_contract_id > 0) {
                                    $start_date = get_post_meta($current_contract_id, '_project_start_date', true);
                                    $end_date = get_post_meta($current_contract_id, '_project_end_date', true);
                                }
                                ?>
                                
                                <div class="col-xl-6">
                                    <label class="form-label">تاریخ شروع :</label>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i> </div>
                                            <input type="text" class="form-control pzl-jalali-date-picker" id="project_start_date" name="project_start_date" placeholder="تاریخ و زمان را انتخاب کنید" value="<?php echo $start_date ? (function_exists('jdate') ? jdate('Y/m/d', strtotime($start_date)) : date('Y/m/d', strtotime($start_date))) : ''; ?>">
                                        </div>
                                    </div>
                </div>

                                <div class="col-xl-6">
                                    <label class="form-label">تاریخ پایان :</label>
                <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-text text-muted"> <i class="ri-calendar-line"></i> </div>
                                            <input type="text" class="form-control pzl-jalali-date-picker" id="project_end_date" name="project_end_date" placeholder="تاریخ و زمان را انتخاب کنید" value="<?php echo $end_date ? (function_exists('jdate') ? jdate('Y/m/d', strtotime($end_date)) : date('Y/m/d', strtotime($end_date))) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-xl-6">
                                    <label class="form-label">وضعیت :</label>
                                    <select class="form-control" id="project_status" name="project_status" data-trigger="" required>
                                        <?php
                                        $current_status = [];
                                        $current_status_id = 0;
                                        if ($project_to_edit && $project_id > 0) {
                                            $current_status = wp_get_post_terms($project_id, 'project_status', ['fields' => 'ids']);
                                            $current_status_id = !empty($current_status) ? $current_status[0] : 0;
                                        }
                                        foreach ($project_statuses as $status) {
                                            $selected = '';
                                            if ($current_status_id == $status->term_id) {
                                                $selected = 'selected';
                                            } elseif (!$current_status_id && $status->slug === 'active') {
                                                $selected = 'selected';
                                            }
                                            echo '<option value="' . esc_attr($status->term_id) . '" ' . $selected . '>' . esc_html($status->name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="col-xl-6">
                                    <label class="form-label">اولویت :</label>
                                    <select class="form-control" id="project_priority" name="project_priority" data-trigger="">
                                        <?php
                                        $priority_map = ['high' => 'زیاد', 'medium' => 'متوسط', 'low' => 'کم'];
                                        $current_priority = 'medium';
                                        if ($project_to_edit && $project_id > 0) {
                                            $current_priority = get_post_meta($project_id, '_project_priority', true);
                                            if (empty($current_priority)) $current_priority = 'medium';
                                        }
                                        foreach ($priority_map as $key => $label) {
                                            echo '<option value="' . esc_attr($key) . '" ' . selected($current_priority, $key, false) . '>' . esc_html($label) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-xl-6">
                                    <label class="form-label">تخصیص به</label>
                                    <select class="form-control" name="assigned_team_members[]" id="assigned-team-members" multiple="">
                                        <?php
                                        $team_members = get_users(['role__in' => ['team_member', 'system_manager'], 'orderby' => 'display_name']);
                                        $assigned_members = [];
                                        if ($project_to_edit && $project_id > 0) {
                                            $assigned_members = get_post_meta($project_id, '_assigned_team_members', true);
                                            if (!is_array($assigned_members)) $assigned_members = [];
                                        }
                                        
                                        foreach ($team_members as $member) {
                                            $selected = in_array($member->ID, $assigned_members) ? 'selected' : '';
                                            echo '<option value="' . esc_attr($member->ID) . '" ' . $selected . '>' . esc_html($member->display_name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                 
                                <div class="col-xl-6">
                                    <label class="form-label">برچسب‌ها</label>
                                    <?php
                                    $project_tags = [];
                                    $tags_string = '';
                                    if ($project_to_edit && $project_id > 0) {
                                        $project_tags = wp_get_post_terms($project_id, 'project_tag', ['fields' => 'names']);
                                        $tags_string = !empty($project_tags) ? implode('، ', $project_tags) : '';
                                    }
                                    ?>
                                    <input class="form-control" id="choices-text-unique-values" type="text" value="<?php echo esc_attr($tags_string); ?>" placeholder="اینجا بنویسید">
                                    <input type="hidden" name="project_tags" id="project_tags" value="<?php echo esc_attr($tags_string); ?>">
                                </div>

                                <div class="col-xl-12">
                                    <label class="form-label">پیوست‌ها</label>
                                    <input type="file" class="multiple-filepond" name="filepond" multiple="" data-allow-reorder="true" data-max-file-size="3MB" data-max-files="6">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer">
                        <button type="submit" form="pzl-project-form" class="btn btn-primary-light btn-wave ms-auto float-end">ایجاد پروژه</button>
                    </div>
                </div>
            </div>
        </div>
        <!--End::row-1 -->

    <?php else: // 'list' view ?>
        <?php
        $dashboard_url = puzzling_get_dashboard_url();
        ?>
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2 mb-4">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url($dashboard_url); ?>">برنامه‌ها</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(add_query_arg('view', 'projects', $dashboard_url)); ?>">پروژه‌ها</a></li>
                        <li class="breadcrumb-item active" aria-current="page">لیست پروژه‌ها</li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0">لیست پروژه‌ها</h1>
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
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body p-3">
                        <form method="get" id="projects-filter-form" action="<?php echo esc_url($dashboard_url); ?>">
                <input type="hidden" name="view" value="projects">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex flex-wrap gap-1 project-list-main">
                                <?php if ($is_manager): ?>
                                <a href="<?php echo esc_url(add_query_arg(['view' => 'projects', 'action' => 'new'], $dashboard_url)); ?>" class="btn btn-primary me-2">
                                    <i class="ri-add-line me-1 fw-medium align-middle"></i>پروژه جدید
                                </a>
                                <?php endif; ?>
                                    <select class="form-control" name="sort_by" id="sort-by-projects" style="width: auto; min-width: 180px;" onchange="this.form.submit()">
                                        <option value="">مرتب‌سازی بر اساس</option>
                                        <option value="newest" <?php selected(isset($_GET['sort_by']) ? $_GET['sort_by'] : '', 'newest'); ?>>جدیدترین</option>
                                        <option value="date" <?php selected(isset($_GET['sort_by']) ? $_GET['sort_by'] : '', 'date'); ?>>تاریخ اضافه شدن</option>
                                        <option value="title" <?php selected(isset($_GET['sort_by']) ? $_GET['sort_by'] : '', 'title'); ?>>الف - ی</option>
                                    </select>
                                    <?php if ($is_manager): ?>
                                    <select name="customer_filter" class="form-control" style="width: auto; min-width: 150px;" onchange="this.form.submit()">
                                        <option value="">همه مشتریان</option>
                                        <?php 
                                        $all_customers = get_users(['role__in' => ['customer', 'subscriber'], 'orderby' => 'display_name']); 
                                        $current_customer = isset($_GET['customer_filter']) ? intval($_GET['customer_filter']) : 0; 
                                        foreach ($all_customers as $customer) { 
                                            echo '<option value="' . esc_attr($customer->ID) . '" ' . selected($current_customer, $customer->ID, false) . '>' . esc_html($customer->display_name) . '</option>'; 
                                        } 
                                        ?>
                                    </select>
                                    <?php endif; ?>
                                    <select name="status_filter" class="form-control" style="width: auto; min-width: 150px;" onchange="this.form.submit()">
                                        <option value="">همه وضعیت‌ها</option>
                                        <?php 
                                        $current_status = isset($_GET['status_filter']) ? sanitize_key($_GET['status_filter']) : ''; 
                                        foreach ($project_statuses as $status) { 
                                            echo '<option value="' . esc_attr($status->slug) . '" ' . selected($current_status, $status->slug, false) . '>' . esc_html($status->name) . '</option>'; 
                                        } 
                                        ?>
                                    </select>
                                </div>
                                <div class="d-flex" role="search">
                                    <input class="form-control me-2" type="search" name="s" placeholder="جستجوی پروژه" aria-label="Search" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                                    <button class="btn btn-light" type="submit">جستجو</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row-1 -->

        <!-- Start::row-2 -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card overflow-hidden">
            <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table text-nowrap">
                                <thead>
                                   <tr>
                                        <th scope="col">نام پروژه</th>
                                        <th scope="col">توضیحات</th>
                                        <th scope="col">تیم</th>
                                        <th scope="col">تاریخ واگذاری</th>
                                        <th scope="col">تاریخ سررسید</th>
                                        <th scope="col">وضعیت</th>
                                        <th scope="col">اولویت</th>
                                        <th scope="col">اقدامات</th>
                                    </tr>
                                </thead>
                                <tbody>
            <?php
                                    $dashboard_url = puzzling_get_dashboard_url();
                                    $base_page_url = add_query_arg('view', 'projects', $dashboard_url);
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;
            $args = ['post_type' => 'project', 'posts_per_page' => 12, 'paged' => $paged];
                                    
                                    // For team members, filter projects they have access to
                                    if ($is_team_member && !$is_manager) {
                                        // Get projects where team member is assigned
                                        $assigned_projects = [];
                                        $all_projects = get_posts(['post_type' => 'project', 'posts_per_page' => -1, 'post_status' => 'publish']);
                                        foreach ($all_projects as $proj) {
                                            $assigned_members = get_post_meta($proj->ID, '_assigned_team_members', true);
                                            if (is_array($assigned_members) && in_array($current_user->ID, $assigned_members)) {
                                                $assigned_projects[] = $proj->ID;
                                            } else {
                                                // Check if team member has tasks in this project
                                                $user_tasks = get_posts([
                                                    'post_type' => 'task',
                                                    'posts_per_page' => 1,
                                                    'meta_query' => [
                                                        ['key' => '_project_id', 'value' => $proj->ID, 'compare' => '='],
                                                        ['key' => '_assigned_to', 'value' => $current_user->ID, 'compare' => '=']
                                                    ]
                                                ]);
                                                if (!empty($user_tasks)) {
                                                    $assigned_projects[] = $proj->ID;
                                                }
                                            }
                                        }
                                        if (!empty($assigned_projects)) {
                                            $args['post__in'] = $assigned_projects;
                                        } else {
                                            $args['post__in'] = [0]; // No projects found
                                        }
                                    }
                                    
                                    // Search
            if (!empty($_GET['s'])) $args['s'] = sanitize_text_field($_GET['s']);
                                    
                                    // Customer filter (only for managers)
                                    if ($is_manager && !empty($_GET['customer_filter'])) {
                                        $args['author'] = intval($_GET['customer_filter']);
                                    }
                                    
                                    // Status filter
                                    if (!empty($_GET['status_filter'])) {
                                        if (!isset($args['tax_query'])) {
                                            $args['tax_query'] = [];
                                        }
                                        $args['tax_query'][] = ['taxonomy' => 'project_status', 'field' => 'slug', 'terms' => sanitize_key($_GET['status_filter'])];
                                    }
                                    
                                    // Sort
                                    if (!empty($_GET['sort_by'])) {
                                        $sort_by = sanitize_key($_GET['sort_by']);
                                        if ($sort_by === 'newest') {
                                            $args['orderby'] = 'date';
                                            $args['order'] = 'DESC';
                                        } elseif ($sort_by === 'date') {
                                            $args['orderby'] = 'date';
                                            $args['order'] = 'ASC';
                                        } elseif ($sort_by === 'title') {
                                            $args['orderby'] = 'title';
                                            $args['order'] = 'ASC';
                                        }
                                    }
                                    
            $projects_query = new WP_Query($args);

                                    if ($projects_query->have_posts()): 
                                        while($projects_query->have_posts()): $projects_query->the_post();
                        $project_id = get_the_ID();
                        $customer = get_userdata(get_the_author_meta('ID'));
                                            $edit_url = add_query_arg(['view' => 'projects', 'action' => 'edit', 'project_id' => $project_id], $dashboard_url);
                                            $view_url = add_query_arg(['view' => 'projects', 'action' => 'view', 'project_id' => $project_id], $dashboard_url);
                        $contract_id = get_post_meta($project_id, '_contract_id', true);
                        
                                            // Get project tasks for completion calculation
                                            $project_tasks = get_posts([
                                                'post_type' => 'task',
                                                'posts_per_page' => -1,
                                                'meta_query' => [
                                                    ['key' => '_project_id', 'value' => $project_id, 'compare' => '=']
                                                ]
                                            ]);
                                            
                                            $total_tasks = count($project_tasks);
                                            $completed_tasks = 0;
                                            $team_member_ids = [];
                                            
                                            foreach ($project_tasks as $task) {
                                                $task_status_terms = wp_get_post_terms($task->ID, 'task_status');
                                                if (!empty($task_status_terms) && $task_status_terms[0]->slug === 'done') {
                                                    $completed_tasks++;
                                                }
                                                
                                                // Collect team member IDs
                                                $assigned_to = get_post_meta($task->ID, '_assigned_to', true);
                                                if ($assigned_to && !in_array($assigned_to, $team_member_ids)) {
                                                    $team_member_ids[] = $assigned_to;
                                                }
                                            }
                                            
                                            $completion_percentage = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
                                            
                                            // Get team members
                                            $team_members = [];
                                            if (!empty($team_member_ids)) {
                                                $team_members = get_users(['include' => $team_member_ids, 'number' => 8]);
                                            }
                                            
                                            // Get project priority (from highest priority task, or default)
                                            $project_priority = 'متوسط'; // Default
                                            $priority_badges = [
                                                'high' => 'bg-danger-transparent',
                                                'medium' => 'bg-warning-transparent',
                                                'low' => 'bg-success-transparent'
                                            ];
                                            $priority_badge_class = 'bg-warning-transparent';
                                            
                                            // Get highest priority from tasks
                                            if (!empty($project_tasks)) {
                                                $highest_priority = 'low';
                                                foreach ($project_tasks as $task) {
                                                    $priority_terms = wp_get_post_terms($task->ID, 'task_priority');
                                                    if (!empty($priority_terms)) {
                                                        $priority_slug = $priority_terms[0]->slug;
                                                        $priority_order = ['low' => 1, 'medium' => 2, 'high' => 3];
                                                        if (($priority_order[$priority_slug] ?? 0) > ($priority_order[$highest_priority] ?? 0)) {
                                                            $highest_priority = $priority_slug;
                                                        }
                                                    }
                                                }
                                                $priority_map = ['high' => 'بالا', 'medium' => 'متوسط', 'low' => 'پایین'];
                                                $project_priority = $priority_map[$highest_priority] ?? 'متوسط';
                                                $priority_badge_class = $priority_badges[$highest_priority] ?? 'bg-warning-transparent';
                                            }
                                            
                                            // Dates
                                            $assignment_date = get_the_date('Y-m-d');
                        $end_date = $contract_id ? get_post_meta($contract_id, '_project_end_date', true) : '';

                                            // Status
                        $status_terms = get_the_terms($project_id, 'project_status');
                        $status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : '---';
                        $status_slug = !empty($status_terms) ? esc_attr($status_terms[0]->slug) : 'default';
                                            
                                            // Logo background color based on status
                                            $logo_bg_classes = [
                                                'active' => 'bg-info-transparent',
                                                'completed' => 'bg-success-transparent',
                                                'on-hold' => 'bg-warning-transparent',
                                                'cancelled' => 'bg-danger-transparent'
                                            ];
                                            $logo_bg_class = $logo_bg_classes[$status_slug] ?? 'bg-info-transparent';
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2">
                                                        <span class="avatar avatar-rounded p-1 <?php echo esc_attr($logo_bg_class); ?>">
                                                            <?php if (has_post_thumbnail()): ?>
                                                                <?php the_post_thumbnail('thumbnail', ['style' => 'width: 32px; height: 32px; object-fit: cover;']); ?>
                                                            <?php else: ?>
                                                                <span style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                                                    <?php echo esc_html(mb_substr(get_the_title(), 0, 1)); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <div class="flex-fill">
                                                        <a href="<?php echo esc_url($view_url); ?>" class="fw-medium fs-14 d-block text-truncate project-list-title"><?php the_title(); ?></a>
                                                        <span class="text-muted d-block fs-12">مجموع <span class="fw-medium text-default"><?php echo esc_html($completed_tasks); ?>/<?php echo esc_html($total_tasks); ?></span> وظیفه انجام شده</span>
                        </div>
                        </div>
                                            </td>
                                            <td>
                                                <p class="text-muted mb-0 project-list-description"><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 15)); ?></p>
                                            </td>
                                            <td>
                                                <div class="avatar-list-stacked">
                                                    <?php 
                                                    $displayed_count = 0;
                                                    foreach ($team_members as $member): 
                                                        if ($displayed_count >= 4) break;
                                                        $avatar_url = get_avatar_url($member->ID, ['size' => 32]);
                                                    ?>
                                                        <span class="avatar avatar-sm avatar-rounded">
                                                            <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($member->display_name); ?>">
                                                        </span>
                                                    <?php 
                                                        $displayed_count++;
                                                    endforeach; 
                                                    $remaining = count($team_members) - $displayed_count;
                                                    if ($remaining > 0):
                                                    ?>
                                                        <a class="avatar avatar-sm bg-primary avatar-rounded text-fixed-white" href="javascript:void(0);">
                                                            +<?php echo esc_html($remaining); ?>
                            </a>
                            <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo function_exists('jdate') ? jdate('Y/m/d', strtotime($assignment_date)) : date('Y/m/d', strtotime($assignment_date)); ?>
                                            </td>
                                            <td>
                                                <?php echo $end_date ? (function_exists('jdate') ? jdate('Y/m/d', strtotime($end_date)) : date('Y/m/d', strtotime($end_date))) : '---'; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="progress progress-xs progress-animate" role="progressbar" aria-valuenow="<?php echo esc_attr($completion_percentage); ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <div class="progress-bar bg-primary" style="width: <?php echo esc_attr($completion_percentage); ?>%"></div>
                                                    </div>
                                                    <div class="mt-1"><span class="text-primary fw-medium"><?php echo esc_html($completion_percentage); ?>%</span> تکمیل شد</div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo esc_attr($priority_badge_class); ?>"><?php echo esc_html($project_priority); ?></span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <a aria-label="anchor" href="javascript:void(0);" class="btn btn-icon btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fe fe-more-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="<?php echo esc_url($view_url); ?>"><i class="ri-eye-line align-middle me-1 d-inline-block"></i>مشاهده</a></li>
                                                        <?php if ($is_manager): ?>
                                                        <li><a class="dropdown-item" href="<?php echo esc_url($edit_url); ?>"><i class="ri-edit-line align-middle me-1 d-inline-block"></i>ویرایش</a></li>
                                                        <li><a class="dropdown-item delete-project" href="javascript:void(0);" data-project-id="<?php echo esc_attr($project_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('puzzling_delete_project_' . $project_id)); ?>"><i class="ri-delete-bin-line me-1 align-middle d-inline-block"></i>حذف</a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php 
                                        endwhile;
                                        wp_reset_postdata();
                                    else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="pzl-empty-state">
                                                    <i class="ri-error-warning-line fs-2 text-muted"></i>
                                                    <h4 class="mt-3">پروژه‌ای یافت نشد</h4>
                                                    <p class="text-muted">هیچ پروژه‌ای با این مشخصات یافت نشد. می‌توانید یک پروژه جدید ایجاد کنید.</p>
                                                </div>
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
        <!-- End::row-2 -->
        
        <?php 
        if (isset($projects_query) && $projects_query->max_num_pages > 1): 
            $pagination_base = add_query_arg([
                'view' => 'projects',
                's' => isset($_GET['s']) ? $_GET['s'] : '',
                'customer_filter' => isset($_GET['customer_filter']) ? $_GET['customer_filter'] : '',
                'status_filter' => isset($_GET['status_filter']) ? $_GET['status_filter'] : '',
                'sort_by' => isset($_GET['sort_by']) ? $_GET['sort_by'] : '',
            ], $dashboard_url);
            
            $pagination = paginate_links([
                'total' => $projects_query->max_num_pages,
                'current' => $paged,
                'format' => '?paged=%#%',
                'prev_text' => 'قبلی',
                'next_text' => 'بعدی',
                'type' => 'array',
                'base' => $pagination_base . '%_%',
                'add_args' => false
            ]);
            
            if ($pagination):
        ?>
        <ul class="pagination justify-content-end mt-3">
            <?php
                foreach ($pagination as $link):
                    $is_active = strpos($link, 'current') !== false;
                    $is_disabled = strpos($link, 'dots') !== false || strpos($link, 'prev') !== false && $paged == 1 || strpos($link, 'next') !== false && $paged == $projects_query->max_num_pages;
            ?>
                <li class="page-item <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>">
                    <?php 
                    // Convert paginate_links output to proper Bootstrap pagination format
                    if (strpos($link, 'current') !== false) {
                        echo str_replace(['<span class="page-numbers current">', '</span>'], ['<a class="page-link">', '</a>'], $link);
                    } elseif (strpos($link, 'dots') !== false) {
                        echo str_replace(['<span class="page-numbers dots">', '</span>'], ['<span class="page-link">', '</span>'], $link);
                    } else {
                        echo str_replace(['<a class="page-numbers"', '<a class="prev', '<a class="next'], ['<a class="page-link"', '<a class="page-link"', '<a class="page-link"'], $link);
                    }
                    ?>
                </li>
            <?php 
                endforeach;
            ?>
        </ul>
        <?php 
            endif;
        endif; 
        ?>
        
    <?php endif; ?>
</div>

<style>
/* Projects List Table Styles */
.project-list-title {
    color: var(--default-text-color);
    text-decoration: none;
    transition: color 0.2s;
}

.project-list-title:hover {
    color: var(--primary-color);
}

.project-list-description {
    max-width: 300px;
    line-height: 1.5;
}

.avatar-list-stacked {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.avatar-list-stacked .avatar {
    margin-left: -0.5rem;
    border: 2px solid #fff;
}

.avatar-list-stacked .avatar:first-child {
    margin-left: 0;
}

.progress-xs {
    height: 6px;
}

.progress-animate .progress-bar {
    transition: width 0.6s ease;
}

.table thead th {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--default-border);
}

.table tbody tr:hover {
    background-color: rgba(var(--primary-rgb), 0.02);
}

.page-header-breadcrumb {
    margin-bottom: 1.5rem;
}

.page-title {
    color: var(--default-text-color);
}

/* Project Form Styles */
.card-footer {
    border-top: 1px solid var(--default-border);
    padding: 1rem 1.5rem;
    background-color: var(--custom-white);
}

.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--default-text-color);
}

.input-group-text {
    background-color: var(--default-bg);
    border-color: var(--default-border);
    color: var(--default-text-color);
}

.form-control:focus,
.form-control:focus-visible {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
}

#assigned-team-members {
    min-height: 100px;
}

@media (max-width: 768px) {
    .project-list-main {
        flex-direction: column;
        width: 100%;
    }
    
    .project-list-main .form-control,
    .project-list-main .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .avatar-list-stacked {
        flex-wrap: wrap;
    }
    
    .card-footer .btn {
        width: 100%;
        float: none !important;
        margin-top: 1rem;
    }
}
</style>