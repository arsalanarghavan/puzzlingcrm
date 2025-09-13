<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'puzzling_render_task_item' ) ) {
    /**
     * Renders the HTML for a single task item.
     * @param WP_Post $task The task post object.
     * @return string HTML output.
     */
    function puzzling_render_task_item( $task ) {
        $priority_terms = wp_get_post_terms( $task->ID, 'task_priority' );
        $status_terms = wp_get_post_terms( $task->ID, 'task_status' );
        
        $priority_class = ! empty( $priority_terms ) ? 'priority-' . esc_attr( $priority_terms[0]->slug ) : 'priority-low';
        $priority_name = ! empty( $priority_terms ) ? esc_html( $priority_terms[0]->name ) : 'کم';
        
        $is_done = has_term( 'done', 'task_status', $task );
        $status_class = $is_done ? 'status-done' : '';
        $checked_attr = $is_done ? 'checked' : '';
        
        $assigned_user_id = get_post_meta( $task->ID, '_assigned_to', true );
        $assigned_user = get_userdata($assigned_user_id);
        $assignee_name = $assigned_user ? ' (' . esc_html($assigned_user->display_name) . ')' : '';

        return sprintf(
            '<li class="task-item %s" data-task-id="%d">
                <input type="checkbox" class="task-checkbox" %s>
                <span class="task-title">%s%s</span>
                <span class="task-priority %s">%s</span>
                <span class="task-due-date">%s</span>
                <span class="task-actions"><a href="#" class="delete-task">حذف</a></span>
            </li>',
            esc_attr( $status_class ),
            esc_attr( $task->ID ),
            $checked_attr,
            esc_html( $task->post_title ),
            $assignee_name, // Show assignee name
            esc_attr( $priority_class ),
            $priority_name,
            esc_html( get_post_meta( $task->ID, '_due_date', true ) )
        );
    }
}