<?php
/**
 * Team Member Dashboard Template - FULLY UPGRADED
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$tasks_per_page = 10; // Number of tasks per page

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
                <input type="text" id="task_title" name="title" placeholder="عنوان تسک" required>
                
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
        <ul id="active-tasks-list" class="task-list">
            <?php
            $active_tasks_args = [
                'post_type' => 'task',
                'posts_per_page' => $tasks_per_page,
                'paged' => $paged,
                'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done', 'operator' => 'NOT IN']],
            ];
            if (!current_user_can('manage_options')) {
                $active_tasks_args['meta_key'] = '_assigned_to';
                $active_tasks_args['meta_value'] = $user_id;
            }
            $active_tasks_query = new WP_Query($active_tasks_args);

            if (!$active_tasks_query->have_posts()) {
                echo '<p class="no-tasks-message">هیچ تسک فعالی برای شما ثبت نشده است.</p>';
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
                'base' => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                'total' => $active_tasks_query->max_num_pages,
                'current' => max( 1, $paged ),
                'format' => '?paged=%#%',
            ]);
            wp_reset_postdata();
            ?>
        </div>
    </div>
</div>