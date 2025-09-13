<?php
class PuzzlingCRM_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_add_task', [$this, 'add_task']);
        add_action('wp_ajax_puzzling_update_task_status', [$this, 'update_task_status']);
        add_action('wp_ajax_puzzling_delete_task', [$this, 'delete_task']);
    }

    public function add_task() {
        check_ajax_referer('puzzling_ajax_task_nonce', 'security');

        if ( ! current_user_can('edit_tasks') || ! isset($_POST['title']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        $title = sanitize_text_field($_POST['title']);
        $priority_id = intval($_POST['priority']);
        $due_date = sanitize_text_field($_POST['due_date']);
        
        // Use assigned_to from POST, fallback to current user if not set (for team members adding for themselves)
        $assigned_to = isset($_POST['assigned_to']) && current_user_can('assign_tasks') ? intval($_POST['assigned_to']) : get_current_user_id();

        $task_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => 'task',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(), // The user who creates the task
        ]);

        if ( is_wp_error($task_id) ) {
            wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
        }
        
        // Assign task to a user via post meta
        update_post_meta($task_id, '_assigned_to', $assigned_to);

        wp_set_post_terms($task_id, [$priority_id], 'task_priority');
        wp_set_post_terms($task_id, 'to-do', 'task_status'); // Default status

        if (!empty($due_date)) {
            update_post_meta($task_id, '_due_date', $due_date);
        }

        // Use the global function to render the task item HTML
        $task = get_post($task_id);
        // Ensure the rendering function is available
        if (function_exists('puzzling_render_task_item')) {
            $task_html = puzzling_render_task_item($task);
        } else {
            $task_html = '<li>تسک جدید اضافه شد. لطفا صفحه را رفرش کنید.</li>';
        }

        wp_send_json_success(['message' => 'تسک با موفقیت اضافه شد.', 'task_html' => $task_html]);
    }

    public function update_task_status() {
        check_ajax_referer('puzzling_ajax_task_nonce', 'security');

        if ( ! current_user_can('edit_tasks') || ! isset($_POST['task_id']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $is_done = filter_var($_POST['is_done'], FILTER_VALIDATE_BOOLEAN);

        $done_term = term_exists('done', 'task_status');
        if ( !$done_term ) {
            $done_term = wp_insert_term('انجام شده', 'task_status', ['slug' => 'done']);
        }
        $done_term_id = $done_term['term_id'];

        if ($is_done) {
            wp_set_post_terms($task_id, [$done_term_id], 'task_status');
        } else {
            // Revert to 'to-do' status
            wp_set_post_terms($task_id, term_exists('to-do', 'task_status')['term_id'], 'task_status');
        }

        wp_send_json_success(['message' => 'وضعیت تسک به‌روزرسانی شد.']);
    }

    public function delete_task() {
        check_ajax_referer('puzzling_ajax_task_nonce', 'security');

        if ( ! current_user_can('delete_tasks') || ! isset($_POST['task_id']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $task = get_post($task_id);

        // Allow manager to delete any task, or user to delete their own assigned task
        $assigned_user_id = get_post_meta($task_id, '_assigned_to', true);
        if ( !$task || ( !current_user_can('manage_options') && $assigned_user_id != get_current_user_id() ) ) {
            wp_send_json_error(['message' => 'شما اجازه حذف این تسک را ندارید.']);
        }

        $result = wp_delete_post($task_id, true); // true = force delete

        if ( $result ) {
            wp_send_json_success(['message' => 'تسک با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف تسک.']);
        }
    }
}