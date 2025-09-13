<?php
/**
 * Team Member Dashboard Template - SEARCH & PAGINATION & UPGRADED
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : (isset($_GET['paged']) ? intval($_GET['paged']) : 1);
$tasks_per_page = 10; // Number of tasks per page
$search_query = isset($_GET['task_search']) ? sanitize_text_field($_GET['task_search']) : '';

if (!function_exists('puzzling_render_task_item')) {
    include_once PUZZLINGCRM_PLUGIN_DIR . 'includes/puzzling-functions.php';
}
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
                    $team_members = get_users(['role__in' => ['team_member', 'system_manager', 'administrator']]);
                    foreach ($team_members as $member) {
                        echo '<option value="' . esc_attr($member->ID) . '"' . selected($user_id, $member->ID, false) . '>' . esc_html($member->display_name) . '</option>';
                    }
                    ?>
                </select>
                <?php endif; ?>
                
                <select name="priority" required>
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
            <?php wp_nonce_field('puzzling_ajax_task_nonce', 'security'); ?>
        </form>
    </div>

    <div class="task-lists">
        <h4>لیست تسک‌های فعال</h4>

        <div class="pzl-search-form">
            <form method="get">
                <input type="search" name="task_search" placeholder="جستجوی تسک..." value="<?php echo esc_attr($search_query); ?>">
                <button type="submit" class="pzl-button pzl-button-secondary">جستجو</button>
            </form>
        </div>

        <ul id="active-tasks-list" class="task-list">
            <?php
            $active_tasks_args = [
                'post_type' => 'task',
                'posts_per_page' => $tasks_per_page,
                'paged' => $paged,
                's' => $search_query,
                'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done', 'operator' => 'NOT IN']],
            ];
            if (!current_user_can('manage_options')) {
                $active_tasks_args['meta_key'] = '_assigned_to';
                $active_tasks_args['meta_value'] = $user_id;
            }
            $active_tasks_query = new WP_Query($active_tasks_args);

            if (!$active_tasks_query->have_posts()) {
                echo '<p class="no-tasks-message">هیچ تسک فعالی یافت نشد.</p>';
            } else {
                while($active_tasks_query->have_posts()) {
                    $active_tasks_query->the_post();
                    echo puzzling_render_task_item(get_post());
                }
            }
            ?>
        </ul>
        <div class="pagination">
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'total' => $active_tasks_query->max_num_pages,
                'current' => max( 1, $paged ),
                'format' => '?paged=%#%',
            ]);
            wp_reset_postdata();
            ?>
        </div>
    </div>
</div>
<style>
.add-task-form-container { background: #fff; border: 1px solid #e0e0e0; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
.add-task-form-container .form-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.add-task-form-container input[type="text"] { flex-grow: 1; min-width: 200px; }
.pzl-search-form { margin-bottom: 20px; display: flex; gap: 10px; }
.pzl-search-form input { flex-grow: 1; }
.pagination { margin-top: 20px; }
.pagination .page-numbers { padding: 5px 10px; border: 1px solid #ddd; text-decoration: none; }
.pagination .page-numbers.current { background: var(--primary-color); color: #fff; }
</style>