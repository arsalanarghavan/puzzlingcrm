<?php
/**
 * Single Project View Template for Frontend Dashboard
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $puzzling_project_id;
$project_id = $puzzling_project_id;
$project = get_post($project_id);

// Security Check: Ensure the current user has access to this project
$current_user_id = get_current_user_id();
if ( !$project || ($project->post_author != $current_user_id && !current_user_can('edit_posts')) ) {
    echo '<p>شما دسترسی لازم برای مشاهده این پروژه را ندارید.</p>';
    return;
}

$dashboard_url = get_permalink(get_page_by_title('PuzzlingCRM Dashboard'));
?>

<div class="pzl-single-project">
    <a href="<?php echo esc_url($dashboard_url); ?>" class="back-to-dashboard-link">&larr; بازگشت به داشبورد</a>

    <div class="project-content">
        <?php echo wp_kses_post( apply_filters('the_content', $project->post_content) ); ?>
    </div>

    <hr>

    <h3>تسک‌های مربوط به این پروژه</h3>
    <div class="task-lists">
        <ul class="task-list">
        <?php
        $project_tasks_args = [
            'post_type' => 'task',
            'posts_per_page' => -1, // Show all tasks for this project
            'meta_key' => '_project_id',
            'meta_value' => $project_id,
            'orderby' => 'post_date',
            'order' => 'DESC',
        ];

        // If user is not a manager, only show tasks assigned to them within this project
        if (!current_user_can('manage_options')) {
            $project_tasks_args['meta_query'] = [
                'relation' => 'AND',
                [
                    'key' => '_project_id',
                    'value' => $project_id,
                    'compare' => '=',
                ],
                [
                    'key' => '_assigned_to',
                    'value' => $current_user_id,
                    'compare' => '=',
                ]
            ];
        }

        $project_tasks = get_posts($project_tasks_args);

        if (empty($project_tasks)) {
            echo '<p>هیچ تسکی برای این پروژه ثبت نشده است.</p>';
        } else {
            foreach ($project_tasks as $task) {
                echo puzzling_render_task_item($task);
            }
        }
        ?>
        </ul>
    </div>
</div>

<style>
.back-to-dashboard-link { display: inline-block; margin-bottom: 20px; color: var(--primary-color); text-decoration: none; font-weight: bold; }
.project-content { background: #f9f9f9; padding: 20px; border-radius: 5px; border: 1px solid #e0e0e0; margin-bottom: 30px; }
.task-details { flex-grow: 1; }
.task-project-link { font-size: 12px; color: #777; display: block; }
</style>