<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'puzzling_render_task_item' ) ) {
    /**
     * Renders the HTML for a single task item.
     * Ensures all output is escaped.
     * @param WP_Post $task The task post object.
     * @return string HTML output.
     */
    function puzzling_render_task_item( $task ) {
        $priority_terms = wp_get_post_terms( $task->ID, 'task_priority' );
        $priority_class = ! empty( $priority_terms ) ? 'priority-' . esc_attr( $priority_terms[0]->slug ) : 'priority-low';
        $priority_name = ! empty( $priority_terms ) ? esc_html( $priority_terms[0]->name ) : 'کم';
        
        $is_done = has_term( 'done', 'task_status', $task );
        $status_class = $is_done ? 'status-done' : '';
        $checked_attr = $is_done ? 'checked' : '';
        
        $project_id = get_post_meta( $task->ID, '_project_id', true );
        $project_title = $project_id ? get_the_title($project_id) : 'بدون پروژه';
        
        $dashboard_url = get_permalink(get_page_by_title('PuzzlingCRM Dashboard'));
        $project_link = $project_id ? add_query_arg(['view' => 'project', 'project_id' => $project_id], $dashboard_url) : '#';

        $assignee_name = '';
        if (current_user_can('manage_options')) {
            $assigned_user_id = get_post_meta( $task->ID, '_assigned_to', true );
            $assigned_user = get_userdata($assigned_user_id);
            $assignee_name = $assigned_user ? ' (' . esc_html($assigned_user->display_name) . ')' : '';
        }

        // **IMPROVED: All outputs are now properly escaped.**
        return sprintf(
            '<li class="task-item %s" data-task-id="%d">
                <input type="checkbox" class="task-checkbox" %s>
                <div class="task-details">
                    <span class="task-title">%s%s</span>
                    <span class="task-project-link">پروژه: <a href="%s">%s</a></span>
                </div>
                <span class="task-priority %s">%s</span>
                <span class="task-due-date">%s</span>
                <span class="task-actions"><a href="#" class="delete-task">حذف</a></span>
            </li>',
            esc_attr( $status_class ),
            esc_attr( $task->ID ),
            esc_attr( $checked_attr ),
            esc_html( $task->post_title ),
            esc_html( $assignee_name ),
            esc_url( $project_link ),
            esc_html( $project_title ),
            esc_attr( $priority_class ),
            esc_html( $priority_name ),
            esc_html( get_post_meta( $task->ID, '_due_date', true ) )
        );
    }
}