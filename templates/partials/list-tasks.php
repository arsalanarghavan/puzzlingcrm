<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

// --- Get Data for Filters ---
$staff_roles = ['system_manager', 'finance_manager', 'team_member', 'administrator'];
$all_staff = get_users(['role__in' => $staff_roles, 'orderby' => 'display_name']);
$all_projects = get_posts(['post_type' => 'project', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']);
$task_statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false]);
$priorities = get_terms(['taxonomy' => 'task_priority', 'hide_empty' => false]);

// --- Handle Filtering ---
$project_filter = isset($_GET['project_filter']) ? intval($_GET['project_filter']) : 0;
$staff_filter = isset($_GET['staff_filter']) ? intval($_GET['staff_filter']) : 0;
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
?>
<div class="pzl-dashboard-section">
    <div class="pzl-tasks-header">
        <h3><i class="fas fa-tasks"></i> مدیریت وظایف (نمای کانبان)</h3>
        <div class="pzl-tasks-filters">
            <form method="get" class="pzl-form">
                <input type="hidden" name="view" value="tasks">
                <div class="pzl-form-row">
                    <div class="form-group">
                        <input type="search" name="s" placeholder="جستجوی عنوان وظیفه..." value="<?php echo esc_attr($search_query); ?>">
                    </div>
                    <div class="form-group">
                        <select name="project_filter">
                            <option value="">همه پروژه‌ها</option>
                            <?php foreach ($all_projects as $project) { echo '<option value="' . esc_attr($project->ID) . '" ' . selected($project_filter, $project->ID, false) . '>' . esc_html($project->post_title) . '</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="staff_filter">
                            <option value="">همه کارکنان</option>
                             <?php foreach ($all_staff as $staff) { echo '<option value="' . esc_attr($staff->ID) . '" ' . selected($staff_filter, $staff->ID, false) . '>' . esc_html($staff->display_name) . '</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="pzl-button">فیلتر</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="pzl-kanban-board">
        <?php foreach ($task_statuses as $status): ?>
            <div class="kanban-column" data-status-slug="<?php echo esc_attr($status->slug); ?>">
                <h4 class="kanban-column-title"><?php echo esc_html($status->name); ?></h4>
                <ul class="kanban-task-list">
                    <?php
                    $args = [
                        'post_type' => 'task',
                        'posts_per_page' => -1,
                        'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => $status->slug]],
                        'meta_query' => ['relation' => 'AND'],
                    ];
                    if ($project_filter > 0) { $args['meta_query'][] = ['key' => '_project_id', 'value' => $project_filter]; }
                    if ($staff_filter > 0) { $args['meta_query'][] = ['key' => '_assigned_to', 'value' => $staff_filter]; }
                    if (!empty($search_query)) { $args['s'] = $search_query; }
                    
                    $tasks_query = new WP_Query($args);

                    if ($tasks_query->have_posts()):
                        while($tasks_query->have_posts()): $tasks_query->the_post();
                            echo puzzling_render_task_card(get_post());
                        endwhile;
                    endif;
                    wp_reset_postdata();
                    ?>
                </ul>
                <div class="add-card-controls">
                    <button class="add-card-btn"><i class="fas fa-plus"></i> افزودن کارت</button>
                    <div class="add-card-form" style="display: none;">
                        <textarea placeholder="عنوان کارت را وارد کنید..."></textarea>
                        <div class="add-card-actions">
                            <button class="pzl-button pzl-button-sm submit-add-card">افزودن</button>
                            <button class="pzl-button pzl-button-sm cancel-add-card" type="button">&times;</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="task-detail-modal" class="pzl-modal-overlay" style="display: none;">
    <div class="pzl-modal-content">
        <button class="pzl-modal-close">&times;</button>
        <div id="task-modal-body">
            <div class="pzl-loader"></div>
        </div>
    </div>
</div>