<?php
/**
 * PuzzlingCRM Task AJAX Handler
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Task_Ajax_Handler {

    public function __construct() {
        // --- Standard Task Actions ---
        add_action('wp_ajax_puzzling_add_task', [$this, 'add_task']);
        add_action('wp_ajax_puzzling_quick_add_task', [$this, 'quick_add_task']);
        add_action('wp_ajax_puzzling_update_task_status', [$this, 'update_task_status']);
        add_action('wp_ajax_puzzling_delete_task', [$this, 'delete_task']);
        add_action('wp_ajax_puzzling_update_task_assignee', [$this, 'ajax_update_task_assignee']);

        // --- Kanban Board & Modal Actions ---
        add_action('wp_ajax_puzzling_get_task_details', [$this, 'get_task_details']);
        add_action('wp_ajax_puzzling_add_task_comment', [$this, 'add_task_comment_new']);
        add_action('wp_ajax_puzzling_save_task_content', [$this, 'save_task_content']);
        add_action('wp_ajax_puzzling_add_task_comment', [$this, 'add_task_comment']);

        // --- Advanced Task Features ---
        add_action('wp_ajax_puzzling_manage_checklist', [$this, 'manage_checklist']);
        add_action('wp_ajax_puzzling_log_time', [$this, 'log_time']);
        add_action('wp_ajax_puzzling_quick_edit_task', [$this, 'quick_edit_task']);

        // --- Advanced Views Data ---
        add_action('wp_ajax_get_tasks_for_views', [$this, 'get_tasks_for_views']);

        // --- Advanced Task Linking ---
        add_action('wp_ajax_puzzling_add_task_link', [$this, 'add_task_link']);
        add_action('wp_ajax_puzzling_remove_task_link', [$this, 'remove_task_link']);
        add_action('wp_ajax_puzzling_search_tasks_for_linking', [$this, 'search_tasks_for_linking']);
        
        // --- Other Task Actions ---
        add_action('wp_ajax_puzzling_bulk_edit_tasks', [$this, 'bulk_edit_tasks']);
        add_action('wp_ajax_puzzling_save_task_as_template', [$this, 'save_task_as_template']);
        
        // --- Calendar & Timeline Actions ---
        add_action('wp_ajax_puzzling_get_tasks_calendar', [$this, 'get_tasks_calendar']);
        add_action('wp_ajax_puzzling_get_tasks_gantt', [$this, 'get_tasks_gantt']);
        add_action('wp_ajax_puzzling_create_task', [$this, 'add_task']); // Alias برای add_task
    }

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
        array_unshift($activity_log, $new_log_entry);
        update_post_meta($task_id, '_task_activity_log', $activity_log);
    }

    private function execute_automations($trigger, $task_id, $trigger_value = null) {
        $settings = PuzzlingCRM_Settings_Handler::get_setting('automations', []);
        $automations = $settings['automations'] ?? [];

        foreach ($automations as $automation) {
            $rule_trigger = $automation['trigger'] ?? '';
            $rule_action = $automation['action'] ?? '';
            $rule_value = $automation['value'] ?? '';
            
            $trigger_condition_met = ($rule_trigger === $trigger);

            if ($trigger_condition_met) {
                switch ($rule_action) {
                    case 'change_status':
                        $term = get_term_by('slug', $rule_value, 'task_status');
                        if ($term) {
                            wp_set_post_terms($task_id, $term->term_id, 'task_status');
                            $this->_log_task_activity($task_id, sprintf('وضعیت به صورت خودکار به "%s" تغییر کرد.', $term->name));
                        }
                        break;
                    case 'assign_user':
                        $user_id = intval($rule_value);
                        if (get_user_by('ID', $user_id)) {
                            update_post_meta($task_id, '_assigned_to', $user_id);
                            $this->_log_task_activity($task_id, sprintf('وظیفه به صورت خودکار به "%s" تخصیص داده شد.', get_the_author_meta('display_name', $user_id)));
                        }
                        break;
                    case 'add_comment':
                        wp_insert_comment(['comment_post_ID' => $task_id, 'comment_content' => $rule_value, 'user_id' => 0, 'comment_author' => 'سیستم اتوماسیون', 'comment_author_email' => 'system@puzzling.com', 'comment_approved' => 1]);
                        $this->_log_task_activity($task_id, 'یک کامنت خودکار توسط سیستم ثبت شد.');
                        break;
                }
            }
        }
    }
    
    private function send_task_assignment_email($user_id, $task_id) {
        $user = get_userdata($user_id);
        $task = get_post($task_id);
        if (!$user || !$task) return;

        $project_title = get_the_title(get_post_meta($task_id, '_project_id', true));
        $dashboard_url = function_exists('puzzling_get_dashboard_url') ? puzzling_get_dashboard_url() : home_url();
        
        $to = $user->user_email;
        $subject = 'یک تسک جدید به شما تخصیص داده شد: ' . $task->post_title;
        $body = '<p>سلام ' . esc_html($user->display_name) . '،</p>';
        $body .= '<p>یک تسک جدید در پروژه <strong>' . esc_html($project_title) . '</strong> به شما محول شده است:</p>';
        $body .= '<ul><li><strong>عنوان تسک:</strong> ' . esc_html($task->post_title) . '</li></ul>';
        $body .= '<p>برای مشاهده جزئیات به داشبورد مراجعه کنید:</p>';
        $body .= '<p><a href="' . esc_url($dashboard_url) . '">رفتن به داشبورد</a></p>';
        
        wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }

    public function add_task() {
		check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
		if (!current_user_can('edit_tasks') || !isset($_POST['title'])) {
			wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
		}
	
		$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
		$notification_prefs = $settings['notifications']['new_task'] ?? [];
	
		$title = sanitize_text_field($_POST['title']);
		$content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
		$task_category_id = isset($_POST['task_category']) ? intval($_POST['task_category']) : 0;
		$due_date = puzzling_jalali_to_gregorian(sanitize_text_field($_POST['due_date']));
		$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
		$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
		$story_points = isset($_POST['story_points']) ? sanitize_text_field($_POST['story_points']) : '';
		$task_labels = isset($_POST['task_labels']) ? sanitize_text_field($_POST['task_labels']) : '';
		$show_to_customer = isset($_POST['show_to_customer']) ? 1 : 0;
	
		$assigned_to_user = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0;
		$assigned_to_role = isset($_POST['assigned_role']) ? intval($_POST['assigned_role']) : 0;
	
		if (empty($project_id) || (empty($assigned_to_user) && empty($assigned_to_role))) {
			wp_send_json_error(['message' => 'لطفاً پروژه و مسئول تسک را انتخاب کنید.']);
		}
	
		$task_id = wp_insert_post([
			'post_title' => $title, 'post_content' => $content, 'post_type' => 'task',
			'post_status' => 'publish', 'post_author' => get_current_user_id(), 'post_parent' => $parent_id
		]);
	
		if (is_wp_error($task_id)) {
			wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
		}
	
		update_post_meta($task_id, '_project_id', $project_id);
		update_post_meta($task_id, '_show_to_customer', $show_to_customer);
		if (!empty($due_date)) update_post_meta($task_id, '_due_date', $due_date);
		if (!empty($story_points)) update_post_meta($task_id, '_story_points', $story_points);
	
		wp_set_post_terms($task_id, $task_category_id, 'task_category');
        wp_set_post_terms($task_id, puzzling_get_default_task_status_slug(), 'task_status');
		if (!empty($task_labels)) wp_set_post_terms($task_id, array_map('trim', explode(',', $task_labels)), 'task_label');
	
		$assigned_user_ids = [];
		if ($assigned_to_user > 0) {
			update_post_meta($task_id, '_assigned_to', $assigned_to_user);
			$assigned_user_ids[] = $assigned_to_user;
		} elseif ($assigned_to_role > 0) {
			update_post_meta($task_id, '_assigned_role', $assigned_to_role);
			$users_with_role = get_users(['tax_query' => [['taxonomy' => 'organizational_position', 'field' => 'term_id', 'terms' => $assigned_to_role]], 'fields' => 'ID']);
			if (!empty($users_with_role)) {
				update_post_meta($task_id, '_assigned_to_multiple', $users_with_role);
				update_post_meta($task_id, '_assigned_to', $users_with_role[0]);
				$assigned_user_ids = $users_with_role;
			}
		}
	
		$this->_log_task_activity($task_id, 'وظیفه را ایجاد کرد.');
	
		if (!empty($_FILES['task_attachments'])) {
            $attachment_ids = [];
            $files = $_FILES['task_attachments'];
            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $_FILES = ["task_attachment_single" => ['name' => $files['name'][$key], 'type' => $files['type'][$key], 'tmp_name' => $files['tmp_name'][$key], 'error' => $files['error'][$key], 'size' => $files['size'][$key]]];
                    $attachment_id = media_handle_upload("task_attachment_single", $task_id);
                    if (!is_wp_error($attachment_id)) $attachment_ids[] = $attachment_id;
                }
            }
            if(!empty($attachment_ids)) update_post_meta($task_id, '_task_attachments', $attachment_ids);
		}
	
		$project_title = get_the_title($project_id);
		$notification_message_plain = "تسک جدید '{$title}' در پروژه '{$project_title}' به شما تخصیص داده شد.";
		$notification_message_html = "تسک جدید <b>'{$title}'</b> در پروژه <b>'{$project_title}'</b> به شما تخصیص داده شد.";
	
		foreach ($assigned_user_ids as $user_id_to_notify) {
			$user = get_userdata($user_id_to_notify);
			if (!$user) continue;
	
			PuzzlingCRM_Logger::add('تسک جدید به شما محول شد', ['content' => $notification_message_plain, 'type' => 'notification', 'user_id' => $user_id_to_notify, 'object_id' => $task_id]);
	
			if (!empty($notification_prefs['email'])) $this->send_task_assignment_email($user_id_to_notify, $task_id);
	
			if (!empty($notification_prefs['sms'])) {
				$sms_handler = puzzling_get_sms_handler($settings);
				$phone = get_user_meta($user_id_to_notify, 'pzl_mobile_phone', true);
				if ($sms_handler && !empty($phone)) $sms_handler->send_sms($phone, $notification_message_plain);
			}
	
			if (!empty($notification_prefs['telegram'])) {
				$bot_token = $settings['telegram_bot_token'] ?? '';
				$chat_id = $settings['telegram_chat_id'] ?? '';
				if (!empty($bot_token) && !empty($chat_id)) {
					$telegram_handler = new PuzzlingCRM_Telegram_Handler($bot_token, $chat_id);
					$telegram_handler->send_message("کاربر گرامی {$user->display_name},\n" . $notification_message_html);
				}
			}
		}
	
		wp_send_json_success(['message' => 'تسک با موفقیت اضافه شد و اطلاع‌رسانی‌ها ارسال گردید.', 'reload' => true]);
    }

    public function quick_add_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['title'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        $title = sanitize_text_field($_POST['title']);
        $status_slug = sanitize_key($_POST['status_slug']);
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $assigned_to = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0;
        
        if (empty($project_id) || empty($assigned_to)) {
             wp_send_json_error(['message' => 'برای افزودن سریع، لطفاً ابتدا برد را بر اساس پروژه و کارمند فیلتر کنید.']);
        }

        $task_id = wp_insert_post(['post_title' => $title, 'post_type' => 'task', 'post_status' => 'publish', 'post_author' => get_current_user_id()]);
        if (is_wp_error($task_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد تسک.']);
        }

        update_post_meta($task_id, '_project_id', $project_id);
        update_post_meta($task_id, '_assigned_to', $assigned_to);
        wp_set_post_terms($task_id, $status_slug, 'task_status');
        
        $medium_priority = get_term_by('slug', 'medium', 'task_priority');
        if ($medium_priority) {
            wp_set_post_terms($task_id, $medium_priority->term_id, 'task_priority');
        }

		$default_cat = get_term_by('slug', 'project-based', 'task_category');
		if ($default_cat) {
			wp_set_object_terms($task_id, $default_cat->term_id, 'task_category');
		}

        $this->_log_task_activity($task_id, 'وظیفه را به صورت سریع ایجاد کرد.');
        $this->send_task_assignment_email($assigned_to, $task_id);
        
        $project_title = get_the_title($project_id);
        PuzzlingCRM_Logger::add('تسک جدید به شما محول شد', ['content' => "تسک '{$title}' در پروژه '{$project_title}' به شما تخصیص داده شد.", 'type' => 'notification', 'user_id' => $assigned_to, 'object_id' => $task_id]);

        $task_html = function_exists('puzzling_render_task_card') ? puzzling_render_task_card(get_post($task_id)) : '';
        wp_send_json_success(['message' => 'تسک سریع با موفقیت اضافه شد.', 'task_html' => $task_html]);
    }

    public function update_task_status() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        // Allow administrators and managers
        if (!current_user_can('edit_tasks') && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        // Check for both 'status' (from new drag&drop) and 'new_status_slug' (from old system)
        $new_status_slug = isset($_POST['status']) ? sanitize_key($_POST['status']) : (isset($_POST['new_status_slug']) ? sanitize_key($_POST['new_status_slug']) : '');
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        
        if (!$task_id || !$new_status_slug) {
            wp_send_json_error(['message' => 'اطلاعات ناقص.']);
        }

        $rules = PuzzlingCRM_Settings_Handler::get_setting('workflow_rules', []);
        $user = wp_get_current_user();

        if (isset($rules[$new_status_slug]) && !empty($rules[$new_status_slug])) {
            if (empty(array_intersect($rules[$new_status_slug], $user->roles))) {
                wp_send_json_error(['message' => 'شما اجازه انتقال وظیفه به این وضعیت را ندارید.']);
                return;
            }
        }

        $old_status_terms = wp_get_post_terms($task_id, 'task_status');
        $old_status_name = !empty($old_status_terms) ? $old_status_terms[0]->name : 'نامشخص';
        $term = get_term_by('slug', $new_status_slug, 'task_status');

        if ($term) {
            wp_set_post_terms($task_id, $term->term_id, 'task_status');
            $this->_log_task_activity($task_id, sprintf('وضعیت وظیفه را از "%s" به "%s" تغییر داد.', $old_status_name, $term->name));
            $this->execute_automations('status_changed', $task_id, $new_status_slug);
            wp_send_json_success(['message' => 'وضعیت تسک به‌روزرسانی شد.']);
        } else {
             wp_send_json_error(['message' => 'وضعیت نامعتبر است.']);
        }
    }

    public function delete_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('delete_tasks') || !isset($_POST['task_id'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $task = get_post($task_id);
        if (!$task || (!current_user_can('manage_options') && $task->post_author != get_current_user_id())) {
            wp_send_json_error(['message' => 'شما اجازه حذف این تسک را ندارید.']);
        }

        $result = wp_delete_post($task_id, true);
        if ($result) {
            PuzzlingCRM_Logger::add('تسک حذف شد', ['content' => "تسک '{$task->post_title}' توسط " . wp_get_current_user()->display_name . " حذف شد.", 'type' => 'log', 'object_id' => $task_id]);
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
        
        $comment_id = wp_insert_comment(['comment_post_ID' => $task_id, 'comment_author' => $user->display_name, 'comment_author_email' => $user->user_email, 'comment_content' => $comment_text, 'user_id' => $user->ID, 'comment_approved' => 1]);
        if ($comment_id) {
            $this->_log_task_activity($task_id, sprintf('یک نظر جدید ثبت کرد: "%s"', esc_html(wp_trim_words($comment_text, 10))));
            
            preg_match_all('/@(\w+)/', $comment_text, $matches);
            if (!empty($matches[1])) {
                foreach (array_unique($matches[1]) as $login) {
                    if ($mentioned_user = get_user_by('login', $login)) {
                         PuzzlingCRM_Logger::add(sprintf('شما در تسک "%s" منشن شدید', get_the_title($task_id)), ['content' => sprintf('%s شما را در یک نظر منشن کرد.', $user->display_name), 'type' => 'notification', 'user_id' => $mentioned_user->ID, 'object_id' => $task_id]);
                    }
                }
            }
            $this->execute_automations('comment_added', $task_id);

            $comment = get_comment($comment_id);
            ob_start();
            echo '<li class="pzl-comment-item"><div class="pzl-comment-avatar">' . get_avatar($comment->user_id, 32) . '</div><div class="pzl-comment-content"><p><strong>' . esc_html($comment->comment_author) . '</strong>: ' . wp_kses_post(wpautop($comment->comment_content)) . '</p><span class="pzl-comment-date">' . human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' پیش</span></div></li>';
            wp_send_json_success(['comment_html' => ob_get_clean()]);
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
        $checklist = get_post_meta($task_id, '_task_checklist', true) ?: [];

        switch ($sub_action) {
            case 'add':
                $text = sanitize_text_field($_POST['text']);
                if (empty($text)) wp_send_json_error(['message' => 'متن نمی‌تواند خالی باشد.']);
                $checklist['item_' . time()] = ['text' => $text, 'checked' => false];
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
        if ($hours <= 0) wp_send_json_error(['message' => 'ساعت وارد شده باید بزرگتر از صفر باشد.']);
        
        $time_logs = get_post_meta($task_id, '_task_time_logs', true) ?: [];
        $current_user = wp_get_current_user();
        $new_log = ['user_id' => $current_user->ID, 'user_name' => $current_user->display_name, 'hours' => $hours, 'description' => $description, 'date' => current_time('mysql')];
        $time_logs[] = $new_log;
        update_post_meta($task_id, '_task_time_logs', $time_logs);
        
        $this->_log_task_activity($task_id, sprintf('%.2f ساعت زمان ثبت کرد.', $hours));

        wp_send_json_success(['new_log' => $new_log, 'total_hours' => array_sum(wp_list_pluck($time_logs, 'hours'))]);
    }

    public function quick_edit_task() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['field'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }
        
        $task_id = intval($_POST['task_id']);
        $field = sanitize_key($_POST['field']);
        $value = $_POST['value'];

        switch ($field) {
            case 'title':
                wp_update_post(['ID' => $task_id, 'post_title' => sanitize_text_field($value)]);
                $this->_log_task_activity($task_id, sprintf('عنوان وظیفه را به "%s" تغییر داد.', sanitize_text_field($value)));
                break;
            case 'due_date':
                update_post_meta($task_id, '_due_date', puzzling_jalali_to_gregorian(sanitize_text_field($value)));
                $this->_log_task_activity($task_id, sprintf('ددلاین را به "%s" تغییر داد.', sanitize_text_field($value)));
                break;
            case 'assignee':
                $assignee_id = intval($value);
                update_post_meta($task_id, '_assigned_to', $assignee_id);
                $new_assignee = get_userdata($assignee_id);
                if ($new_assignee) {
                    $log_message = sprintf('مسئول وظیفه را به "%s" تغییر داد.', $new_assignee->display_name);
                } else {
                    $log_message = 'مسئول وظیفه را حذف کرد.';
                }
                $this->_log_task_activity($task_id, $log_message);
                break;
        }

        $task_html = function_exists('puzzling_render_task_card') ? puzzling_render_task_card(get_post($task_id)) : '';
        wp_send_json_success(['message' => 'وظیفه به‌روزرسانی شد.', 'task_html' => $task_html]);
    }

    public function get_tasks_for_views() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks')) wp_send_json_error();

        $project_filter = isset($_POST['project_filter']) ? intval($_POST['project_filter']) : 0;
        
        $args = ['post_type' => 'task', 'posts_per_page' => -1];
        if($project_filter > 0){
            $args['meta_query'] = [
                [
                    'key' => '_project_id',
                    'value' => $project_filter
                ]
            ];
        }

        $tasks = new WP_Query($args);
        $events = $gantt_data = $gantt_links = [];

        if ($tasks->have_posts()) {
            while ($tasks->have_posts()) {
                $tasks->the_post();
                $due_date = get_post_meta(get_the_ID(), '_due_date', true);
                if($due_date) {
                    $events[] = ['id' => get_the_ID(), 'title' => get_the_title(), 'start' => $due_date, 'allDay' => true];
                    $gantt_data[] = ['id' => get_the_ID(), 'text' => get_the_title(), 'start_date' => get_the_date('Y-m-d'), 'end_date' => $due_date, 'parent' => get_post()->post_parent, 'open' => true];
                }
                if (get_post()->post_parent != 0) {
                    $gantt_links[] = ['id' => 'link_' . get_the_ID(), 'source' => get_post()->post_parent, 'target' => get_the_ID(), 'type' => '0'];
                }
            }
        }
        wp_reset_postdata();

        wp_send_json_success(['calendar_events' => $events, 'gantt_tasks' => ['data' => $gantt_data, 'links' => $gantt_links]]);
    }

    public function search_tasks_for_linking() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks')) wp_send_json_error();

        $tasks = new WP_Query(['post_type' => 'task', 'posts_per_page' => 10, 's' => sanitize_text_field($_POST['search']), 'post__not_in' => [intval($_POST['current_task_id'])]]);
        $results = [];
        if ($tasks->have_posts()) {
            while ($tasks->have_posts()) { $tasks->the_post(); $results[] = ['id' => get_the_ID(), 'text' => '#' . get_the_ID() . ': ' . get_the_title()]; }
        }
        wp_reset_postdata();
        wp_send_json_success($results);
    }

    public function add_task_link() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks')) wp_send_json_error();

        $from_id = intval($_POST['from_task_id']);
        $to_id = intval($_POST['to_task_id']);
        $type = sanitize_key($_POST['link_type']);

        $links = get_post_meta($from_id, '_task_links', true) ?: [];
        $links[] = ['type' => $type, 'task_id' => $to_id];
        update_post_meta($from_id, '_task_links', $links);

        $inverse_map = ['blocks' => 'is_blocked_by', 'is_blocked_by' => 'blocks', 'relates_to' => 'relates_to'];
        $inverse_links = get_post_meta($to_id, '_task_links', true) ?: [];
        $inverse_links[] = ['type' => $inverse_map[$type], 'task_id' => $from_id];
        update_post_meta($to_id, '_task_links', $inverse_links);
        
        $this->_log_task_activity($from_id, sprintf('وظیفه را به #%d با نوع "%s" پیوند داد.', $to_id, $type));
        $this->_log_task_activity($to_id, sprintf('وظیفه به #%d با نوع "%s" پیوند داده شد.', $from_id, $inverse_map[$type]));

        wp_send_json_success();
    }
    
    public function remove_task_link() {
        // This function would be needed to complete the feature
        wp_send_json_error(['message' => 'Not implemented yet.']);
    }

    public function bulk_edit_tasks() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_ids']) || !is_array($_POST['task_ids'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا اطلاعات ناقص.']);
        }

        $task_ids = array_map('intval', $_POST['task_ids']);
        $actions = $_POST['bulk_actions'];

        foreach ($task_ids as $task_id) {
            if (!empty($actions['status'])) wp_set_post_terms($task_id, sanitize_key($actions['status']), 'task_status');
            if (!empty($actions['assignee'])) update_post_meta($task_id, '_assigned_to', intval($actions['assignee']));
            if (!empty($actions['priority'])) wp_set_post_terms($task_id, intval($actions['priority']), 'task_priority');
        }
        
        wp_send_json_success(['message' => 'وظایف با موفقیت به‌روزرسانی شدند.']);
    }
    
    public function save_task_as_template() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['template_name'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $template_name = sanitize_text_field($_POST['template_name']);
        $source_task = get_post($task_id);

        if (!$source_task) wp_send_json_error(['message' => 'وظیفه منبع یافت نشد.']);

        $template_id = wp_insert_post(['post_title' => $template_name, 'post_content' => $source_task->post_content, 'post_type' => 'pzl_task_template', 'post_status' => 'publish']);
        if (is_wp_error($template_id)) wp_send_json_error(['message' => 'خطا در ایجاد قالب.']);
        
        $priority = wp_get_post_terms($task_id, 'task_priority');
        if(!is_wp_error($priority) && !empty($priority)) update_post_meta($template_id, '_template_priority', $priority[0]->term_id);
        
        update_post_meta($template_id, '_template_story_points', get_post_meta($task_id, '_story_points', true));
        update_post_meta($template_id, '_template_checklist', get_post_meta($task_id, '_task_checklist', true));

        wp_send_json_success(['message' => 'قالب با موفقیت ذخیره شد.']);
    }

    public function ajax_update_task_assignee() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        if (!current_user_can('edit_tasks') || !isset($_POST['task_id']) || !isset($_POST['assignee_id'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $task_id = intval($_POST['task_id']);
        $assignee_id = intval($_POST['assignee_id']);
        $new_assignee = get_userdata($assignee_id);

        if (!$new_assignee && $assignee_id > 0) {
            wp_send_json_error(['message' => 'کاربر انتخاب شده معتبر نیست.']);
        }

        update_post_meta($task_id, '_assigned_to', $assignee_id);
        $log_message = ($assignee_id > 0) ? sprintf('مسئول وظیفه را به "%s" تغییر داد.', $new_assignee->display_name) : 'مسئول وظیفه را حذف کرد.';
        $this->_log_task_activity($task_id, $log_message);
        
        wp_send_json_success(['message' => 'مسئول وظیفه با موفقیت به‌روزرسانی شد.']);
    }
    
    /**
     * Get tasks for calendar view
     */
    public function get_tasks_calendar() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '';
        $end = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : '';
        
        $args = [
            'post_type' => 'task',
            'posts_per_page' => -1,
            'meta_query' => []
        ];
        
        if ($start && $end) {
            $args['meta_query'][] = [
                'key' => '_due_date',
                'value' => [$start, $end],
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            ];
        }
        
        $tasks = get_posts($args);
        $events = [];
        
        foreach ($tasks as $task) {
            $due_date = get_post_meta($task->ID, '_due_date', true);
            if ($due_date) {
                $status_terms = get_the_terms($task->ID, 'task_status');
                $color = '#845adf';
                
                if ($status_terms && !is_wp_error($status_terms)) {
                    $slug = $status_terms[0]->slug;
                    if ($slug === 'done') $color = '#28a745';
                    elseif ($slug === 'in-progress') $color = '#4ebedb';
                    elseif ($slug === 'review') $color = '#ffc107';
                }
                
                $events[] = [
                    'id' => $task->ID,
                    'title' => $task->post_title,
                    'start' => $due_date,
                    'color' => $color,
                    'allDay' => true
                ];
            }
        }
        
        wp_send_json_success(['events' => $events]);
    }
    
    /**
     * Get tasks for Gantt timeline
     */
    public function get_tasks_gantt() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $tasks = get_posts([
            'post_type' => 'task',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'ASC'
        ]);
        
        $gantt_tasks = [];
        
        foreach ($tasks as $task) {
            $start_date = get_post_meta($task->ID, '_start_date', true);
            $due_date = get_post_meta($task->ID, '_due_date', true);
            
            if (!$start_date) $start_date = $task->post_date;
            if (!$due_date) $due_date = date('Y-m-d', strtotime($start_date . ' +7 days'));
            
            $status_terms = get_the_terms($task->ID, 'task_status');
            $progress = 0;
            
            if ($status_terms && !is_wp_error($status_terms)) {
                $slug = $status_terms[0]->slug;
                if ($slug === 'done') $progress = 1;
                elseif ($slug === 'in-progress') $progress = 0.5;
                elseif ($slug === 'review') $progress = 0.8;
            }
            
            $gantt_tasks[] = [
                'id' => $task->ID,
                'text' => $task->post_title,
                'start_date' => date('d-m-Y', strtotime($start_date)),
                'duration' => $this->calculate_duration($start_date, $due_date),
                'progress' => $progress,
                'open' => true
            ];
        }
        
        wp_send_json_success(['tasks' => $gantt_tasks]);
    }
    
    /**
     * Calculate duration between two dates
     */
    private function calculate_duration($start, $end) {
        $start_ts = strtotime($start);
        $end_ts = strtotime($end);
        $diff = $end_ts - $start_ts;
        return max(1, round($diff / (60 * 60 * 24)));
    }
}