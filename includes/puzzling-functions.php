<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'puzzling_get_dashboard_url' ) ) {
    function puzzling_get_dashboard_url() {
        $dashboard_page_id = get_option( 'puzzling_dashboard_page_id', 0 );
        if ( $dashboard_page_id && get_post_status( $dashboard_page_id ) === 'publish' ) {
            return get_permalink( $dashboard_page_id );
        }
        return home_url( '/' );
    }
}

// **NEW & IMPROVED: Renders the HTML for a single task card**
if ( ! function_exists( 'puzzling_render_task_card' ) ) {
    function puzzling_render_task_card( $task ) {
        if (is_int($task)) {
            $task = get_post($task);
        }
        if (!$task) return '';
        
        $task_id = $task->ID;
        $priority_terms = wp_get_post_terms( $task_id, 'task_priority' );
        $priority_class = !empty($priority_terms) ? 'priority-' . esc_attr($priority_terms[0]->slug) : '';
        
        $project_id = get_post_meta($task_id, '_project_id', true);
        $project_title = $project_id ? get_the_title($project_id) : '';

        $assigned_user_id = get_post_meta($task_id, '_assigned_to', true);
        $assignee_avatar = $assigned_user_id ? get_avatar($assigned_user_id, 24) : '';
        
        $due_date = get_post_meta($task_id, '_due_date', true);
        $due_date_html = '';
        if ($due_date) {
             $due_date_html = '<span class="pzl-card-due-date"><i class="fas fa-calendar-alt"></i> ' . esc_html(date_i18n('M j', strtotime($due_date))) . '</span>';
        }

        // Sub-tasks count
        $subtasks = get_children(['post_parent' => $task_id, 'post_type' => 'task']);
        $subtask_count = count($subtasks);
        $subtask_html = $subtask_count > 0 ? '<span class="pzl-card-subtasks"><i class="fas fa-tasks"></i> ' . esc_html($subtask_count) . '</span>' : '';

        // Labels
        $labels = wp_get_post_terms($task_id, 'task_label');
        $labels_html = '';
        if (!empty($labels)) {
            $labels_html .= '<div class="pzl-card-labels">';
            foreach($labels as $label) {
                $labels_html .= '<span class="pzl-label">' . esc_html($label->name) . '</span>';
            }
            $labels_html .= '</div>';
        }

        return sprintf(
            '<div class="pzl-task-card" data-task-id="%d">
                %s
                <div class="pzl-card-priority %s"></div>
                <h4 class="pzl-card-title">%s</h4>
                <div class="pzl-card-footer">
                    <div class="pzl-card-meta">
                        %s
                        %s
                        %s
                    </div>
                    <div class="pzl-card-assignee">%s</div>
                </div>
            </div>',
            esc_attr($task_id),
            $labels_html,
            esc_attr($priority_class),
            esc_html($task->post_title),
            $due_date_html,
            $subtask_html,
            $project_title ? '<span class="pzl-card-project"><i class="fas fa-folder"></i> ' . esc_html($project_title) . '</span>' : '',
            $assignee_avatar
        );
    }
}