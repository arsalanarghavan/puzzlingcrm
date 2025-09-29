<?php
/**
 * Template for Agile/Scrum Board (Sprints & Backlog)
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$all_projects = get_posts(['post_type' => 'project', 'numberposts' => -1, 'post_status' => 'publish']);
$selected_project = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
?>
<div class="pzl-scrum-board-wrapper">
    <div class="pzl-card">
        <div class="pzl-scrum-board-header">
            <h3><i class="fas fa-columns"></i> بک‌لاگ و اسپرینت‌ها</h3>
            <form method="get" class="pzl-form">
                <input type="hidden" name="view" value="scrum_board">
                <div class="form-group">
                    <label for="project_filter_scrum">پروژه را انتخاب کنید:</label>
                    <select name="project_id" id="project_filter_scrum" onchange="this.form.submit()">
                        <option value="">-- انتخاب پروژه --</option>
                        <?php foreach ($all_projects as $project) {
                            echo '<option value="' . esc_attr($project->ID) . '" ' . selected($selected_project, $project->ID, false) . '>' . esc_html($project->post_title) . '</option>';
                        } ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="pzl-scrum-board-content">
        <div class="pzl-scrum-column" id="backlog-column">
            <h4><i class="fas fa-box-open"></i> بک‌لاگ</h4>
            <div class="pzl-scrum-task-list" data-sprint-id="0">
                <div class="pzl-loader"></div>
            </div>
        </div>

        <?php
        if ($selected_project) {
            $sprints = get_posts([
                'post_type' => 'pzl_sprint',
                'posts_per_page' => -1,
                'meta_key' => '_project_id',
                'meta_value' => $selected_project,
                'orderby' => 'date',
                'order' => 'ASC'
            ]);

            foreach ($sprints as $sprint) {
                $start_date = get_post_meta($sprint->ID, '_sprint_start_date', true);
                $end_date = get_post_meta($sprint->ID, '_sprint_end_date', true);
                ?>
                <div class="pzl-scrum-column">
                    <h4><i class="fas fa-rocket"></i> <?php echo esc_html($sprint->post_title); ?></h4>
                    <small><?php echo jdate('Y/m/d', strtotime($start_date)); ?> - <?php echo jdate('Y/m/d', strtotime($end_date)); ?></small>
                    <div class="pzl-scrum-task-list" data-sprint-id="<?php echo esc_attr($sprint->ID); ?>">
                        <?php
                        $sprint_tasks = get_posts([
                            'post_type' => 'task', 'posts_per_page' => -1,
                            'meta_key' => '_sprint_id', 'meta_value' => $sprint->ID
                        ]);
                        foreach ($sprint_tasks as $task) {
                            echo puzzling_render_task_card($task);
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>