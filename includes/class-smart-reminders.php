<?php
/**
 * Smart Reminders & Notifications Handler
 * Intelligent reminder system with multiple notification channels
 *
 * @package PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 * @author     Arsalan Arghavan
 */

class PuzzlingCRM_Smart_Reminders {
    
    private $table_name;
    private $notification_channels = ['email', 'sms', 'push', 'in_app'];
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'puzzling_reminders';
        
        add_action('wp_ajax_puzzling_create_reminder', [$this, 'create_reminder']);
        add_action('wp_ajax_puzzling_get_reminders', [$this, 'get_reminders']);
        add_action('wp_ajax_puzzling_update_reminder', [$this, 'update_reminder']);
        add_action('wp_ajax_puzzling_delete_reminder', [$this, 'delete_reminder']);
        add_action('wp_ajax_puzzling_snooze_reminder', [$this, 'snooze_reminder']);
        add_action('wp_ajax_puzzling_mark_reminder_completed', [$this, 'mark_reminder_completed']);
        
        // Cron hooks for processing reminders
        add_action('puzzling_process_reminders', [$this, 'process_reminders']);
        add_action('puzzling_send_reminder_notifications', [$this, 'send_reminder_notifications']);
        
        // Schedule cron events
        add_action('init', [$this, 'schedule_cron_events']);
        
        // Smart reminder triggers
        add_action('save_post', [$this, 'check_smart_reminders'], 10, 3);
        add_action('transition_post_status', [$this, 'check_status_change_reminders'], 10, 3);
        add_action('wp_login', [$this, 'check_login_reminders'], 10, 2);
    }
    
    public function create_reminder() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $reminder_date = sanitize_text_field($_POST['reminder_date'] ?? '');
        $reminder_time = sanitize_text_field($_POST['reminder_time'] ?? '');
        $priority = sanitize_text_field($_POST['priority'] ?? 'medium');
        $channels = $_POST['channels'] ?? ['in_app'];
        $object_type = sanitize_text_field($_POST['object_type'] ?? '');
        $object_id = intval($_POST['object_id'] ?? 0);
        $recurring = sanitize_text_field($_POST['recurring'] ?? 'none');
        $recurring_interval = intval($_POST['recurring_interval'] ?? 1);
        
        if (empty($title) || empty($reminder_date)) {
            wp_send_json_error('عنوان و تاریخ یادآوری الزامی است');
        }
        
        $reminder_datetime = $reminder_date . ' ' . $reminder_time;
        if (strtotime($reminder_datetime) <= time()) {
            wp_send_json_error('تاریخ یادآوری باید در آینده باشد');
        }
        
        $reminder_id = $this->save_reminder([
            'user_id' => $user_id,
            'title' => $title,
            'description' => $description,
            'reminder_datetime' => $reminder_datetime,
            'priority' => $priority,
            'channels' => $channels,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'recurring' => $recurring,
            'recurring_interval' => $recurring_interval,
            'status' => 'pending'
        ]);
        
        if ($reminder_id) {
            wp_send_json_success(['reminder_id' => $reminder_id, 'message' => 'یادآوری با موفقیت ایجاد شد']);
        } else {
            wp_send_json_error('خطا در ایجاد یادآوری');
        }
    }
    
    public function get_reminders() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $status = sanitize_text_field($_GET['status'] ?? 'all');
        $priority = sanitize_text_field($_GET['priority'] ?? 'all');
        $object_type = sanitize_text_field($_GET['object_type'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $per_page = intval($_GET['per_page'] ?? 20);
        
        $reminders = $this->query_reminders([
            'user_id' => $user_id,
            'status' => $status,
            'priority' => $priority,
            'object_type' => $object_type,
            'page' => $page,
            'per_page' => $per_page
        ]);
        
        wp_send_json_success($reminders);
    }
    
    public function update_reminder() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $reminder_id = intval($_POST['reminder_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$this->can_edit_reminder($reminder_id, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $update_data = [];
        
        if (isset($_POST['title'])) {
            $update_data['title'] = sanitize_text_field($_POST['title']);
        }
        
        if (isset($_POST['description'])) {
            $update_data['description'] = sanitize_textarea_field($_POST['description']);
        }
        
        if (isset($_POST['reminder_date']) && isset($_POST['reminder_time'])) {
            $reminder_datetime = sanitize_text_field($_POST['reminder_date']) . ' ' . sanitize_text_field($_POST['reminder_time']);
            $update_data['reminder_datetime'] = $reminder_datetime;
        }
        
        if (isset($_POST['priority'])) {
            $update_data['priority'] = sanitize_text_field($_POST['priority']);
        }
        
        if (isset($_POST['channels'])) {
            $update_data['channels'] = json_encode($_POST['channels']);
        }
        
        if (isset($_POST['recurring'])) {
            $update_data['recurring'] = sanitize_text_field($_POST['recurring']);
        }
        
        if (isset($_POST['recurring_interval'])) {
            $update_data['recurring_interval'] = intval($_POST['recurring_interval']);
        }
        
        $result = $this->update_reminder_data($reminder_id, $update_data);
        
        if ($result) {
            wp_send_json_success(['message' => 'یادآوری با موفقیت به‌روزرسانی شد']);
        } else {
            wp_send_json_error('خطا در به‌روزرسانی یادآوری');
        }
    }
    
    public function delete_reminder() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $reminder_id = intval($_POST['reminder_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$this->can_edit_reminder($reminder_id, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $result = $this->delete_reminder_data($reminder_id);
        
        if ($result) {
            wp_send_json_success(['message' => 'یادآوری با موفقیت حذف شد']);
        } else {
            wp_send_json_error('خطا در حذف یادآوری');
        }
    }
    
    public function snooze_reminder() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $reminder_id = intval($_POST['reminder_id'] ?? 0);
        $snooze_minutes = intval($_POST['snooze_minutes'] ?? 15);
        $user_id = get_current_user_id();
        
        if (!$this->can_edit_reminder($reminder_id, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $new_datetime = date('Y-m-d H:i:s', strtotime("+{$snooze_minutes} minutes"));
        
        $result = $this->update_reminder_data($reminder_id, [
            'reminder_datetime' => $new_datetime,
            'status' => 'pending',
            'snooze_count' => $this->get_reminder_snooze_count($reminder_id) + 1
        ]);
        
        if ($result) {
            wp_send_json_success(['message' => "یادآوری برای {$snooze_minutes} دقیقه به تعویق افتاد"]);
        } else {
            wp_send_json_error('خطا در به تعویق انداختن یادآوری');
        }
    }
    
    public function mark_reminder_completed() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $reminder_id = intval($_POST['reminder_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$this->can_edit_reminder($reminder_id, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $result = $this->update_reminder_data($reminder_id, [
            'status' => 'completed',
            'completed_at' => current_time('mysql')
        ]);
        
        if ($result) {
            wp_send_json_success(['message' => 'یادآوری به عنوان تکمیل شده علامت‌گذاری شد']);
        } else {
            wp_send_json_error('خطا در علامت‌گذاری یادآوری');
        }
    }
    
    public function process_reminders() {
        global $wpdb;
        
        $current_time = current_time('mysql');
        
        // Get due reminders
        $due_reminders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE status = 'pending' 
             AND reminder_datetime <= %s 
             ORDER BY reminder_datetime ASC",
            $current_time
        ));
        
        foreach ($due_reminders as $reminder) {
            $this->send_reminder_notification($reminder);
            
            // Handle recurring reminders
            if ($reminder->recurring !== 'none') {
                $this->create_next_recurring_reminder($reminder);
            }
            
            // Mark as sent
            $wpdb->update(
                $this->table_name,
                ['status' => 'sent', 'sent_at' => $current_time],
                ['id' => $reminder->id],
                ['%s', '%s'],
                ['%d']
            );
        }
    }
    
    public function send_reminder_notifications() {
        // This method is called by cron to send notifications
        $this->process_reminders();
    }
    
    private function send_reminder_notification($reminder) {
        $channels = json_decode($reminder->channels, true) ?: ['in_app'];
        $user = get_user_by('ID', $reminder->user_id);
        
        if (!$user) return;
        
        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    $this->send_email_reminder($user, $reminder);
                    break;
                case 'sms':
                    $this->send_sms_reminder($user, $reminder);
                    break;
                case 'push':
                    $this->send_push_reminder($user, $reminder);
                    break;
                case 'in_app':
                    $this->send_in_app_reminder($user, $reminder);
                    break;
            }
        }
    }
    
    private function send_email_reminder($user, $reminder) {
        $subject = sprintf('[یادآوری] %s', $reminder->title);
        $message = $this->format_reminder_message($reminder);
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    private function send_sms_reminder($user, $reminder) {
        $phone = get_user_meta($user->ID, 'phone_number', true);
        if (!$phone) return;
        
        $message = sprintf('یادآوری: %s - %s', $reminder->title, $reminder->description);
        
        // Use your SMS provider here
        // $this->send_sms($phone, $message);
    }
    
    private function send_push_reminder($user, $reminder) {
        // Send push notification via WebSocket
        if (class_exists('PuzzlingCRM_WebSocket_Handler')) {
            $ws_handler = new PuzzlingCRM_WebSocket_Handler();
            $ws_handler->push_notification($user->ID, [
                'title' => 'یادآوری',
                'message' => $reminder->title,
                'type' => 'reminder',
                'action_url' => admin_url('admin.php?page=puzzling-reminders')
            ]);
        }
    }
    
    private function send_in_app_reminder($user, $reminder) {
        // Store in-app notification
        if (class_exists('PuzzlingCRM_WebSocket_Handler')) {
            $ws_handler = new PuzzlingCRM_WebSocket_Handler();
            $ws_handler->create_notification(
                $user->ID,
                'یادآوری',
                $reminder->title,
                'reminder',
                admin_url('admin.php?page=puzzling-reminders')
            );
        }
    }
    
    private function format_reminder_message($reminder) {
        $message = "یادآوری: {$reminder->title}\n\n";
        $message .= "توضیحات: {$reminder->description}\n\n";
        $message .= "اولویت: " . $this->get_priority_label($reminder->priority) . "\n";
        $message .= "تاریخ: " . $this->format_datetime($reminder->reminder_datetime) . "\n\n";
        
        if ($reminder->object_type && $reminder->object_id) {
            $object_url = $this->get_object_url($reminder->object_type, $reminder->object_id);
            if ($object_url) {
                $message .= "لینک مرتبط: {$object_url}\n";
            }
        }
        
        return $message;
    }
    
    private function create_next_recurring_reminder($reminder) {
        $next_datetime = $this->calculate_next_recurring_date($reminder);
        
        if ($next_datetime) {
            $this->save_reminder([
                'user_id' => $reminder->user_id,
                'title' => $reminder->title,
                'description' => $reminder->description,
                'reminder_datetime' => $next_datetime,
                'priority' => $reminder->priority,
                'channels' => json_decode($reminder->channels, true),
                'object_type' => $reminder->object_type,
                'object_id' => $reminder->object_id,
                'recurring' => $reminder->recurring,
                'recurring_interval' => $reminder->recurring_interval,
                'status' => 'pending'
            ]);
        }
    }
    
    private function calculate_next_recurring_date($reminder) {
        $current_datetime = new DateTime($reminder->reminder_datetime);
        $interval = $reminder->recurring_interval;
        
        switch ($reminder->recurring) {
            case 'daily':
                $current_datetime->add(new DateInterval("P{$interval}D"));
                break;
            case 'weekly':
                $current_datetime->add(new DateInterval("P{$interval}W"));
                break;
            case 'monthly':
                $current_datetime->add(new DateInterval("P{$interval}M"));
                break;
            case 'yearly':
                $current_datetime->add(new DateInterval("P{$interval}Y"));
                break;
            default:
                return null;
        }
        
        return $current_datetime->format('Y-m-d H:i:s');
    }
    
    public function check_smart_reminders($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        $post_types = ['project', 'task', 'contract', 'lead', 'ticket'];
        if (!in_array($post->post_type, $post_types)) {
            return;
        }
        
        // Check for smart reminder triggers
        $this->check_due_date_reminders($post);
        $this->check_status_change_reminders($post);
        $this->check_assignee_reminders($post);
    }
    
    private function check_due_date_reminders($post) {
        $due_date = get_post_meta($post->ID, '_due_date', true);
        if (!$due_date) return;
        
        $due_datetime = new DateTime($due_date);
        $now = new DateTime();
        $diff = $now->diff($due_datetime);
        
        // Create reminders for approaching due dates
        if ($diff->days <= 3 && $diff->invert == 0) {
            $this->create_smart_reminder([
                'title' => "موعد {$this->get_post_type_label($post->post_type)} نزدیک است",
                'description' => "{$post->post_title} تا {$diff->days} روز دیگر موعد دارد",
                'object_type' => $post->post_type,
                'object_id' => $post->ID,
                'priority' => $diff->days == 0 ? 'high' : 'medium'
            ]);
        }
    }
    
    private function check_status_change_reminders($post) {
        // This will be called by transition_post_status hook
    }
    
    private function check_assignee_reminders($post) {
        $assigned_to = get_post_meta($post->ID, '_assigned_to', true);
        if (!$assigned_to) return;
        
        $last_activity = get_post_meta($post->ID, '_last_activity', true);
        if (!$last_activity) return;
        
        $last_activity_time = new DateTime($last_activity);
        $now = new DateTime();
        $diff = $now->diff($last_activity_time);
        
        // Create reminder if no activity for 3 days
        if ($diff->days >= 3) {
            $this->create_smart_reminder([
                'title' => "فعالیت در {$this->get_post_type_label($post->post_type)}",
                'description' => "{$post->post_title} برای {$diff->days} روز بدون فعالیت است",
                'object_type' => $post->post_type,
                'object_id' => $post->ID,
                'priority' => 'medium',
                'user_id' => $assigned_to
            ]);
        }
    }
    
    private function create_smart_reminder($data) {
        $defaults = [
            'user_id' => get_current_user_id(),
            'channels' => ['in_app'],
            'recurring' => 'none',
            'recurring_interval' => 1,
            'status' => 'pending'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Set reminder time to 1 hour from now
        $data['reminder_datetime'] = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        return $this->save_reminder($data);
    }
    
    private function save_reminder($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id' => $data['user_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'reminder_datetime' => $data['reminder_datetime'],
                'priority' => $data['priority'],
                'channels' => json_encode($data['channels']),
                'object_type' => $data['object_type'] ?? '',
                'object_id' => $data['object_id'] ?? 0,
                'recurring' => $data['recurring'],
                'recurring_interval' => $data['recurring_interval'],
                'status' => $data['status'],
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    private function query_reminders($args = []) {
        global $wpdb;
        
        $defaults = [
            'user_id' => 0,
            'status' => 'all',
            'priority' => 'all',
            'object_type' => '',
            'page' => 1,
            'per_page' => 20
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        if ($args['user_id'] > 0) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        if ($args['status'] !== 'all') {
            $where_conditions[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if ($args['priority'] !== 'all') {
            $where_conditions[] = 'priority = %s';
            $where_values[] = $args['priority'];
        }
        
        if (!empty($args['object_type'])) {
            $where_conditions[] = 'object_type = %s';
            $where_values[] = $args['object_type'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total = $wpdb->get_var($count_query);
        
        // Get reminders
        $offset = ($args['page'] - 1) * $args['per_page'];
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY reminder_datetime ASC LIMIT %d OFFSET %d";
        
        $query_values = array_merge($where_values, [$args['per_page'], $offset]);
        $query = $wpdb->prepare($query, $query_values);
        
        $reminders = $wpdb->get_results($query);
        
        // Format reminders
        $formatted_reminders = [];
        foreach ($reminders as $reminder) {
            $formatted_reminders[] = $this->format_reminder($reminder);
        }
        
        return [
            'reminders' => $formatted_reminders,
            'total' => intval($total),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
            'total_pages' => ceil($total / $args['per_page'])
        ];
    }
    
    private function format_reminder($reminder) {
        $user = get_user_by('ID', $reminder->user_id);
        
        return [
            'id' => $reminder->id,
            'user_id' => $reminder->user_id,
            'user_name' => $user ? $user->display_name : 'کاربر ناشناس',
            'title' => $reminder->title,
            'description' => $reminder->description,
            'reminder_datetime' => $reminder->reminder_datetime,
            'formatted_datetime' => $this->format_datetime($reminder->reminder_datetime),
            'time_ago' => $this->get_time_ago($reminder->reminder_datetime),
            'priority' => $reminder->priority,
            'priority_label' => $this->get_priority_label($reminder->priority),
            'channels' => json_decode($reminder->channels, true) ?: [],
            'object_type' => $reminder->object_type,
            'object_id' => $reminder->object_id,
            'object_title' => $this->get_object_title($reminder->object_type, $reminder->object_id),
            'recurring' => $reminder->recurring,
            'recurring_interval' => $reminder->recurring_interval,
            'status' => $reminder->status,
            'snooze_count' => intval($reminder->snooze_count),
            'created_at' => $reminder->created_at,
            'sent_at' => $reminder->sent_at,
            'completed_at' => $reminder->completed_at
        ];
    }
    
    private function update_reminder_data($reminder_id, $data) {
        global $wpdb;
        
        $format = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['user_id', 'object_id', 'recurring_interval', 'snooze_count'])) {
                $format[] = '%d';
            } else {
                $format[] = '%s';
            }
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $reminder_id],
            $format,
            ['%d']
        );
    }
    
    private function delete_reminder_data($reminder_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $reminder_id],
            ['%d']
        );
    }
    
    private function can_edit_reminder($reminder_id, $user_id) {
        global $wpdb;
        
        $reminder = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$this->table_name} WHERE id = %d",
            $reminder_id
        ));
        
        return $reminder && ($reminder->user_id == $user_id || current_user_can('manage_options'));
    }
    
    private function get_reminder_snooze_count($reminder_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT snooze_count FROM {$this->table_name} WHERE id = %d",
            $reminder_id
        ));
        
        return intval($count);
    }
    
    private function get_priority_label($priority) {
        $labels = [
            'low' => 'پایین',
            'medium' => 'متوسط',
            'high' => 'بالا',
            'urgent' => 'فوری'
        ];
        
        return $labels[$priority] ?? $priority;
    }
    
    private function get_post_type_label($post_type) {
        $labels = [
            'project' => 'پروژه',
            'task' => 'وظیفه',
            'contract' => 'قرارداد',
            'lead' => 'سرنخ',
            'ticket' => 'تیکت'
        ];
        
        return $labels[$post_type] ?? $post_type;
    }
    
    private function get_object_title($object_type, $object_id) {
        if (!$object_type || !$object_id) return '';
        
        $post = get_post($object_id);
        return $post ? $post->post_title : '';
    }
    
    private function get_object_url($object_type, $object_id) {
        if (!$object_type || !$object_id) return '';
        
        $urls = [
            'project' => admin_url("admin.php?page=puzzling-projects&action=edit&id={$object_id}"),
            'task' => admin_url("admin.php?page=puzzling-tasks&action=edit&id={$object_id}"),
            'contract' => admin_url("admin.php?page=puzzling-contracts&action=edit&id={$object_id}"),
            'lead' => admin_url("admin.php?page=puzzling-leads&action=edit&id={$object_id}"),
            'ticket' => admin_url("admin.php?page=puzzling-tickets&action=edit&id={$object_id}")
        ];
        
        return $urls[$object_type] ?? '';
    }
    
    private function format_datetime($datetime) {
        return date('Y/m/d H:i', strtotime($datetime));
    }
    
    private function get_time_ago($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'همین الان';
        } elseif ($time < 3600) {
            return floor($time / 60) . ' دقیقه پیش';
        } elseif ($time < 86400) {
            return floor($time / 3600) . ' ساعت پیش';
        } else {
            return floor($time / 86400) . ' روز پیش';
        }
    }
    
    public function schedule_cron_events() {
        if (!wp_next_scheduled('puzzling_process_reminders')) {
            wp_schedule_event(time(), 'every_minute', 'puzzling_process_reminders');
        }
    }
    
    public static function create_reminders_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'puzzling_reminders';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            reminder_datetime datetime NOT NULL,
            priority varchar(20) DEFAULT 'medium',
            channels text,
            object_type varchar(50) DEFAULT '',
            object_id int(11) DEFAULT 0,
            recurring varchar(20) DEFAULT 'none',
            recurring_interval int(11) DEFAULT 1,
            status varchar(20) DEFAULT 'pending',
            snooze_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            sent_at datetime NULL,
            completed_at datetime NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY reminder_datetime (reminder_datetime),
            KEY status (status),
            KEY priority (priority)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
