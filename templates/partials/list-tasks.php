<?php
/**
 * Template for the Task Management system for the current user.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$current_user = wp_get_current_user();
if ($current_user->ID === 0) {
    echo '<p>برای مشاهده تسک‌ها، لطفاً ابتدا وارد شوید.</p>';
    return;
}

$user_id = $current_user->ID;

// **REMOVED: Duplicate function `puzzling_render_task_item_template` was removed.**
// The main function from puzzling-functions.php will be used instead.
?>

<div class="pzl-task-manager-wrapper">
    <div class="add-task-form-container">
        <h4><span class="dashicons dashicons-plus-alt"></span> افزودن تسک جدید</h4>
        <form id="puzzling-add-task-form">
            <div class="form-row">
                <input type="text" id="task_title" name="task_title" placeholder="عنوان تسک..." required>
                <select id="task_priority" name="task_priority" title="اهمیت" required>
                    <?php $priorities = get_terms(['taxonomy' => 'task_priority', 'hide_empty' => false]);
                    foreach ($priorities as $priority) { echo '<option value="' . esc_attr($priority->term_id) . '">' . esc_html($priority->name) . '</option>'; } ?>
                </select>
                <input type="date" id="task_due_date" name="task_due_date" title="ددلاین">
                <button type="submit" class="pzl-button pzl-button-primary">افزودن</button>
            </div>
            <?php wp_nonce_field('puzzling_ajax_task_nonce', 'security'); ?>
        </form>
    </div>

    <div class="task-lists">
        <h4><span class="dashicons dashicons-list-view"></span> لیست تسک‌های فعال</h4>
        <ul id="active-tasks-list" class="task-list">
            <?php
            $active_tasks = get_posts(['post_type' => 'task', 'author' => $user_id, 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done', 'operator' => 'NOT IN']]]);
            if (empty($active_tasks)) {
                echo '<p class="no-tasks-message">هیچ تسک فعالی برای شما ثبت نشده است.</p>';
            } else {
                foreach ($active_tasks as $task) { 
                    // Use the main function from puzzling-functions.php
                    echo puzzling_render_task_item($task); 
                }
            }
            ?>
        </ul>

        <hr>

        <h4><span class="dashicons dashicons-yes"></span> لیست تسک‌های انجام شده</h4>
        <ul id="done-tasks-list" class="task-list">
             <?php
            $done_tasks = get_posts(['post_type' => 'task', 'author' => $user_id, 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done']]]);
            if (empty($done_tasks)) {
                echo '<p class="no-tasks-message">هنوز تسکی را به اتمام نرسانده‌اید.</p>';
            } else {
                foreach ($done_tasks as $task) { 
                    // Use the main function from puzzling-functions.php
                    echo puzzling_render_task_item($task); 
                }
            }
            ?>
        </ul>
    </div>
</div>