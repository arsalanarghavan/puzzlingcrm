<?php
/**
 * Team Member Dashboard Template - Fully Completed & Upgraded
 * Includes AJAX form, search, pagination, and role-based views.
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

// Ensure the function for rendering a task item is available.
if (!function_exists('puzzling_render_task_item')) {
    $functions_file = PUZZLINGCRM_PLUGIN_DIR . 'includes/puzzling-functions.php';
    if (file_exists($functions_file)) {
        include_once $functions_file;
    } else {
        // Fallback in case the function file is missing, to avoid fatal errors.
        function puzzling_render_task_item($task) {
            return '<li>Error: Task render function is missing.</li>';
        }
    }
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Sanitize GET parameters for pagination and search.
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$search_query = isset($_GET['task_search']) ? sanitize_text_field($_GET['task_search']) : '';
$tasks_per_page = 10; // Number of tasks per page.

?>

<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-list-view" style="vertical-align: middle;"></span> تسک‌های من</h3>

    <div class="add-task-form-container">
        <h4><span class="dashicons dashicons-plus-alt"></span> افزودن تسک جدید</h4>
        <form id="puzzling-add-task-form">
            <div class="form-row">
                <input type="text" id="task_title" name="title" placeholder="عنوان تسک..." required>
                
                <select id="task_project" name="project_id" required>
                    <option value="">-- انتخاب پروژه --</option>
                    <?php
                    $projects = get_posts(['post_type' => 'project', 'numberposts' => -1, 'post_status' => 'publish']);
                    foreach ($projects as $project) {
                        echo '<option value="' . esc_attr($project->ID) . '">' . esc_html($project->post_title) . '</option>';
                    }
                    ?>
                </select>

                <?php if (current_user_can('assign_tasks')) : ?>
                <select name="assigned_to" required>
                    <option value="">-- تخصیص به --</option>
                    <?php
                    // List users who can be assigned tasks.
                    $team_members = get_users(['role__in' => ['team_member', 'system_manager', 'administrator']]);
                    foreach ($team_members as $member) {
                        // The current manager is selected by default.
                        echo '<option value="' . esc_attr($member->ID) . '"' . selected($user_id, $member->ID, false) . '>' . esc_html($member->display_name) . '</option>';
                    }
                    ?>
                </select>
                <?php endif; ?>
                
                <select name="priority" required title="اهمیت تسک">
                    <?php
                    $priorities = get_terms(['taxonomy' => 'task_priority', 'hide_empty' => false]);
                    foreach ($priorities as $priority) {
                        echo '<option value="' . esc_attr($priority->term_id) . '">' . esc_html($priority->name) . '</option>';
                    }
                    ?>
                </select>
                
                <input type="date" name="due_date" title="ددلاین تسک">

                <button type="submit" class="pzl-button pzl-button-primary">افزودن</button>
            </div>
            <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); // Note: This nonce is primarily for non-JS fallback. The JS uses the localized nonce. ?>
        </form>
    </div>

    <div class="task-lists">
        <h4><span class="dashicons dashicons-marker"></span> لیست تسک‌های فعال</h4>

        <div class="pzl-search-form">
            <form method="get">
                <input type="search" name="task_search" placeholder="جستجوی عنوان تسک..." value="<?php echo esc_attr($search_query); ?>">
                <button type="submit" class="pzl-button pzl-button-secondary">جستجو</button>
            </form>
        </div>

        <ul id="active-tasks-list" class="task-list">
            <?php
            $active_tasks_args = [
                'post_type' => 'task',
                'posts_per_page' => $tasks_per_page,
                'paged' => $paged,
                's' => $search_query, // Search by title.
                'tax_query' => [
                    [
                        'taxonomy' => 'task_status',
                        'field' => 'slug',
                        'terms' => 'done',
                        'operator' => 'NOT IN'
                    ]
                ],
            ];
            // If the current user is not a manager, show only tasks assigned to them.
            if (!current_user_can('manage_options')) {
                $active_tasks_args['meta_key'] = '_assigned_to';
                $active_tasks_args['meta_value'] = $user_id;
            }
            $active_tasks_query = new WP_Query($active_tasks_args);

            if (!$active_tasks_query->have_posts()) {
                echo '<li class="no-tasks-message">هیچ تسک فعالی یافت نشد.</li>';
            } else {
                while($active_tasks_query->have_posts()) {
                    $active_tasks_query->the_post();
                    // Use the dedicated function to render the task item.
                    echo puzzling_render_task_item(get_post());
                }
            }
            ?>
        </ul>
        <div class="pagination">
            <?php
            // Display pagination links.
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'total' => $active_tasks_query->max_num_pages,
                'current' => max( 1, $paged ),
                'format' => '?paged=%#%',
                'prev_text' => '« قبلی',
                'next_text' => 'بعدی »',
            ]);
            wp_reset_postdata();
            ?>
        </div>
        
        <hr style="margin: 30px 0;">

        <h4><span class="dashicons dashicons-yes"></span> لیست تسک‌های انجام شده</h4>
        <ul id="done-tasks-list" class="task-list">
             <?php
            $done_tasks_args = [
                'post_type' => 'task',
                'posts_per_page' => 5, // Show only the 5 most recent completed tasks.
                'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done']],
            ];
            if (!current_user_can('manage_options')) {
                $done_tasks_args['meta_key'] = '_assigned_to';
                $done_tasks_args['meta_value'] = $user_id;
            }
            $done_tasks = get_posts($done_tasks_args);

            if (empty($done_tasks)) {
                echo '<li class="no-tasks-message">هنوز تسکی را به اتمام نرسانده‌اید.</li>';
            } else {
                foreach ($done_tasks as $task) { echo puzzling_render_task_item($task); }
            }
            ?>
        </ul>
    </div>
</div>
<style>
.add-task-form-container { background: #fff; border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
.add-task-form-container h4 { margin-top: 0; }
.add-task-form-container .form-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.add-task-form-container input[type="text"], .add-task-form-container select { flex-grow: 1; min-width: 150px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
.add-task-form-container button { flex-shrink: 0; }
.pzl-search-form { margin-bottom: 20px; display: flex; gap: 10px; }
.pzl-search-form input { flex-grow: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
.task-lists h4 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
.pagination { margin-top: 20px; text-align: left; }
.pagination .page-numbers { display: inline-block; padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; border-radius: 4px; transition: background-color 0.3s; }
.pagination .page-numbers:hover { background-color: #f0f0f0; }
.pagination .page-numbers.current { background: var(--primary-color, #F0192A); color: #fff; border-color: var(--primary-color, #F0192A); }
.no-tasks-message { background-color: #f9f9f9; text-align: center; padding: 20px; border-radius: 5px; color: #777; list-style: none; }
</style>