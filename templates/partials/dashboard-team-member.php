<?php
/**
 * Team Member Dashboard Template
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Function to render a single task item
function puzzling_render_task_item( $task ) {
    $priority_terms = wp_get_post_terms( $task->ID, 'task_priority' );
    $status_terms = wp_get_post_terms( $task->ID, 'task_status' );
    
    $priority_class = ! empty( $priority_terms ) ? 'priority-' . esc_attr( $priority_terms[0]->slug ) : 'priority-low';
    $priority_name = ! empty( $priority_terms ) ? esc_html( $priority_terms[0]->name ) : 'کم';
    
    $is_done = has_term( 'done', 'task_status', $task );
    $status_class = $is_done ? 'status-done' : '';
    $checked_attr = $is_done ? 'checked' : '';

    echo sprintf(
        '<li class="task-item %s" data-task-id="%d">
            <input type="checkbox" class="task-checkbox" %s>
            <span class="task-title">%s</span>
            <span class="task-priority %s">%s</span>
            <span class="task-due-date">%s</span>
            <span class="task-actions"><a href="#" class="delete-task">حذف</a></span>
        </li>',
        esc_attr( $status_class ),
        esc_attr( $task->ID ),
        $checked_attr,
        esc_html( $task->post_title ),
        esc_attr( $priority_class ),
        $priority_name,
        esc_html( get_post_meta( $task->ID, '_due_date', true ) ),
    );
}
?>

<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-list-view" style="vertical-align: middle;"></span> تسک‌های من</h3>

    <div class="add-task-form-container">
        <h4><span class="dashicons dashicons-plus-alt"></span> افزودن تسک جدید</h4>
        <form id="puzzling-add-task-form">
            <div class="form-row">
                <input type="text" id="task_title" name="task_title" placeholder="عنوان تسک" required>
                
                <select id="task_priority" name="task_priority" required>
                    <?php
                    $priorities = get_terms(['taxonomy' => 'task_priority', 'hide_empty' => false]);
                    foreach ($priorities as $priority) {
                        echo '<option value="' . esc_attr($priority->term_id) . '">' . esc_html($priority->name) . '</option>';
                    }
                    ?>
                </select>
                
                <input type="date" id="task_due_date" name="task_due_date" title="ددلاین تسک">

                <button type="submit" class="pzl-button pzl-button-primary">افزودن</button>
            </div>
            <?php wp_nonce_field('puzzling_ajax_task_nonce', 'security'); ?>
        </form>
    </div>

    <div class="task-lists">
        <h4>لیست تسک‌های فعال</h4>
        <ul id="active-tasks-list" class="task-list">
            <?php
            $active_tasks = get_posts([
                'post_type' => 'task',
                'author' => $user_id,
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'task_status',
                        'field' => 'slug',
                        'terms' => 'done',
                        'operator' => 'NOT IN',
                    ],
                ],
            ]);

            if (empty($active_tasks)) {
                echo '<p>هیچ تسک فعالی برای شما ثبت نشده است.</p>';
            } else {
                foreach ($active_tasks as $task) {
                    puzzling_render_task_item($task);
                }
            }
            ?>
        </ul>

        <h4>لیست تسک‌های انجام شده</h4>
        <ul id="done-tasks-list" class="task-list">
             <?php
            $done_tasks = get_posts([
                'post_type' => 'task',
                'author' => $user_id,
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'task_status',
                        'field' => 'slug',
                        'terms' => 'done',
                    ],
                ],
            ]);

            if (!empty($done_tasks)) {
                foreach ($done_tasks as $task) {
                    puzzling_render_task_item($task);
                }
            }
            ?>
        </ul>
    </div>
</div>

<style>
/* Add some specific styles for the task manager */
.add-task-form-container { background: #fff; border: 1px solid #e0e0e0; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
.add-task-form-container .form-row { display: flex; gap: 10px; align-items: center; }
.add-task-form-container input[type="text"] { flex-grow: 1; padding: 8px; }
.task-due-date { font-size: 12px; color: #777; margin-right: 15px; }
.task-actions { font-size: 12px; }
.task-actions a { color: #F0192A; text-decoration: none; }
</style>