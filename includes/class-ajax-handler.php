<?php
class PuzzlingCRM_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_puzzling_add_task', [$this, 'add_task']);
        add_action('wp_ajax_puzzling_update_task_status', [$this, 'update_task_status']);
        add_action('wp_ajax_puzzling_delete_task', [$this, 'delete_task']);
        add_action('wp_ajax_puzzling_get_notifications', [$this, 'get_notifications']);
        add_action('wp_ajax_puzzling_mark_notification_read', [$this, 'mark_notification_read']);

        // New AJAX actions for Kanban board
        add_action('wp_ajax_puzzling_get_task_details', [$this, 'get_task_details']);
        add_action('wp_ajax_puzzling_save_task_content', [$this, 'save_task_content']);
        add_action('wp_ajax_puzzling_add_task_comment', [$this, 'add_task_comment']);

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

        if (empty($project_id)) {
            wp_send_json_error(['message' => 'لطفاً یک پروژه را برای تسک انتخاب کنید.']);
        }

        $task_id = wp_insert_post([
            'post_title' => $title, 
            'post_content' => $content,
            'post_type' => 'task', 
            'post_status' => 'publish', 
            'post_author' => get_current_user_id()
        ]);

        if ( is_wp_error($task_id) ) {
            wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
        }
        
        update_post_meta($task_id, '_project_id', $project_id);
        update_post_meta($task_id, '_assigned_to', $assigned_to);
        if (!empty($due_date)) update_post_meta($task_id, '_due_date', $due_date);

        wp_set_post_terms($task_id, [$priority_id], 'task_priority');
        wp_set_post_terms($task_id, 'to-do', 'task_status'); // Default status
        
        $this->send_task_assignment_email($assigned_to, $task_id);
        
        $project_title = get_the_title($project_id);
        
        // Notify the assigned user
        PuzzlingCRM_Logger::add('تسک جدید به شما محول شد', ['content' => "تسک '{$title}' در پروژه '{$project_title}' به شما تخصیص داده شد.", 'type' => 'notification', 'user_id' => $assigned_to, 'object_id' => $task_id]);

        $task = get_post($task_id);
        $task_html = function_exists('puzzling_render_task_card') ? puzzling_render_task_card($task) : '';
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

        if ( ! current_user_can('edit_tasks') || ! isset($_POST['task_id']) || !isset($_POST['new_status_slug']) ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        $task_id = intval($_POST['task_id']);
        $new_status_slug = sanitize_key($_POST['new_status_slug']);
        $task = get_post($task_id);

        $term = term_exists($new_status_slug, 'task_status');
        if ($term) {
            wp_set_post_terms($task_id, $term['term_id'], 'task_status');
            
            PuzzlingCRM_Logger::add('وضعیت تسک به‌روز شد', ['content' => "وضعیت تسک '{$task->post_title}' به '{$term['name']}' تغییر یافت.", 'type' => 'log', 'object_id' => $task_id]);

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

        $task_title = $task->post_title; // Save title before deleting
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
        ?>
        <div class="pzl-modal-header">
            <h3 id="pzl-modal-title"><?php echo esc_html($task->post_title); ?></h3>
        </div>
        <div class="pzl-modal-body">
            <h4><i class="fas fa-align-left"></i> توضیحات</h4>
            <div id="pzl-task-description-viewer">
                <?php echo $task->post_content ? wpautop(wp_kses_post($task->post_content)) : '<p class="pzl-no-content">توضیحاتی برای این وظیفه ثبت نشده است. برای افزودن کلیک کنید.</p>'; ?>
            </div>
            <div id="pzl-task-description-editor" style="display: none;">
                <textarea id="pzl-task-content-input" rows="6"><?php echo esc_textarea($task->post_content); ?></textarea>
                <button id="pzl-save-task-content" class="pzl-button">ذخیره</button>
                <button id="pzl-cancel-edit-content" class="pzl-button-secondary">انصراف</button>
            </div>
            <hr>
            <h4><i class="fas fa-comments"></i> فعالیت و نظرات</h4>
            <div class="pzl-task-comments">
                <ul class="pzl-comment-list">
                    <?php
                    $comments = get_comments(['post_id' => $task_id, 'status' => 'approve', 'order' => 'ASC']);
                    if ($comments) {
                        foreach($comments as $comment) {
                            echo '<li class="pzl-comment-item">';
                            echo '<div class="pzl-comment-avatar">' . get_avatar($comment->user_id, 32) . '</div>';
                            echo '<div class="pzl-comment-content"><p><strong>' . esc_html($comment->comment_author) . '</strong>: ' . wp_kses_post($comment->comment_content) . '</p><span class="pzl-comment-date">' . human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' پیش</span></div>';
                            echo '</li>';
                        }
                    } else {
                        echo '<p>هنوز نظری ثبت نشده است.</p>';
                    }
                    ?>
                </ul>
            </div>
            <div class="pzl-add-comment-form">
                <textarea id="pzl-new-comment-text" placeholder="نظر خود را بنویسید..." rows="3"></textarea>
                <button id="pzl-submit-comment" class="pzl-button">ارسال نظر</button>
            </div>
        </div>
        <?php
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


    // --- Other existing functions ---
    
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
}