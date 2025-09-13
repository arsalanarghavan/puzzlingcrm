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
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $assigned_to = isset($_POST['assigned_to']) && current_user_can('assign_tasks') ? intval($_POST['assigned_to']) : get_current_user_id();

        if (empty($project_id)) {
            wp_send_json_error(['message' => 'لطفاً یک پروژه را برای تسک انتخاب کنید.']);
        }

        $task_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => 'task',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);

        if ( is_wp_error($task_id) ) {
            wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
        }
        
        update_post_meta($task_id, '_project_id', $project_id);
        update_post_meta($task_id, '_assigned_to', $assigned_to);
        if (!empty($due_date)) {
            update_post_meta($task_id, '_due_date', $due_date);
        }

        wp_set_post_terms($task_id, [$priority_id], 'task_priority');
        wp_set_post_terms($task_id, 'to-do', 'task_status');

        // Send notification email
        $this->send_task_assignment_email($assigned_to, $task_id);
        
        $task = get_post($task_id);
        $task_html = function_exists('puzzling_render_task_item') ? puzzling_render_task_item($task) : '';
        wp_send_json_success(['message' => 'تسک با موفقیت اضافه و ایمیل اطلاع‌رسانی ارسال شد.', 'task_html' => $task_html]);
    }
    
    private function send_task_assignment_email($user_id, $task_id) {
        $user = get_userdata($user_id);
        $task = get_post($task_id);
        $project_id = get_post_meta($task_id, '_project_id', true);
        $project_title = get_the_title($project_id);
        
        if (!$user || !$task) {
            return;
        }
        
        $to = $user->user_email;
        $subject = 'یک تسک جدید به شما تخصیص داده شد: ' . $task->post_title;
        $dashboard_url = get_permalink(get_page_by_title('PuzzlingCRM Dashboard'));
        
        $body  = '<p>سلام ' . esc_html($user->display_name) . '،</p>';
        $body .= '<p>یک تسک جدید در پروژه <strong>' . esc_html($project_title) . '</strong> به شما محول شده است:</p>';
        $body .= '<ul>';
        $body .= '<li><strong>عنوان تسک:</strong> ' . esc_html($task->post_title) . '</li>';
        $body .= '</ul>';
        $body .= '<p>برای مشاهده جزئیات و مدیریت تسک‌های خود، لطفاً به داشبورد مراجعه کنید:</p>';
        $body .= '<p><a href="' . esc_url($dashboard_url) . '">رفتن به داشبورد PuzzlingCRM</a></p>';
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        wp_mail($to, $subject, $body, $headers);
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

        $assigned_user_id = get_post_meta($task_id, '_assigned_to', true);
        if ( !$task || ( !current_user_can('manage_options') && $assigned_user_id != get_current_user_id() ) ) {
            wp_send_json_error(['message' => 'شما اجازه حذف این تسک را ندارید.']);
        }

        $result = wp_delete_post($task_id, true);

        if ( $result ) {
            wp_send_json_success(['message' => 'تسک با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف تسک.']);
        }
    }
}