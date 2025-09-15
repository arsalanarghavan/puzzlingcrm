<?php
// Make sure to require the necessary WordPress file upload handling functions
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

class PuzzlingCRM_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_add_task', [$this, 'add_task']);
        add_action('wp_ajax_puzzling_quick_add_task', [$this, 'quick_add_task']);
        add_action('wp_ajax_puzzling_update_task_status', [$this, 'update_task_status']);
        add_action('wp_ajax_puzzling_delete_task', [$this, 'delete_task']);
        add_action('wp_ajax_puzzling_get_notifications', [$this, 'get_notifications']);
        add_action('wp_ajax_puzzling_mark_notification_read', [$this, 'mark_notification_read']);

        // Kanban board actions
        add_action('wp_ajax_puzzling_get_task_details', [$this, 'get_task_details']);
        add_action('wp_ajax_puzzling_save_task_content', [$this, 'save_task_content']);
        add_action('wp_ajax_puzzling_add_task_comment', [$this, 'add_task_comment']);
        
        // Workflow Management Actions
        add_action('wp_ajax_puzzling_save_status_order', [$this, 'save_status_order']);
        add_action('wp_ajax_puzzling_add_new_status', [$this, 'add_new_status']);
        add_action('wp_ajax_puzzling_delete_status', [$this, 'delete_status']);

        // **NEW: Advanced Task Features**
        add_action('wp_ajax_puzzling_manage_checklist', [$this, 'manage_checklist']);
        add_action('wp_ajax_puzzling_log_time', [$this, 'log_time']);

        // **NEW: AJAX handler for Quick Edit**
        add_action('wp_ajax_puzzling_quick_edit_task', [$this, 'quick_edit_task']);
    }
    
    /**
     * Logs an activity to a task's metadata.
     * @param int $task_id The ID of the task.
     * @param string $activity_text The description of the activity.
     */
    private function _log_task_activity($task_id, $activity_text) {
        $activity_log = get_post_meta($task_id, '_task_activity_log', true);
        if (!is_array($activity_log)) {
            $activity_log = [];
        }
        $current_user = wp_get_current_user();
        $new_log_entry = [
            'user_id' => $current_user->ID,
            'user_name' => $current_user->display_name,
            'text' => $activity_text,
            'time' => current_time('mysql'),
        ];
        // Add to the beginning of the array
        array_unshift($activity_log, $new_log_entry);
        update_post_meta($task_id, '_task_activity_log', $activity_log);
    }


    private function notify_all_admins($title, $args) {
        $admins = get_users([
            'role__in' => ['administrator', 'system_manager'],
            'fields' => 'ID',
        ]);

        foreach ($admins as $admin_id) {
            $notification_args = array_merge($args, ['user_id' => $admin_id]);
            PuzzlingCRM_Logger::add($title, $notification_args);
        }
    }

    public function add_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');

        if ( ! current_user_can('edit_tasks') || ! isset($_POST['title']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        $title = sanitize_text_field($_POST['title']);
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $priority_id = intval($_POST['priority']);
        $due_date = sanitize_text_field($_POST['due_date']);
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $assigned_to = isset($_POST['assigned_to']) && current_user_can('assign_tasks') ? intval($_POST['assigned_to']) : get_current_user_id();
        
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        $time_estimate = isset($_POST['time_estimate']) ? floatval($_POST['time_estimate']) : 0;
        $task_labels = isset($_POST['task_labels']) ? sanitize_text_field($_POST['task_labels']) : '';
        $epic_id = isset($_POST['epic_id']) ? intval($_POST['epic_id']) : 0; // New Epic field

        if (empty($project_id)) {
            wp_send_json_error(['message' => 'لطفاً یک پروژه را برای تسک انتخاب کنید.']);
        }

        $task_id = wp_insert_post([
            'post_title' => $title, 
            'post_content' => $content,
            'post_type' => 'task', 
            'post_status' => 'publish', 
            'post_author' => get_current_user_id(),
            'post_parent' => $parent_id,
        ]);

        if ( is_wp_error($task_id) ) {
            wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
        }
        
        // Save metadata
        update_post_meta($task_id, '_project_id', $project_id);
        update_post_meta($task_id, '_assigned_to', $assigned_to);
        if (!empty($due_date)) update_post_meta($task_id, '_due_date', $due_date);
        if ($time_estimate > 0) update_post_meta($task_id, '_time_estimate', $time_estimate);
        if ($epic_id > 0) {
             update_post_meta($task_id, '_task_epic_id', $epic_id);
        }

        // Set taxonomies
        wp_set_post_terms($task_id, [$priority_id], 'task_priority');
        wp_set_post_terms($task_id, 'to-do', 'task_status');
        if (!empty($task_labels)) {
            $labels_array = array_map('trim', explode(',', $task_labels));
            wp_set_post_terms($task_id, $labels_array, 'task_label');
        }
        
        $this->_log_task_activity($task_id, sprintf('وظیفه را ایجاد کرد.'));

        // Handle file attachments
        if ( ! empty($_FILES['task_attachments']) ) {
            $files = $_FILES['task_attachments'];
            $attachment_ids = [];

            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $file = [
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key]
                    ];
                    $_FILES = ["upload_file" => $file];
                    $attachment_id = media_handle_upload("upload_file", $task_id);
                    if (!is_wp_error($attachment_id)) {
                        $attachment_ids[] = $attachment_id;
                        $this->_log_task_activity($task_id, sprintf('فایل "%s" را پیوست کرد.', esc_html($files['name'][$key])));
                    }
                }
            }
            if (!empty($attachment_ids)) {
                 update_post_meta($task_id, '_task_attachments', $attachment_ids);
            }
        }
        
        $this->send_task_assignment_email($assigned_to, $task_id);
        
        $project_title = get_the_title($project_id);
        
        PuzzlingCRM_Logger::add('تسک جدید به شما محول شد', ['content' => "تسک '{$title}' در پروژه '{$project_title}' به شما تخصیص داده شد.", 'type' => 'notification', 'user_id' => $assigned_to, 'object_id' => $task_id]);

        $task = get_post($task_id);
        $task_html = function_exists('puzzling_render_task_card') ? puzzling_render_task_card($task) : '';
        wp_send_json_success(['message' => 'تسک با موفقیت اضافه شد.', 'task_html' => $task_html]);
    }

    public function quick_add_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');

        if ( ! current_user_can('edit_tasks') || ! isset($_POST['title']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        $title = sanitize_text_field($_POST['title']);
        $status_slug = sanitize_key($_POST['status_slug']);
        
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $assigned_to = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0;
        
        if (empty($project_id) || empty($assigned_to)) {
             wp_send_json_error(['message' => 'برای افزودن سریع، لطفاً ابتدا برد را بر اساس پروژه و کارمند فیلتر کنید.']);
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
        wp_set_post_terms($task_id, $status_slug, 'task_status');
        
        $medium_priority = get_term_by('slug', 'medium', 'task_priority');
        if ($medium_priority) {
            wp_set_post_terms($task_id, $medium_priority->term_id, 'task_priority');
        }

        $this->_log_task_activity($task_id, sprintf('وظیفه را به صورت سریع ایجاد کرد.'));

        $this->send_task_assignment_email($assigned_to, $task_id);
        
        $project_title = get_the_title($project_id);
        PuzzlingCRM_Logger::add('تسک جدید به شما محول شد', ['content' => "تسک '{$title}' در پروژه '{$project_title}' به شما تخصیص داده شد.", 'type' => 'notification', 'user_id' => $assigned_to, 'object_id' => $task_id]);

        $task = get_post($task_id);
        $task_html = function_exists('puzzling_render_task_card') ? puzzling_render_task_card($task) : '';
        wp_send_json_success(['message' => 'تسک سریع با موفقیت اضافه شد.', 'task_html' => $task_html]);
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

        if ( ! current_user_can('edit_tasks') || ! isset($_POST['task_id']) || !isset($_POST['new_status_slug']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        // **NEW: WORKFLOW RULES (Simple Example)**
        // In a real implementation, this would read from a settings page.
        $workflow_rules = [
            'done' => ['system_manager'] // Only system_manager can move tasks to "Done"
        ];
        
        $task_id = intval($_POST['task_id']);
        $new_status_slug = sanitize_key($_POST['new_status_slug']);
        $user = wp_get_current_user();
        
        if (array_key_exists($new_status_slug, $workflow_rules)) {
            $allowed_roles = $workflow_rules[$new_status_slug];
            if (empty(array_intersect($allowed_roles, $user->roles))) {
                wp_send_json_error(['message' => 'شما اجازه انتقال وظیفه به این وضعیت را ندارید.']);
                return;
            }
        }

        $task = get_post($task_id);
        $old_status_terms = wp_get_post_terms($task_id, 'task_status');
        $old_status_name = !empty($old_status_terms) ? $old_status_terms[0]->name : 'نامشخص';

        $term = get_term_by('slug', $new_status_slug, 'task_status');
        if ($term) {
            wp_set_post_terms($task_id, $term->term_id, 'task_status');
            
            $log_message = sprintf('وضعیت وظیفه را از "%s" به "%s" تغییر داد.', $old_status_name, $term->name);
            $this->_log_task_activity($task_id, $log_message);

            wp_send_json_success(['message' => 'وضعیت تسک به‌روزرسانی شد.']);
        } else {
             wp_send_json_error(['message' => 'وضعیت نامعتبر است.']);
        }
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

        $task_title = $task->post_title;
        $result = wp_delete_post($task_id, true);

        if ( $result ) {
            PuzzlingCRM_Logger::add('تسک حذف شد', ['content' => "تسک '{$task_title}' توسط " . wp_get_current_user()->display_name . " حذف شد.", 'type' => 'log', 'object_id' => $task_id]);
            wp_send_json_success(['message' => 'تسک با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف تسک.']);
        }
    }
    
    public function get_task_details() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!isset($_POST['task_id']) || !current_user_can('edit_tasks')) {
            wp_send_json_error(['message' => 'خطای دسترسی.']);
        }

        $task_id = intval($_POST['task_id']);
        $task = get_post($task_id);

        if (!$task) {
            wp_send_json_error(['message' => 'وظیفه یافت نشد.']);
        }
        
        ob_start();
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/modal-task-details.php';
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
    
    public function save_task_content() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!isset($_POST['task_id']) || !isset($_POST['content']) || !current_user_can('edit_tasks')) {
            wp_send_json_error(['message' => 'خطای دسترسی.']);
        }
        
        $task_id = intval($_POST['task_id']);
        $content = wp_kses_post($_POST['content']);

        $result = wp_update_post(['ID' => $task_id, 'post_content' => $content], true);
        
        $this->_log_task_activity($task_id, 'توضیحات وظیفه را به‌روزرسانی کرد.');

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در ذخیره‌سازی.']);
        } else {
            wp_send_json_success(['new_content_html' => wpautop($content)]);
        }
    }
    
    public function add_task_comment() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!isset($_POST['task_id']) || !isset($_POST['comment_text']) || !current_user_can('edit_tasks')) {
            wp_send_json_error(['message' => 'خطای دسترسی.']);
        }
        
        $task_id = intval($_POST['task_id']);
        $comment_text = wp_kses_post($_POST['comment_text']);
        $user = wp_get_current_user();
        
        $comment_id = wp_insert_comment([
            'comment_post_ID' => $task_id,
            'comment_author' => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_content' => $comment_text,
            'user_id' => $user->ID,
            'comment_approved' => 1,
        ]);

        if ($comment_id) {
            $this->_log_task_activity($task_id, sprintf('یک نظر جدید ثبت کرد: "%s"', esc_html(wp_trim_words($comment_text, 10))));
            
            // Handle mentions
            preg_match_all('/@(\w+)/', $comment_text, $matches);
            if (!empty($matches[1])) {
                $mentioned_logins = array_unique($matches[1]);
                foreach ($mentioned_logins as $login) {
                    $mentioned_user = get_user_by('login', $login);
                    if ($mentioned_user) {
                         PuzzlingCRM_Logger::add(
                            sprintf('شما در تسک "%s" منشن شدید', get_the_title($task_id)), 
                            [
                                'content' => sprintf('%s شما را در یک نظر منشن کرد.', $user->display_name),
                                'type' => 'notification', 
                                'user_id' => $mentioned_user->ID, 
                                'object_id' => $task_id
                            ]
                        );
                    }
                }
            }


            $comment = get_comment($comment_id);
             ob_start();
             echo '<li class="pzl-comment-item">';
             echo '<div class="pzl-comment-avatar">' . get_avatar($comment->user_id, 32) . '</div>';
             echo '<div class="pzl-comment-content"><p><strong>' . esc_html($comment->comment_author) . '</strong>: ' . wp_kses_post($comment->comment_content) . '</p><span class="pzl-comment-date">' . human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' پیش</span></div>';
             echo '</li>';
             $comment_html = ob_get_clean();
             wp_send_json_success(['comment_html' => $comment_html]);
        } else {
             wp_send_json_error(['message' => 'خطا در ثبت نظر.']);
        }
    }

    public function manage_checklist() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['sub_action'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $sub_action = sanitize_key($_POST['sub_action']);
        $checklist = get_post_meta($task_id, '_task_checklist', true);
        if (!is_array($checklist)) {
            $checklist = [];
        }

        switch ($sub_action) {
            case 'add':
                $text = sanitize_text_field($_POST['text']);
                if (empty($text)) wp_send_json_error(['message' => 'متن نمی‌تواند خالی باشد.']);
                $item_id = 'item_' . time();
                $checklist[$item_id] = ['text' => $text, 'checked' => false];
                $this->_log_task_activity($task_id, sprintf('آیتم چک‌لیست "%s" را اضافه کرد.', $text));
                break;

            case 'toggle':
                $item_id = sanitize_key($_POST['item_id']);
                if (isset($checklist[$item_id])) {
                    $checklist[$item_id]['checked'] = !$checklist[$item_id]['checked'];
                    $log_action = $checklist[$item_id]['checked'] ? 'کامل' : 'ناکامل';
                    $this->_log_task_activity($task_id, sprintf('وضعیت آیتم چک‌لیست "%s" را به %s تغییر داد.', $checklist[$item_id]['text'], $log_action));
                }
                break;

            case 'delete':
                $item_id = sanitize_key($_POST['item_id']);
                if (isset($checklist[$item_id])) {
                    $this->_log_task_activity($task_id, sprintf('آیتم چک‌لیست "%s" را حذف کرد.', $checklist[$item_id]['text']));
                    unset($checklist[$item_id]);
                }
                break;
        }

        update_post_meta($task_id, '_task_checklist', $checklist);
        wp_send_json_success(['checklist' => $checklist]);
    }

    public function log_time() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['hours'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $hours = floatval($_POST['hours']);
        $description = sanitize_text_field($_POST['description']);
        
        if ($hours <= 0) {
            wp_send_json_error(['message' => 'ساعت وارد شده باید بزرگتر از صفر باشد.']);
        }
        
        $time_logs = get_post_meta($task_id, '_task_time_logs', true);
        if (!is_array($time_logs)) {
            $time_logs = [];
        }
        
        $current_user = wp_get_current_user();
        $new_log = [
            'user_id' => $current_user->ID,
            'user_name' => $current_user->display_name,
            'hours' => $hours,
            'description' => $description,
            'date' => current_time('mysql'),
        ];
        
        $time_logs[] = $new_log;
        update_post_meta($task_id, '_task_time_logs', $time_logs);
        
        $this->_log_task_activity($task_id, sprintf('%.2f ساعت زمان ثبت کرد.', $hours));

        wp_send_json_success(['new_log' => $new_log, 'total_hours' => array_sum(wp_list_pluck($time_logs, 'hours'))]);
    }

    public function save_status_order() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options') || !isset($_POST['order'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $order = $_POST['order'];
        foreach ($order as $index => $term_id) {
            global $wpdb;
            $wpdb->update($wpdb->terms, ['term_order' => $index + 1], ['term_id' => $term_id]);
        }
        clean_term_cache(array_values($order), 'task_status');
        wp_send_json_success(['message' => 'ترتیب وضعیت‌ها ذخیره شد.']);
    }

    public function add_new_status() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options') || !isset($_POST['name'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $name = sanitize_text_field($_POST['name']);
        if (empty($name)) {
            wp_send_json_error(['message' => 'نام وضعیت نمی‌تواند خالی باشد.']);
        }

        $result = wp_insert_term($name, 'task_status');

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'وضعیت جدید اضافه شد.',
            'term_id' => $result['term_id'],
            'name' => $name,
            'slug' => get_term($result['term_id'])->slug
        ]);
    }

    public function delete_status() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('manage_options') || !isset($_POST['term_id'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $term_id = intval($_POST['term_id']);
        
        $default_term = get_term_by('slug', 'to-do', 'task_status');
        if (!$default_term || $default_term->term_id == $term_id) {
            wp_send_json_error(['message' => 'وضعیت پیش‌فرض "To Do" یافت نشد یا در حال حذف آن هستید.']);
        }

        $args = array(
            'post_type' => 'task',
            'tax_query' => [['taxonomy' => 'task_status','field' => 'term_id','terms' => $term_id]],
            'posts_per_page' => -1,
        );
        $tasks_to_reassign = get_posts($args);
        foreach ($tasks_to_reassign as $task) {
            wp_set_object_terms($task->ID, $default_term->term_id, 'task_status');
        }

        $result = wp_delete_term($term_id, 'task_status');

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'وضعیت حذف شد و وظایف آن منتقل شدند.']);
    }

    public function get_notifications() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $user_id = get_current_user_id();
        $args = [
            'post_type' => 'puzzling_log',
            'author' => $user_id,
            'posts_per_page' => 10,
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
            $object_id = get_post_meta($note->ID, '_related_object_id', true);
            $link = $object_id ? add_query_arg(['view' => 'tasks', 'open_task_id' => $object_id], puzzling_get_dashboard_url()) : '#';

            $html .= sprintf(
                '<li data-id="%d" class="%s"><a href="%s">%s <small>%s</small></a></li>',
                esc_attr($note->ID),
                esc_attr($read_class),
                esc_url($link),
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
    
    public function delete_project() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');

        if ( ! current_user_can('delete_posts') || ! isset($_POST['project_id']) || !isset($_POST['_wpnonce']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        $project_id = intval($_POST['project_id']);
        
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'puzzling_delete_project_' . $project_id ) ) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }

        $project = get_post($project_id);

        if ( !$project || $project->post_type !== 'project' ) {
            wp_send_json_error(['message' => 'پروژه یافت نشد.']);
        }

        $result = wp_delete_post($project_id, true);

        if ( $result ) {
            PuzzlingCRM_Logger::add('پروژه حذف شد', ['content' => "پروژه '{$project->post_title}' توسط " . wp_get_current_user()->display_name . " حذف شد.", 'type' => 'log']);
            wp_send_json_success(['message' => 'پروژه با موفقیت حذف شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف پروژه.']);
        }
    }

    /**
     * NEW: Handles quick edits from the Kanban board.
     */
    public function quick_edit_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['field'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        $task_id = intval($_POST['task_id']);
        $field = sanitize_key($_POST['field']);
        $value = sanitize_text_field($_POST['value']);

        switch ($field) {
            case 'title':
                wp_update_post(['ID' => $task_id, 'post_title' => $value]);
                $this->_log_task_activity($task_id, sprintf('عنوان وظیفه را به "%s" تغییر داد.', $value));
                break;
            case 'due_date':
                update_post_meta($task_id, '_due_date', $value);
                 $this->_log_task_activity($task_id, sprintf('ددلاین را به "%s" تغییر داد.', $value));
                break;
            // Add cases for assignee, labels etc.
        }

        wp_send_json_success(['message' => 'وظیفه به‌روزرسانی شد.']);
    }
}