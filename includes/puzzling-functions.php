<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Include the jdf library
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/lib/jdf.php';

if ( ! function_exists( 'puzzling_get_dashboard_url' ) ) {
    function puzzling_get_dashboard_url() {
        if ( is_singular() ) {
            return get_permalink( get_the_ID() );
        }
        global $wp;
        return home_url( add_query_arg( [], $wp->request ) );
    }
}
if ( ! function_exists( 'puzzling_can_user_view_ticket' ) ) {
    /**
     * Checks if a user has permission to view a specific ticket.
     *
     * @param int $ticket_id The ID of the ticket.
     * @param int $user_id The ID of the user.
     * @return bool True if the user can view, false otherwise.
     */
    function puzzling_can_user_view_ticket( $ticket_id, $user_id ) {
        $ticket = get_post( $ticket_id );
        if ( ! $ticket || $ticket->post_type !== 'ticket' ) {
            return false;
        }

        $user = get_user_by( 'ID', $user_id );
        if ( ! $user ) {
            return false;
        }

        $user_roles = (array) $user->roles;

        // Admins and system managers can see everything.
        if ( in_array( 'administrator', $user_roles, true ) || in_array( 'system_manager', $user_roles, true ) ) {
            return true;
        }

        // The author of the ticket (customer) can see their own ticket.
        if ( (int) $ticket->post_author === $user_id ) {
            return true;
        }

        // Team members can view if they are assigned or belong to the ticket's department.
        if ( in_array( 'team_member', $user_roles, true ) ) {
            // Check if directly assigned
            $assigned_to = get_post_meta( $ticket_id, '_assigned_to', true );
            if ( (int) $assigned_to === $user_id ) {
                return true;
            }

            // Check if they belong to the department
            $user_positions = wp_get_object_terms( $user_id, 'organizational_position' );
            if ( ! is_wp_error( $user_positions ) && ! empty( $user_positions ) ) {
                $user_department_ids = [];
                foreach ( $user_positions as $pos ) {
                    // A user belongs to a department if it's their main position or their position's parent.
                    $user_department_ids[] = $pos->term_id;
                    if ( $pos->parent ) {
                        $user_department_ids[] = $pos->parent;
                    }
                }
                $user_department_ids = array_unique( $user_department_ids );

                $ticket_departments = wp_get_post_terms( $ticket_id, 'organizational_position', [ 'fields' => 'ids' ] );
                if ( ! is_wp_error( $ticket_departments ) && ! empty( $ticket_departments ) ) {
                    if ( ! empty( array_intersect( $user_department_ids, $ticket_departments ) ) ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
// **REVISED & STABILIZED V3: Renders the HTML for a single task card**
if ( ! function_exists( 'puzzling_render_task_card' ) ) {
    function puzzling_render_task_card( $task_post ) {
        if (is_int($task_post)) {
            $task_post = get_post($task_post);
        }
        if ( ! $task_post || ! is_a($task_post, 'WP_Post') ) {
            return '';
        }
        
        global $post;
        $original_post = $post;
        $post = $task_post;
        setup_postdata($post);

        $task_id = get_the_ID();
        
        $priority_terms = wp_get_post_terms( $task_id, 'task_priority' );
        $priority_class = !empty($priority_terms) && !is_wp_error($priority_terms) ? 'priority-' . esc_attr($priority_terms[0]->slug) : 'priority-none';
        $priority_title = !empty($priority_terms) && !is_wp_error($priority_terms) ? esc_attr($priority_terms[0]->name) : 'بدون اولویت';

        $project_id = get_post_meta($task_id, '_project_id', true);
        $project_title = $project_id ? get_the_title($project_id) : '';
        $project_html = $project_title ? '<span class="pzl-card-project"><i class="far fa-folder"></i> ' . esc_html($project_title) . '</span>' : '';

        $assigned_user_id = get_post_meta($task_id, '_assigned_to', true);
        $assignee_avatar = $assigned_user_id ? get_avatar($assigned_user_id, 24) : '';
        
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
            $due_date_html = '<span class="pzl-card-due-date ' . $due_date_class . '"><i class="far fa-calendar-alt"></i> ' . jdate('Y/m/d', $due_timestamp) . '</span>';
        }

        $subtask_count = count(get_children(['post_parent' => $task_id, 'post_type' => 'task']));
        $subtask_html = $subtask_count > 0 ? '<span class="pzl-card-subtasks"><i class="fas fa-tasks"></i> ' . esc_html($subtask_count) . '</span>' : '';

        $attachment_ids = get_post_meta($task_id, '_task_attachments', true);
        $attachment_count = is_array($attachment_ids) ? count($attachment_ids) : 0;
        $attachment_html = $attachment_count > 0 ? '<span class="pzl-card-attachments"><i class="fas fa-paperclip"></i> ' . esc_html($attachment_count) . '</span>' : '';
        
        $comment_count = get_comments_number();
        $comment_html = $comment_count > 0 ? '<span class="pzl-card-comments"><i class="far fa-comment"></i> ' . esc_html($comment_count) . '</span>' : '';

        $labels = wp_get_post_terms($task_id, 'task_label');
        $labels_html = '';
        if (!empty($labels) && !is_wp_error($labels)) {
            $labels_html .= '<div class="pzl-card-labels">';
            foreach($labels as $label) {
                $labels_html .= '<span class="pzl-label">' . esc_html($label->name) . '</span>';
            }
            $labels_html .= '</div>';
        }
        
        $cover_html = has_post_thumbnail($task_id) ? '<div class="pzl-card-cover">' . get_the_post_thumbnail($task_id, 'medium') . '</div>' : '';

        wp_reset_postdata();
        $post = $original_post;
        if($post) setup_postdata($post);

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
            esc_html($task_post->post_title),
            $due_date_html,
            $attachment_html,
            $comment_html,
            $subtask_html,
            $project_html,
            $assignee_avatar
        );
    }
}

if ( ! function_exists( 'puzzling_jalali_to_gregorian' ) ) {
    /**
     * Converts a Jalali date string (with potential Farsi numerals) to Gregorian 'Y-m-d' format.
     *
     * @param string $jalali_date The Jalali date, e.g., '1404/07/08' or '۱۴۰۴/۰۷/۰۸'.
     * @return string The Gregorian date, e.g., '2025-09-29', or an empty string on failure.
     */
    function puzzling_jalali_to_gregorian($jalali_date) {
        if(empty($jalali_date)) return '';
        
        // *** CRITICAL FIX: Convert Farsi/Arabic numerals to English before any processing ***
        $jalali_date = tr_num($jalali_date, 'en');
        
        // Normalize separators
        $jalali_date = str_replace('-', '/', $jalali_date);
        
        $parts = explode('/', $jalali_date);
        if(count($parts) !== 3) return ''; // Return empty string for incorrect format

        // Ensure parts are integers
        $j_y = intval($parts[0]);
        $j_m = intval($parts[1]);
        $j_d = intval($parts[2]);
        
        // Basic validation to prevent errors in jdf.php if parts become zero after conversion
        if ($j_y < 1000 || $j_m < 1 || $j_m > 12 || $j_d < 1 || $j_d > 31) {
             return '';
        }
        
        if (function_exists('jcheckdate') && !jcheckdate($j_m, $j_d, $j_y)) {
            return ''; // Return empty for invalid Jalali dates
        }

        $gregorian_parts = jalali_to_gregorian($j_y, $j_m, $j_d);
        return implode('-', $gregorian_parts);
    }
}

if ( ! function_exists( 'puzzling_gregorian_to_jalali' ) ) {
    function puzzling_gregorian_to_jalali($gregorian_date) {
        if(empty($gregorian_date) || $gregorian_date == '0000-00-00' || strtotime($gregorian_date) === false) return '';
        return jdate('Y/m/d', strtotime($gregorian_date));
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

/**
 * Gets the default task status slug (the first one in the defined order).
 * @return string The slug of the first status, or 'to-do' as a fallback.
 */
function puzzling_get_default_task_status_slug() {
    $statuses = get_terms([
        'taxonomy'   => 'task_status',
        'hide_empty' => false,
        'orderby'    => 'term_order',
        'order'      => 'ASC',
        'number'     => 1,
    ]);

    if ( ! empty( $statuses ) && ! is_wp_error( $statuses ) ) {
        return $statuses[0]->slug;
    }

    return 'to-do'; // Fallback
}

if ( ! function_exists( 'puzzling_get_sms_handler' ) ) {
    /**
     * Retrieves the correct SMS handler instance based on saved settings.
     *
     * @param array $settings The plugin's settings array.
     * @return PuzzlingCRM_SMS_Service_Interface|null The handler instance or null if not configured.
     */
    function puzzling_get_sms_handler( $settings ) {
        $active_service = $settings['sms_service'] ?? null;
        $handler = null;

        switch ($active_service) {
            case 'melipayamak':
                $username = $settings['melipayamak_username'] ?? '';
                $password = $settings['melipayamak_password'] ?? '';
                $sender_number = $settings['melipayamak_sender_number'] ?? '';
                if ($username && $password && $sender_number) {
                    $handler = new CSM_Melipayamak_Handler($username, $password, $sender_number);
                }
                break;
            case 'parsgreen':
                $signature = $settings['parsgreen_signature'] ?? '';
                $sender_number = $settings['parsgreen_sender_number'] ?? '';
                if ($signature && $sender_number) {
                    $handler = new PuzzlingCRM_ParsGreen_Handler($signature, $sender_number);
                }
                break;
        }

        return $handler;
    }
}