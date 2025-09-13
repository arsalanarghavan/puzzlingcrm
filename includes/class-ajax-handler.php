<?php
class PuzzlingCRM_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_add_task', [$this, 'add_task']);
        add_action('wp_ajax_puzzling_update_task_status', [$this, 'update_task_status']);
        // **NEW**: Action for deleting a task
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

        $task_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => 'task',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);

        if ( is_wp_error($task_id) ) {
            wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
        }

        wp_set_post_terms($task_id, [$priority_id], 'task_priority');
        wp_set_post_terms($task_id, 'to-do', 'task_status');

        if (!empty($due_date)) {
            update_post_meta($task_id, '_due_date', $due_date);
        }

        // **IMPROVEMENT**: Return the HTML for the new task to improve UX
        ob_start();
        $task = get_post($task_id);
        // We need a globally accessible function to render the task item
        // Let's assume `puzzling_render_task_item` is available (we will ensure it is)
        require_once PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/dashboard-team-member.php';
        puzzling_render_task_item($task);
        $task_html = ob_get_clean();

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
            $done_term = wp_insert_term('Done', 'task_status', ['slug' => 'done']);
        }
        $done_term_id = $done_term['term_id'];

        if ($is_done) {
            wp_set_post_terms($task_id, [$done_term_id], 'task_status');
        } else {
            wp_remove_object_terms($task_id, $done_term_id, 'task_status');
        }

        wp_send_json_success(['message' => 'وضعیت تسک به‌روزرسانی شد.']);
    }

    /**
     * **NEW**: Handles the deletion of a task.
     */
    public function delete_task() {
        check_ajax_referer('puzzling_ajax_task_nonce', 'security');

        if ( ! current_user_can('delete_tasks') || ! isset($_POST['task_id']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $task = get_post($task_id);

        // Ensure the user is deleting their own task
        if ( !$task || $task->post_author != get_current_user_id() ) {
            wp_send_json_error(['message' => 'شما فقط می‌توانید تسک‌های خود را حذف کنید.']);
        }

        $result = wp_delete_post($task_id, true); // true = force delete

        if ( $result ) {
            wp_send_json_success(['message' => 'تسک با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف تسک.']);
        }
    }
}