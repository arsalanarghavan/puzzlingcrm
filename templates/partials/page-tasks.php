<?php
/**
 * Main Task Management Page Template - V3.4 (Board View & Filter Fix)
 * This template includes multiple views and auto-filters for team members.
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$is_team_member = in_array('team_member', (array)$current_user->roles);

if (!current_user_can('edit_tasks')) {
    echo '<p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p>';
    return;
}

// --- Get active tab and view options ---
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'board'; // Default to board view
$swimlane_by = isset($_GET['swimlane']) ? sanitize_key($_GET['swimlane']) : 'none'; // For swimlanes

// --- Pre-fetch data for filters and forms ---
$staff_roles = ['system_manager', 'finance_manager', 'team_member', 'administrator'];
$all_staff = get_users(['role__in' => $staff_roles, 'orderby' => 'display_name']);
$all_projects = get_posts(['post_type' => 'project', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']);
$all_statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false, 'orderby' => 'term_order', 'order' => 'ASC']);
$priorities = get_terms(['taxonomy' => 'task_priority', 'hide_empty' => false]);
$labels = get_terms(['taxonomy' => 'task_label', 'hide_empty' => true]);

?>
<div class="pzl-dashboard-section" id="pzl-task-manager-page">
    <h3><i class="fas fa-tasks"></i> مدیریت وظایف</h3>

    <div class="pzl-dashboard-tabs">
        <a href="<?php echo add_query_arg('tab', 'board', remove_query_arg(['tab', 's', 'project_id', 'staff_id', 'status', 'swimlane'])); ?>" class="pzl-tab <?php echo $active_tab === 'board' ? 'active' : ''; ?>"> <i class="fas fa-columns"></i> نمای بُرد</a>
        <a href="<?php echo add_query_arg('tab', 'list'); ?>" class="pzl-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>"> <i class="fas fa-list-ul"></i> نمای لیست</a>
        <a href="<?php echo add_query_arg('tab', 'calendar'); ?>" class="pzl-tab <?php echo $active_tab === 'calendar' ? 'active' : ''; ?>"> <i class="fas fa-calendar-alt"></i> نمای تقویم</a>
        <a href="<?php echo add_query_arg('tab', 'timeline'); ?>" class="pzl-tab <?php echo $active_tab === 'timeline' ? 'active' : ''; ?>"> <i class="fas fa-stream"></i> نمای تایم‌لاین</a>
        <?php if (!$is_team_member): // Hide from team members as they have a simpler form on their dashboard ?>
            <a href="<?php echo add_query_arg('tab', 'new'); ?>" class="pzl-tab <?php echo $active_tab === 'new' ? 'active' : ''; ?>"> <i class="fas fa-plus"></i> افزودن وظیفه جدید</a>
            <a href="<?php echo add_query_arg('tab', 'workflow'); ?>" class="pzl-tab <?php echo $active_tab === 'workflow' ? 'active' : ''; ?>"> <i class="fas fa-cogs"></i> مدیریت گردش‌کار</a>
        <?php endif; ?>
    </div>

    <div class="pzl-dashboard-tab-content">
    <?php 
    // Handle filtering, auto-applying staff filter for team members
    $project_filter = isset($_GET['project_filter']) ? intval($_GET['project_filter']) : 0;
    $staff_filter = $is_team_member ? $current_user->ID : (isset($_GET['staff_filter']) ? intval($_GET['staff_filter']) : 0);
    $priority_filter = isset($_GET['priority_filter']) ? sanitize_key($_GET['priority_filter']) : '';
    $label_filter = isset($_GET['label_filter']) ? sanitize_key($_GET['label_filter']) : '';
    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    if ($active_tab === 'new' && !$is_team_member): 
    ?>
        <div class="pzl-card">
            <?php
            // Additional data needed only for this form
            $all_tasks_for_parent = get_posts(['post_type' => 'task', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
            $task_templates = get_posts(['post_type' => 'pzl_task_template', 'numberposts' => -1]);
            ?>
            <div class="pzl-card-header">
                <h3><i class="fas fa-plus-circle"></i> افزودن وظیفه جدید</h3>
            </div>
            
            <form id="puzzling-add-task-form" class="pzl-form pzl-ajax-form" data-action="puzzling_add_task" enctype="multipart/form-data">
                
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                
                <div class="form-group">
                    <label for="template_id">استفاده از قالب:</label>
                    <select name="template_id" id="task-template-selector">
                        <option value="">-- بدون قالب --</option>
                        <?php foreach ($task_templates as $template) { echo '<option value="' . esc_attr($template->ID) . '">' . esc_html($template->post_title) . '</option>'; } ?>
                    </select>
                </div>
                <div class="pzl-form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="title">عنوان وظیفه</label>
                        <input type="text" name="title" placeholder="مثال: طراحی صفحه اصلی" required>
                    </div>
                    <div class="form-group">
                        <label for="project_id">برای پروژه</label>
                        <select name="project_id" required>
                            <option value="">-- انتخاب پروژه --</option>
                            <?php foreach ($all_projects as $project) { echo '<option value="' . esc_attr($project->ID) . '">' . esc_html($project->post_title) . '</option>'; } ?>
                        </select>
                    </div>
                </div>
                 <div class="form-group">
                    <label for="task_content">توضیحات وظیفه (اختیاری)</label>
                    <textarea name="content" rows="5" placeholder="جزئیات کامل وظیفه را در اینجا وارد کنید..."></textarea>
                </div>
                <div class="pzl-form-row">
                     <div class="form-group">
                        <label for="parent_id">وظیفه والد (برای ایجاد وظیفه فرعی)</label>
                        <select name="parent_id">
                            <option value="0">-- این یک وظیفه اصلی است --</option>
                            <?php foreach ($all_tasks_for_parent as $task) { echo '<option value="' . esc_attr($task->ID) . '">' . esc_html($task->post_title) . '</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="time_estimate">تخمین زمان (ساعت)</label>
                        <input type="number" name="time_estimate" min="0" step="0.5" placeholder="مثال: 8">
                    </div>
                     <div class="form-group">
                        <label for="story_points">امتیاز داستان (Story Points)</label>
                        <input type="number" name="story_points" min="0" placeholder="مثال: 5">
                    </div>
                </div>
                <div class="pzl-form-row">
                    <div class="form-group">
                        <label for="assigned_to">تخصیص به</label>
                        <select name="assigned_to" required>
                            <option value="">-- انتخاب کارمند --</option>
                            <?php foreach ($all_staff as $member) { echo '<option value="' . esc_attr($member->ID) . '">' . esc_html($member->display_name) . '</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="priority">اولویت</label>
                        <select name="priority" required>
                            <?php foreach ($priorities as $priority) { echo '<option value="' . esc_attr($priority->term_id) . '">' . esc_html($priority->name) . '</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="due_date">ددلاین</label>
                        <input type="date" name="due_date">
                    </div>
                </div>
                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="task_labels">برچسب‌ها</label>
                        <input type="text" name="task_labels" placeholder="برچسب‌ها را با کاما (,) جدا کنید">
                    </div>
                    <div class="form-group half-width">
                        <label for="task_cover_image">کاور وظیفه (اختیاری)</label>
                        <input type="file" name="task_cover_image" accept="image/*">
                    </div>
                </div>
                 <div class="form-group">
                    <label for="task_attachments">پیوست فایل‌ها</label>
                    <input type="file" name="task_attachments[]" multiple>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button">افزودن وظیفه</button>
                </div>
            </form>
        </div>
    <?php elseif($active_tab === 'list'): ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="fas fa-list-ul"></i> لیست پیشرفته وظایف</h3>
            </div>
            
            <?php if (!$is_team_member): // Bulk edit is only for admins ?>
            <div id="bulk-edit-container" class="pzl-card" style="display:none; margin-bottom: 20px; background: #f0f3f6;">
                <h4>ویرایش دسته‌جمعی</h4>
                <div class="pzl-form-row" style="align-items: flex-end;">
                    <div class="form-group">
                        <label>تغییر وضعیت به:</label>
                        <select id="bulk-status">
                            <option value="">-- انتخاب --</option>
                            <?php foreach($all_statuses as $status) { echo '<option value="'.$status->slug.'">'.$status->name.'</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>تخصیص به:</label>
                        <select id="bulk-assignee">
                            <option value="">-- انتخاب --</option>
                            <?php foreach($all_staff as $staff) { echo '<option value="'.$staff->ID.'">'.$staff->display_name.'</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>تغییر اولویت به:</label>
                        <select id="bulk-priority">
                            <option value="">-- انتخاب --</option>
                            <?php foreach($priorities as $p) { echo '<option value="'.$p->term_id.'">'.$p->name.'</option>'; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="button" id="apply-bulk-edit" class="pzl-button">اعمال</button>
                        <button type="button" id="cancel-bulk-edit" class="pzl-button-secondary" style="background: #6c757d !important;">انصراف</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php
            // Query for tasks list
            $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
            $args = ['post_type' => 'task', 'posts_per_page' => 20, 'paged' => $paged, 'meta_query' => ['relation' => 'AND'], 'tax_query' => ['relation' => 'AND']];
            if ($project_filter > 0) $args['meta_query'][] = ['key' => '_project_id', 'value' => $project_filter];
            if ($staff_filter > 0) $args['meta_query'][] = ['key' => '_assigned_to', 'value' => $staff_filter];
            if (!empty($priority_filter)) $args['tax_query'][] = ['taxonomy' => 'task_priority', 'field' => 'slug', 'terms' => $priority_filter];
            if (!empty($label_filter)) $args['tax_query'][] = ['taxonomy' => 'task_label', 'field' => 'slug', 'terms' => $label_filter];
            if (!empty($search_query)) $args['s'] = $search_query;
            $tasks_query = new WP_Query($args);
            ?>
            <table class="pzl-table" id="tasks-list-table">
                <thead><tr>
                    <?php if (!$is_team_member): ?><th><input type="checkbox" id="select-all-tasks"></th><?php endif; ?>
                    <th>عنوان وظیفه</th><th>پروژه</th><?php if (!$is_team_member): ?><th>تخصیص به</th><?php endif; ?><th>وضعیت</th><th>ددلاین</th>
                </tr></thead>
                <tbody>
                    <?php if ($tasks_query->have_posts()): while($tasks_query->have_posts()): $tasks_query->the_post();
                        $project_id = get_post_meta(get_the_ID(), '_project_id', true);
                        $assigned_id = get_post_meta(get_the_ID(), '_assigned_to', true);
                        $status_terms = get_the_terms(get_the_ID(), 'task_status');
                    ?>
                        <tr>
                            <?php if (!$is_team_member): ?><td><input type="checkbox" class="task-checkbox" value="<?php echo get_the_ID(); ?>"></td><?php endif; ?>
                            <td><a href="#" class="open-task-modal" data-task-id="<?php echo get_the_ID(); ?>"><?php the_title(); ?></a></td>
                            <td><?php echo $project_id ? get_the_title($project_id) : '---'; ?></td>
                            <?php if (!$is_team_member): ?><td><?php echo $assigned_id ? get_the_author_meta('display_name', $assigned_id) : '---'; ?></td><?php endif; ?>
                            <td><?php echo !empty($status_terms) ? esc_html($status_terms[0]->name) : '---'; ?></td>
                            <td><?php echo get_post_meta(get_the_ID(), '_due_date', true); ?></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6">هیچ وظیفه‌ای با این فیلترها یافت نشد.</td></tr>
                    <?php endif; wp_reset_postdata(); ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php echo paginate_links(['total' => $tasks_query->max_num_pages, 'current' => $paged]); ?>
            </div>
        </div>
    <?php elseif($active_tab === 'calendar'): ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="fas fa-calendar-alt"></i> نمای تقویم</h3>
            </div>
            <div id="pzl-task-calendar"></div>
        </div>
    <?php elseif($active_tab === 'timeline'): ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="fas fa-stream"></i> نمای تایم‌لاین / گانت</h3>
            </div>
             <div id="pzl-task-gantt" style='width:100%; height:600px;'></div>
        </div>
    <?php elseif ($active_tab === 'workflow' && !$is_team_member): ?>
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="fas fa-cogs"></i> مدیریت وضعیت‌های گردش‌کار</h3>
            </div>
            <p>در این بخش می‌توانید ستون‌های برد کانبان (وضعیت‌های وظایف) را مدیریت کنید.</p>
            <div id="workflow-status-manager">
                <ul id="status-sortable-list">
                    <?php
                    foreach ($all_statuses as $status) { // Use the pre-fetched variable
                        echo '<li data-term-id="' . esc_attr($status->term_id) . '"><i class="fas fa-grip-vertical"></i> ' . esc_html($status->name) . ' <span class="delete-status-btn" data-term-id="' . esc_attr($status->term_id) . '">&times;</span></li>';
                    }
                    ?>
                </ul>
                <form id="add-new-status-form" class="pzl-form-inline">
                    <?php wp_nonce_field('puzzling_add_new_status_nonce', 'security'); ?>
                    <input type="text" id="new-status-name" placeholder="نام وضعیت جدید" required>
                    <button type="submit" class="pzl-button">افزودن وضعیت</button>
                </form>
            </div>
        </div>

    <?php else: // Board View (Default) with Swimlanes ?>
        <div class="pzl-task-board-container">
            <div class="pzl-tasks-filters pzl-card" style="margin-bottom: 20px; padding: 20px;">
                <form method="get" class="pzl-form">
                     <input type="hidden" name="view" value="tasks">
                     <input type="hidden" name="tab" value="board">
                    <div class="pzl-form-row" style="align-items: flex-end;">
                        <?php if (!$is_team_member): // Swimlanes only for admins ?>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="swimlane-filter">گروه‌بندی افقی (Swimlane)</label>
                            <select name="swimlane" id="swimlane-filter" onchange="this.form.submit()">
                                <option value="none" <?php selected($swimlane_by, 'none'); ?>>هیچکدام</option>
                                <option value="assignee" <?php selected($swimlane_by, 'assignee'); ?>>بر اساس مسئول</option>
                                <option value="project" <?php selected($swimlane_by, 'project'); ?>>بر اساس پروژه</option>
                                <option value="priority" <?php selected($swimlane_by, 'priority'); ?>>بر اساس اولویت</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <?php
            $statuses = $all_statuses;
            
            if ($swimlane_by === 'none' || $is_team_member) {
                echo '<div id="pzl-task-board">';
                foreach ($statuses as $status) {
                    echo '<div class="pzl-task-column" data-status-slug="' . esc_attr($status->slug) . '">';
                    echo '<h4 class="pzl-column-header">' . esc_html($status->name) . '</h4>';
                    echo '<div class="pzl-task-list">';
                    
                    // **FIXED QUERY**: Added all filters to the board view query.
                    $tasks_args = [
                        'post_type' => 'task', 'posts_per_page' => -1, 'post_parent' => 0,
                        'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => $status->slug]],
                        'meta_query' => ['relation' => 'AND'],
                        'orderby' => 'menu_order date', 'order' => 'ASC',
                    ];
                    if ($project_filter > 0) { $tasks_args['meta_query'][] = ['key' => '_project_id', 'value' => $project_filter]; }
                    if ($staff_filter > 0) { $tasks_args['meta_query'][] = ['key' => '_assigned_to', 'value' => $staff_filter]; }
                    if (!empty($search_query)) { $tasks_args['s'] = $search_query; }
                    if (!empty($priority_filter)) { $tasks_args['tax_query'][] = ['taxonomy' => 'task_priority', 'field' => 'slug', 'terms' => $priority_filter]; }
                    if (!empty($label_filter)) { $tasks_args['tax_query'][] = ['taxonomy' => 'task_label', 'field' => 'slug', 'terms' => $label_filter]; }

                    $tasks_in_column = get_posts($tasks_args);
                    
                    foreach ($tasks_in_column as $task) echo puzzling_render_task_card($task);
                    
                    echo '</div>';
                    if (!$is_team_member) { // Quick add only for admins on this page
                        echo '<div class="add-card-controls"><button class="add-card-btn"><i class="fas fa-plus"></i> افزودن کارت</button><div class="add-card-form" style="display: none;"><textarea placeholder="عنوان کارت..."></textarea><div class="add-card-actions"><button class="pzl-button pzl-button-sm submit-add-card">افزودن</button><button type="button" class="cancel-add-card">&times;</button></div></div></div>';
                    }
                    echo '</div>';
                }
                echo '</div>';
            } else { // Swimlane logic for admins
                 $groups = [];
                if ($swimlane_by === 'assignee') $groups = $all_staff;
                elseif ($swimlane_by === 'project') $groups = $all_projects;
                elseif ($swimlane_by === 'priority') $groups = $priorities;

                foreach ($groups as $group) {
                    $group_id = ($swimlane_by === 'assignee' || $swimlane_by === 'project') ? $group->ID : $group->term_id;
                    $group_name = ($swimlane_by === 'assignee') ? $group->display_name : (($swimlane_by === 'project') ? $group->post_title : $group->name);
                    
                    echo '<div class="pzl-swimlane" data-group-id="' . esc_attr($group_id) . '">';
                    echo '<h3 class="pzl-swimlane-header">' . esc_html($group_name) . '</h3>';
                    echo '<div class="pzl-swimlane-board">';
                    
                    foreach ($statuses as $status) {
                        echo '<div class="pzl-task-column" data-status-slug="' . esc_attr($status->slug) . '">';
                        echo '<h4 class="pzl-column-header">' . esc_html($status->name) . '</h4>';
                        echo '<div class="pzl-task-list">';
                        
                        $tasks_args = ['post_type' => 'task', 'posts_per_page' => -1, 'post_parent' => 0, 'tax_query' => [['relation' => 'AND', ['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => $status->slug]]], 'meta_query' => [], 'orderby' => 'menu_order date', 'order' => 'ASC'];
                        if ($swimlane_by === 'assignee') $tasks_args['meta_query'][] = ['key' => '_assigned_to', 'value' => $group_id];
                        elseif ($swimlane_by === 'project') $tasks_args['meta_query'][] = ['key' => '_project_id', 'value' => $group_id];
                        elseif ($swimlane_by === 'priority') $tasks_args['tax_query'][0][] = ['taxonomy' => 'task_priority', 'field' => 'term_id', 'terms' => $group_id];
                        
                        $tasks_in_group = get_posts($tasks_args);
                        foreach ($tasks_in_group as $task) echo puzzling_render_task_card($task);
                        
                        echo '</div></div>';
                    }
                    echo '</div></div>';
                }
            }
            ?>
        </div>
    <?php endif; ?>
    </div>
</div>

<div id="pzl-task-modal-backdrop" style="display: none;"></div>
<div id="pzl-task-modal-wrap" style="display: none;">
    <div id="pzl-task-modal-content">
        <button id="pzl-close-modal-btn">&times;</button>
        <div id="pzl-task-modal-body">
            <div class="pzl-loader"></div>
        </div>
    </div>
</div>