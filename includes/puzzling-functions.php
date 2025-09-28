<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'puzzling_get_dashboard_url' ) ) {
    function puzzling_get_dashboard_url() {
        if ( is_singular() ) {
            return get_permalink( get_the_ID() );
        }
        global $wp;
        return home_url( add_query_arg( [], $wp->request ) );
    }
}

// **REVISED & STABILIZED V3: Renders the HTML for a single task card**
if ( ! function_exists( 'puzzling_render_task_card' ) ) {
    function puzzling_render_task_card( $task ) {
        if (is_int($task)) {
            $task = get_post($task);
        }
        // If the task object is invalid, return an empty string to avoid errors.
        if ( ! $task || ! is_a($task, 'WP_Post') ) {
            return '';
        }
        
        $task_id = $task->ID;
        
        // --- Priority ---
        $priority_terms = wp_get_post_terms( $task_id, 'task_priority' );
        $priority_class = !empty($priority_terms) && !is_wp_error($priority_terms) ? 'priority-' . esc_attr($priority_terms[0]->slug) : 'priority-none';
        $priority_title = !empty($priority_terms) && !is_wp_error($priority_terms) ? esc_attr($priority_terms[0]->name) : 'بدون اولویت';

        // --- Project ---
        $project_id = get_post_meta($task_id, '_project_id', true);
        $project_title = $project_id ? get_the_title($project_id) : '';
        $project_html = $project_title ? '<span class="pzl-card-project"><i class="far fa-folder"></i> ' . esc_html($project_title) . '</span>' : '';

        // --- Assignee ---
        $assigned_user_id = get_post_meta($task_id, '_assigned_to', true);
        $assignee_avatar = $assigned_user_id ? get_avatar($assigned_user_id, 24) : '';
        
        // --- Due Date ---
        $due_date = get_post_meta($task_id, '_due_date', true);
        $due_date_html = '';
        if ($due_date) {
            $due_timestamp = strtotime($due_date);
            $today_timestamp = strtotime('today');
            $due_date_class = '';

            if ($due_timestamp < $today_timestamp) {
                $due_date_class = 'pzl-due-overdue';
            } elseif ($due_timestamp < strtotime('+2 days', $today_timestamp)) {
                $due_date_class = 'pzl-due-near';
            }
            $due_date_html = '<span class="pzl-card-due-date ' . $due_date_class . '"><i class="far fa-calendar-alt"></i> ' . esc_html(date_i18n('M j', $due_timestamp)) . '</span>';
        }

        // --- Sub-tasks, Attachments, Comments ---
        $subtask_count = count(get_children(['post_parent' => $task_id, 'post_type' => 'task']));
        $subtask_html = $subtask_count > 0 ? '<span class="pzl-card-subtasks"><i class="fas fa-tasks"></i> ' . esc_html($subtask_count) . '</span>' : '';

        $attachment_ids = get_post_meta($task_id, '_task_attachments', true);
        $attachment_count = is_array($attachment_ids) ? count($attachment_ids) : 0;
        $attachment_html = $attachment_count > 0 ? '<span class="pzl-card-attachments"><i class="fas fa-paperclip"></i> ' . esc_html($attachment_count) . '</span>' : '';
        
        $comment_count = $task->comment_count;
        $comment_html = $comment_count > 0 ? '<span class="pzl-card-comments"><i class="far fa-comment"></i> ' . esc_html($comment_count) . '</span>' : '';

        // --- Labels ---
        $labels = wp_get_post_terms($task_id, 'task_label');
        $labels_html = '';
        if (!empty($labels) && !is_wp_error($labels)) {
            $labels_html .= '<div class="pzl-card-labels">';
            foreach($labels as $label) {
                $labels_html .= '<span class="pzl-label">' . esc_html($label->name) . '</span>';
            }
            $labels_html .= '</div>';
        }
        
        // --- Cover Image ---
        $cover_html = has_post_thumbnail($task_id) ? '<div class="pzl-card-cover">' . get_the_post_thumbnail($task_id, 'medium') . '</div>' : '';

        // --- Final Assembly ---
        return sprintf(
            '<div class="pzl-task-card" data-task-id="%d">
                %s
                <div class="pzl-card-content">
                    %s
                    <div class="pzl-card-main">
                        <div class="pzl-card-priority %s" title="%s"></div>
                        <h4 class="pzl-card-title">%s</h4>
                    </div>
                    <div class="pzl-card-footer">
                        <div class="pzl-card-meta">
                            %s %s %s %s %s
                        </div>
                        <div class="pzl-card-assignee">%s</div>
                    </div>
                </div>
            </div>',
            esc_attr($task_id),
            $cover_html,
            $labels_html,
            esc_attr($priority_class),
            esc_attr($priority_title),
            esc_html($task->post_title),
            $due_date_html,
            $attachment_html,
            $comment_html,
            $subtask_html,
            $project_html,
            $assignee_avatar
        );
    }
}

/**
 * Automatically syncs user phone numbers across multiple meta keys when a user profile is updated.
 */
function puzzling_sync_user_phone_numbers($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    $phone_meta_keys = [
        'pzl_mobile_phone', 'wpyarud_phone', 'puzzling_phone_number', 'user_phone_number'
    ];
    $updated_phone = null;

    foreach ($phone_meta_keys as $key) {
        if (isset($_POST[$key])) {
            $updated_phone = sanitize_text_field($_POST[$key]);
            break;
        }
    }

    if ($updated_phone !== null) {
        foreach ($phone_meta_keys as $key) {
            update_user_meta($user_id, $key, $updated_phone);
        }
    }
}
add_action('personal_options_update', 'puzzling_sync_user_phone_numbers');
add_action('edit_user_profile_update', 'puzzling_sync_user_phone_numbers');