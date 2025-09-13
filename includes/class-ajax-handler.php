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
        // **FIXED: Security vulnerability - Check capability before assigning task**
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
        
        $project_title = get_the_title($project_id);
        PuzzlingCRM_Logger::add('تسک جدید به شما محول شد', ['content' => "تسک '{$title}' در پروژه '{$project_title}' به شما تخصیص داده شد.", 'type' => 'notification', 'user_id' => $assigned_to, 'object_id' => $task_id]);

        $task = get_post($task_id);
        $task_html = function_exists('puzzling_render_task_item') ? puzzling_render_task_item($task) : '';
        wp_send_json_success(['message' => 'تسک با موفقیت اضافه شد.', 'task_html' => $task_html]);
    }
    
    private function send_task_assignment_email($user_id, $task_id) {
        $user = get_userdata($user_id);
        $task = get_post($task_id);
        $project_id = get_post_meta($task_id, '_project_id', true);
        $project_title = get_the_title($project_id);
        if (!$user || !$task) return;
        $to = $user->user_email;
        $subject = 'یک تسک جدید به شما تخصیص داده شد: ' . $task->post_title;
        $dashboard_url = function_exists('puzzling_get_dashboard_url') ? puzzling_get_dashboard_url() : home_url();
        $body  = '<p>سلام ' . esc_html($user->display_name) . '،</p>';
        $body .= '<p>یک تسک جدید در پروژه <strong>' . esc_html($project_title) . '</strong> به شما محول شده است:</p>';
        $body .= '<ul><li><strong>عنوان تسک:</strong> ' . esc_html($task->post_title) . '</li></ul>';
        $body .= '<p>برای مشاهده جزئیات و مدیریت تسک‌های خود، لطفاً به داشبورد مراجعه کنید:</p>';
        $body .= '<p><a href="' . esc_url($dashboard_url) . '">رفتن به داشبورد PuzzlingCRM</a></p>';
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($to, $subject, $body, $headers);
    }
    
    public function update_task_status() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');

        if ( ! current_user_can('edit_tasks') || ! isset($_POST['task_id']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $task = get_post($task_id);
        $is_done = filter_var($_POST['is_done'], FILTER_VALIDATE_BOOLEAN);

        $status_term = $is_done ? 'done' : 'to-do';
        $term = term_exists($status_term, 'task_status');
        if ($term) {
            wp_set_post_terms($task_id, $term['term_id'], 'task_status');
        }

        // **NEW: Log this event**
        $status_text = $is_done ? 'انجام شده' : 'انجام نشده';
        PuzzlingCRM_Logger::add('وضعیت تسک به‌روز شد', ['content' => "وضعیت تسک '{$task->post_title}' به {$status_text} تغییر یافت.", 'type' => 'log', 'object_id' => $task_id]);

        wp_send_json_success(['message' => 'وضعیت تسک به‌روزرسانی شد.']);
    }

    public function delete_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');

        if ( ! current_user_can('delete_tasks') || ! isset($_POST['task_id']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $task = get_post($task_id);

        if ( !$task || ( !current_user_can('manage_options') && $task->post_author != get_current_user_id() ) ) {
            wp_send_json_error(['message' => 'شما اجازه حذف این تسک را ندارید.']);
        }

        $task_title = $task->post_title; // Save title before deleting
        $result = wp_delete_post($task_id, true);

        if ( $result ) {
            // **NEW: Log this event**
            PuzzlingCRM_Logger::add('تسک حذف شد', ['content' => "تسک '{$task_title}' توسط " . wp_get_current_user()->display_name . " حذف شد.", 'type' => 'log', 'object_id' => $task_id]);
            wp_send_json_success(['message' => 'تسک با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف تسک.']);
        }
    }
    
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

        $unread_args = array_merge($args, ['meta_query' => [ 'relation' => 'AND', ['key' => '_log_type', 'value' => 'notification'], ['key' => '_is_read', 'value' => '0'] ]]);
        $unread_count = count(get_posts($unread_args));

        if (empty($notifications)) {
            wp_send_json_success(['count' => 0, 'html' => '<li class="pzl-no-notifications">هیچ اعلانی وجود ندارد.</li>']);
        }

        $html = '';
        foreach ($notifications as $note) {
            $is_read = get_post_meta($note->ID, '_is_read', true);
            $read_class = ($is_read == '1') ? 'pzl-read' : 'pzl-unread';
            $html .= sprintf( '<li data-id="%d" class="%s">%s <small>%s</small></li>', esc_attr($note->ID), esc_attr($read_class), esc_html($note->post_title), esc_html(human_time_diff(get_the_time('U', $note->ID), current_time('timestamp')) . ' پیش') );
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