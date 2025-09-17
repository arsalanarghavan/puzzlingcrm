<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'puzzling_get_dashboard_url' ) ) {
    function puzzling_get_dashboard_url() {
        // Return the permalink of the current page.
        // This makes the function context-aware for shortcodes.
        if ( is_singular() ) {
            return get_permalink( get_the_ID() );
        }
        // Fallback for archives or other pages by returning the current URL.
        global $wp;
        return home_url( add_query_arg( [], $wp->request ) );
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
        
        // **NEW: Deadline Visual Indicators**
        $due_date = get_post_meta($task_id, '_due_date', true);
        $due_date_html = '';
        $due_date_class = '';
        if ($due_date) {
            $due_timestamp = strtotime($due_date);
            // --- FIX: Check if strtotime returns a valid timestamp before using it ---
            if ($due_timestamp) {
                $today_timestamp = strtotime('today');
                $days_diff = ($due_timestamp - $today_timestamp) / (60 * 60 * 24);
                
                if ($days_diff < 0) {
                    $due_date_class = 'pzl-due-overdue';
                } elseif ($days_diff < 2) { // Today or tomorrow
                    $due_date_class = 'pzl-due-near';
                }

                 $due_date_html = '<span class="pzl-card-due-date ' . $due_date_class . '"><i class="far fa-calendar-alt"></i> ' . esc_html(date_i18n('M j', $due_timestamp)) . '</span>';
            }
        }

        // Sub-tasks count
        $subtasks = get_children(['post_parent' => $task_id, 'post_type' => 'task']);
        $subtask_count = count($subtasks);
        $subtask_html = $subtask_count > 0 ? '<span class="pzl-card-subtasks"><i class="fas fa-tasks"></i> ' . esc_html($subtask_count) . '</span>' : '';

        // Attachment and Comment count
        $attachment_ids = get_post_meta($task_id, '_task_attachments', true);
        $attachment_count = is_array($attachment_ids) ? count($attachment_ids) : 0;
        $attachment_html = $attachment_count > 0 ? '<span class="pzl-card-attachments"><i class="fas fa-paperclip"></i> ' . esc_html($attachment_count) . '</span>' : '';
        
        $comment_count = $task->comment_count;
        $comment_html = $comment_count > 0 ? '<span class="pzl-card-comments"><i class="far fa-comment"></i> ' . esc_html($comment_count) . '</span>' : '';

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
        
        // **NEW: Cover Image**
        $cover_html = '';
        if (has_post_thumbnail($task_id)) {
            $cover_html = '<div class="pzl-card-cover">' . get_the_post_thumbnail($task_id, 'medium') . '</div>';
        }


        return sprintf(
            '<div class="pzl-task-card" data-task-id="%d">
                %s
                %s
                <div class="pzl-card-priority %s" title="%s"></div>
                <h4 class="pzl-card-title">%s</h4>
                <div class="pzl-card-footer">
                    <div class="pzl-card-meta">
                        %s %s %s %s
                        %s
                    </div>
                    <div class="pzl-card-assignee">%s</div>
                </div>
            </div>',
            esc_attr($task_id),
            $cover_html,
            $labels_html,
            esc_attr($priority_class),
            !empty($priority_terms) ? esc_attr($priority_terms[0]->name) : '',
            esc_html($task->post_title),
            $due_date_html,
            $attachment_html,
            $comment_html,
            $subtask_html,
            $project_title ? '<span class="pzl-card-project"><i class="far fa-folder"></i> ' . esc_html($project_title) . '</span>' : '',
            $assignee_avatar
        );
    }
}