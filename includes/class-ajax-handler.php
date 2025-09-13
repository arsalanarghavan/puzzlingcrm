<?php
class PuzzlingCRM_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_add_task', [$this, 'add_task']);
        add_action('wp_ajax_puzzling_update_task_status', [$this, 'update_task_status']);
        add_action('wp_ajax_puzzling_delete_task', [$this, 'delete_task']);
        add_action('wp_ajax_puzzling_get_notifications', [$this, 'get_notifications']);
        add_action('wp_ajax_puzzling_mark_notification_read', [$this, 'mark_notification_read']);
    }

    public function add_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');

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

        $task_id = wp_insert_post(['post_title' => $title, 'post_type' => 'task', 'post_status' => 'publish', 'post_author' => get_current_user_id()]);

        if ( is_wp_error($task_id) ) {
            wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
        }
        
        update_post_meta($task_id, '_project_id', $project_id);
        update_post_meta($task_id, '_assigned_to', $assigned_to);
        if (!empty($due_date)) update_post_meta($task_id, '_due_date', $due_date);

        wp_set_post_terms($task_id, [$priority_id], 'task_priority');
        wp_set_post_terms($task_id, 'to-do', 'task_status');

        $this->send_task_assignment_email($assigned_to, $task_id);
        
        // **NEW: Create a notification for the assigned user**
        $project_title = get_the_title($project_id);
        PuzzlingCRM_Logger::add('تسک جدید به شما محول شد', ['content' => "تسک '{$title}' در پروژه '{$project_title}' به شما تخصیص داده شد.", 'type' => 'notification', 'user_id' => $assigned_to, 'object_id' => $task_id]);

        $task = get_post($task_id);
        $task_html = function_exists('puzzling_render_task_item') ? puzzling_render_task_item($task) : '';
        wp_send_json_success(['message' => 'تسک با موفقیت اضافه و ایمیل اطلاع‌رسانی ارسال شد.', 'task_html' => $task_html]);
    }
    
    private function send_task_assignment_email($user_id, $task_id) {
        // ... (this function remains the same)
    }
    
    public function update_task_status() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        // ... (this function remains the same)
    }

    public function delete_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        // ... (this function remains the same, but you could add logging here too)
    }
    
    // **NEW: Notification Functions**
    public function get_notifications() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $user_id = get_current_user_id();
        $args = [
            'post_type' => 'puzzling_log',
            'author' => $user_id,
            'posts_per_page' => 5,
            'meta_query' => [ ['key' => '_log_type', 'value' => 'notification'] ]
        ];
        $notifications = get_posts($args);

        $unread_count = 0;
        foreach (get_posts(array_merge($args, ['meta_query' => [['key' => '_is_read', 'value' => '0']]])) as $p) {
            $unread_count++;
        }

        $html = '';
        if (empty($notifications)) {
            wp_send_json_success(['count' => 0, 'html' => '<li class="pzl-no-notifications">هیچ اعلانی وجود ندارد.</li>']);
        }

        foreach ($notifications as $note) {
            $is_read = get_post_meta($note->ID, '_is_read', true);
            $read_class = ($is_read == '1') ? 'pzl-read' : 'pzl-unread';
            $html .= sprintf(
                '<li data-id="%d" class="%s">%s <small>%s</small></li>',
                esc_attr($note->ID),
                esc_attr($read_class),
                esc_html($note->post_title),
                esc_html(human_time_diff(get_the_time('U', $note->ID), current_time('timestamp')) . ' پیش')
            );
        }
        
        wp_send_json_success(['count' => $unread_count, 'html' => $html]);
    }

    public function mark_notification_read() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (isset($_POST['id'])) {
            $note_id = intval($_POST['id']);
            $note = get_post($note_id);
            if ($note && $note->post_author == get_current_user_id()) {
                update_post_meta($note_id, '_is_read', '1');
                wp_send_json_success(['message' => 'خوانده شد.']);
            }
        }
        wp_send_json_error(['message' => 'خطا.']);
    }
}