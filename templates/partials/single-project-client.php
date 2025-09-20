<?php
/**
 * Single Project Read-Only View Template for Clients.
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// This template is loaded by dashboard-client.php which already has security checks.
global $puzzling_project; // The project object is passed from the parent template
$project = $puzzling_project;
$project_id = $project->ID;
$contract_id = get_post_meta($project_id, '_contract_id', true);
$dashboard_url = get_permalink();
?>

<div class="pzl-single-project-client">
    <a href="<?php echo esc_url(add_query_arg('view', 'projects', $dashboard_url)); ?>" class="pzl-button back-to-dashboard-btn">&larr; بازگشت به لیست پروژه‌ها</a>

    <div class="pzl-card">
        <div class="pzl-project-card-header-flex">
            <div class="pzl-project-card-logo">
                <?php if (has_post_thumbnail($project_id)) : ?>
                    <?php echo get_the_post_thumbnail($project_id, 'thumbnail'); ?>
                <?php else: ?>
                    <div class="pzl-logo-placeholder"><?php echo esc_html(mb_substr($project->post_title, 0, 1)); ?></div>
                <?php endif; ?>
            </div>
            <div class="pzl-project-card-title-group">
                <h3 class="pzl-project-card-title"><?php echo esc_html($project->post_title); ?></h3>
                <?php if ($contract_id): ?>
                <span class="pzl-project-card-customer">تحت قرارداد <a href="<?php echo esc_url(add_query_arg('view', 'contracts', $dashboard_url)); ?>">#<?php echo esc_html($contract_id); ?></a></span>
                <?php endif; ?>
            </div>
        </div>

        <hr>
        
        <h4><i class="fas fa-info-circle"></i> جزئیات پروژه</h4>
        <div class="project-content">
            <?php echo $project->post_content ? wp_kses_post(wpautop($project->post_content)) : '<p>توضیحاتی برای این پروژه ثبت نشده است.</p>'; ?>
        </div>
        
        <hr>

        <h4><i class="fas fa-tasks"></i> وظایف شما در این پروژه</h4>
        <div class="task-lists">
            <?php
            $project_tasks_args = [
                'post_type' => 'task',
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'AND',
                    ['key' => '_project_id', 'value' => $project_id],
                    ['key' => '_assigned_to', 'value' => get_current_user_id()]
                ],
                'orderby' => 'post_date',
                'order' => 'DESC',
            ];

            $project_tasks = get_posts($project_tasks_args);

            if (empty($project_tasks)) {
                echo '<div class="pzl-empty-state" style="margin-top:0;"><p>در حال حاضر هیچ وظیفه‌ای برای شما در این پروژه تعریف نشده است.</p></div>';
            } else {
                echo '<div class="pzl-task-list-client">';
                foreach ($project_tasks as $task) {
                    echo puzzling_render_task_card($task);
                }
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<style>
.pzl-task-list-client {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}
</style>