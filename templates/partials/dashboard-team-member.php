<?php
/**
 * Team Member Dashboard Template - IMPROVED
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Use the global rendering function
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
                <input type="text" id="task_title" name="task_title" placeholder="عنوان تسک" required>
                
                <?php if (current_user_can('assign_tasks')) : // Only managers can assign tasks to others ?>
                <select id="task_assigned_to" name="assigned_to" required>
                    <option value="">-- تخصیص به --</option>
                    <?php
                    $team_members = get_users(['role__in' => ['team_member', 'system_manager', 'administrator']]);
                    foreach ($team_members as $member) {
                        echo '<option value="' . esc_attr($member->ID) . '"' . selected($user_id, $member->ID, false) . '>' . esc_html($member->display_name) . '</option>';
                    }
                    ?>
                </select>
                <?php endif; ?>
                
                <select id="task_priority" name="priority" required>
                    <?php
                    $priorities = get_terms(['taxonomy' => 'task_priority', 'hide_empty' => false]);
                    foreach ($priorities as $priority) {
                        echo '<option value="' . esc_attr($priority->term_id) . '">' . esc_html($priority->name) . '</option>';
                    }
                    ?>
                </select>
                
                <input type="date" id="task_due_date" name="due_date" title="ددلاین تسک">

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
                'posts_per_page' => -1,
                'meta_key' => '_assigned_to',
                'meta_value' => $user_id,
                'tax_query' => [[
                    'taxonomy' => 'task_status',
                    'field' => 'slug',
                    'terms' => 'done',
                    'operator' => 'NOT IN',
                ]],
            ];

            if (current_user_can('manage_options')) {
                unset($active_tasks_args['meta_key']);
                unset($active_tasks_args['meta_value']);
            }
            
            $active_tasks = get_posts($active_tasks_args);

            if (empty($active_tasks)) {
                echo '<p class="no-tasks-message">هیچ تسک فعالی برای شما ثبت نشده است.</p>';
            } else {
                foreach ($active_tasks as $task) {
                    echo puzzling_render_task_item($task);
                }
            }
            ?>
        </ul>

        <h4>لیست تسک‌های انجام شده</h4>
        <ul id="done-tasks-list" class="task-list">
             <?php
            $done_tasks_args = [
                'post_type' => 'task',
                'posts_per_page' => -1,
                'meta_key' => '_assigned_to',
                'meta_value' => $user_id,
                'tax_query' => [[
                    'taxonomy' => 'task_status',
                    'field' => 'slug',
                    'terms' => 'done',
                ]],
            ];

            if (current_user_can('manage_options')) {
                unset($done_tasks_args['meta_key']);
                unset($done_tasks_args['meta_value']);
            }

            $done_tasks = get_posts($done_tasks_args);

            if (!empty($done_tasks)) {
                foreach ($done_tasks as $task) {
                    echo puzzling_render_task_item($task);
                }
            }
            ?>
        </ul>
    </div>
</div>

<style>
.add-task-form-container { background: #fff; border: 1px solid #e0e0e0; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
.add-task-form-container .form-row { display: flex; gap: 10px; align-items: center; }
.add-task-form-container input[type="text"] { flex-grow: 1; padding: 8px; }
.task-due-date { font-size: 12px; color: #777; margin-right: 15px; }
.task-actions { font-size: 12px; margin-right: auto; }
.task-actions a { color: #F0192A; text-decoration: none; }
</style>