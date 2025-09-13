<?php
class PuzzlingCRM_Ajax_Handler {

    public function __construct() {
        // AJAX action for adding a new task
        add_action('wp_ajax_puzzling_add_task', [$this, 'add_task']);
        
        // AJAX action for updating task status
        add_action('wp_ajax_puzzling_update_task_status', [$this, 'update_task_status']);
    }

    public function add_task() {
        check_ajax_referer('puzzling_ajax_task_nonce', 'security');

        if ( ! current_user_can('edit_posts') || ! isset($_POST['title']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        $title = sanitize_text_field($_POST['title']);
        $priority_id = intval($_POST['priority']);
        $due_date = sanitize_text_field($_POST['due_date']);

        $task_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => 'task',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);

        if ( is_wp_error($task_id) ) {
            wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
        }

        // Set the priority taxonomy
        wp_set_post_terms($task_id, [$priority_id], 'task_priority');
        
        // Set the initial status to "To Do" (assuming you have a 'to-do' term slug)
        wp_set_post_terms($task_id, 'to-do', 'task_status');

        // Save due date
        if (!empty($due_date)) {
            update_post_meta($task_id, '_due_date', $due_date);
        }

        // We can send back the HTML for the new task item to be prepended to the list
        ob_start();
        $task = get_post($task_id);
        // We need the render function from the template. It's better to move it to a helper file.
        // For now, let's just send success and refresh on the client side.
        // In a more advanced version, we'd return the task item HTML here.
        wp_send_json_success(['message' => 'تسک با موفقیت اضافه شد.']);
    }

    public function update_task_status() {
        check_ajax_referer('puzzling_ajax_task_nonce', 'security');

        if ( ! current_user_can('edit_posts') || ! isset($_POST['task_id']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $is_done = filter_var($_POST['is_done'], FILTER_VALIDATE_BOOLEAN);

        // Get the term ID for "done" status. Create it if it doesn't exist.
        $done_term = term_exists('done', 'task_status');
        if ( !$done_term ) {
            $done_term = wp_insert_term('Done', 'task_status', ['slug' => 'done']);
        }
        $done_term_id = $done_term['term_id'];

        if ($is_done) {
            // Add 'done' status
            wp_set_post_terms($task_id, [$done_term_id], 'task_status');
        } else {
            // Remove 'done' status.
            // This will revert it to the default or you might set it back to 'to-do'.
            wp_remove_object_terms($task_id, $done_term_id, 'task_status');
        }

        wp_send_json_success(['message' => 'وضعیت تسک به‌روزرسانی شد.']);
    }
}