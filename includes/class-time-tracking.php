<?php
/**
 * Time Tracking System Handler
 * Comprehensive time tracking for projects and tasks
 *
 * @package PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 * @author     Arsalan Arghavan
 */

class PuzzlingCRM_Time_Tracking {
    
    private $table_name;
    private $sessions_table;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'puzzling_time_entries';
        $this->sessions_table = $wpdb->prefix . 'puzzling_time_sessions';
        
        add_action('wp_ajax_puzzling_start_timer', [$this, 'start_timer']);
        add_action('wp_ajax_puzzling_stop_timer', [$this, 'stop_timer']);
        add_action('wp_ajax_puzzling_pause_timer', [$this, 'pause_timer']);
        add_action('wp_ajax_puzzling_resume_timer', [$this, 'resume_timer']);
        add_action('wp_ajax_puzzling_get_time_entries', [$this, 'get_time_entries']);
        add_action('wp_ajax_puzzling_add_time_entry', [$this, 'add_time_entry']);
        add_action('wp_ajax_puzzling_update_time_entry', [$this, 'update_time_entry']);
        add_action('wp_ajax_puzzling_delete_time_entry', [$this, 'delete_time_entry']);
        add_action('wp_ajax_puzzling_get_time_reports', [$this, 'get_time_reports']);
        add_action('wp_ajax_puzzling_get_active_timer', [$this, 'get_active_timer']);
        
        // Auto-save time entries
        add_action('wp_ajax_puzzling_auto_save_time', [$this, 'auto_save_time']);
        
        // Cleanup old sessions
        add_action('wp_ajax_puzzling_cleanup_sessions', [$this, 'cleanup_sessions']);
    }
    
    public function start_timer() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $project_id = intval($_POST['project_id'] ?? 0);
        $task_id = intval($_POST['task_id'] ?? 0);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? 'work');
        
        // Check if user already has an active timer
        $active_timer = $this->get_active_timer_for_user($user_id);
        if ($active_timer) {
            wp_send_json_error('شما در حال حاضر یک تایمر فعال دارید');
        }
        
        $session_id = $this->create_time_session([
            'user_id' => $user_id,
            'project_id' => $project_id,
            'task_id' => $task_id,
            'description' => $description,
            'category' => $category,
            'start_time' => current_time('mysql'),
            'status' => 'running'
        ]);
        
        if ($session_id) {
            wp_send_json_success([
                'session_id' => $session_id,
                'message' => 'تایمر با موفقیت شروع شد'
            ]);
        } else {
            wp_send_json_error('خطا در شروع تایمر');
        }
    }
    
    public function stop_timer() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $session_id = intval($_POST['session_id'] ?? 0);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        $session = $this->get_session_by_id($session_id);
        if (!$session || $session->user_id != $user_id) {
            wp_send_json_error('جلسه یافت نشد');
        }
        
        if ($session->status !== 'running') {
            wp_send_json_error('تایمر در حال اجرا نیست');
        }
        
        $end_time = current_time('mysql');
        $duration = $this->calculate_duration($session->start_time, $end_time, $session->paused_duration);
        
        // Update session
        $this->update_session($session_id, [
            'end_time' => $end_time,
            'duration' => $duration,
            'status' => 'completed',
            'description' => $description
        ]);
        
        // Create time entry
        $entry_id = $this->create_time_entry([
            'user_id' => $user_id,
            'project_id' => $session->project_id,
            'task_id' => $session->task_id,
            'session_id' => $session_id,
            'description' => $description,
            'category' => $session->category,
            'start_time' => $session->start_time,
            'end_time' => $end_time,
            'duration' => $duration,
            'is_billable' => $this->is_billable($session->project_id)
        ]);
        
        if ($entry_id) {
            wp_send_json_success([
                'entry_id' => $entry_id,
                'duration' => $this->format_duration($duration),
                'message' => 'تایمر با موفقیت متوقف شد'
            ]);
        } else {
            wp_send_json_error('خطا در ذخیره زمان');
        }
    }
    
    public function pause_timer() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $session_id = intval($_POST['session_id'] ?? 0);
        
        $session = $this->get_session_by_id($session_id);
        if (!$session || $session->user_id != $user_id) {
            wp_send_json_error('جلسه یافت نشد');
        }
        
        if ($session->status !== 'running') {
            wp_send_json_error('تایمر در حال اجرا نیست');
        }
        
        $pause_time = current_time('mysql');
        $current_duration = $this->calculate_duration($session->start_time, $pause_time, $session->paused_duration);
        
        $this->update_session($session_id, [
            'status' => 'paused',
            'pause_time' => $pause_time,
            'paused_duration' => $session->paused_duration + $current_duration
        ]);
        
        wp_send_json_success(['message' => 'تایمر متوقف شد']);
    }
    
    public function resume_timer() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $session_id = intval($_POST['session_id'] ?? 0);
        
        $session = $this->get_session_by_id($session_id);
        if (!$session || $session->user_id != $user_id) {
            wp_send_json_error('جلسه یافت نشد');
        }
        
        if ($session->status !== 'paused') {
            wp_send_json_error('تایمر متوقف نیست');
        }
        
        $resume_time = current_time('mysql');
        $pause_duration = $this->calculate_duration($session->pause_time, $resume_time, 0);
        
        $this->update_session($session_id, [
            'status' => 'running',
            'start_time' => $resume_time,
            'pause_time' => null,
            'paused_duration' => $session->paused_duration + $pause_duration
        ]);
        
        wp_send_json_success(['message' => 'تایمر از سر گرفته شد']);
    }
    
    public function get_time_entries() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $project_id = intval($_GET['project_id'] ?? 0);
        $task_id = intval($_GET['task_id'] ?? 0);
        $date_from = sanitize_text_field($_GET['date_from'] ?? '');
        $date_to = sanitize_text_field($_GET['date_to'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $per_page = intval($_GET['per_page'] ?? 20);
        
        $entries = $this->query_time_entries([
            'user_id' => $user_id,
            'project_id' => $project_id,
            'task_id' => $task_id,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'page' => $page,
            'per_page' => $per_page
        ]);
        
        wp_send_json_success($entries);
    }
    
    public function add_time_entry() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $project_id = intval($_POST['project_id'] ?? 0);
        $task_id = intval($_POST['task_id'] ?? 0);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? 'work');
        $start_time = sanitize_text_field($_POST['start_time'] ?? '');
        $end_time = sanitize_text_field($_POST['end_time'] ?? '');
        $duration = intval($_POST['duration'] ?? 0);
        $is_billable = isset($_POST['is_billable']) ? (bool) $_POST['is_billable'] : true;
        
        if (empty($start_time) || empty($end_time)) {
            wp_send_json_error('زمان شروع و پایان الزامی است');
        }
        
        if (!$duration) {
            $duration = $this->calculate_duration($start_time, $end_time, 0);
        }
        
        $entry_id = $this->create_time_entry([
            'user_id' => $user_id,
            'project_id' => $project_id,
            'task_id' => $task_id,
            'description' => $description,
            'category' => $category,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'duration' => $duration,
            'is_billable' => $is_billable
        ]);
        
        if ($entry_id) {
            wp_send_json_success([
                'entry_id' => $entry_id,
                'message' => 'ورودی زمان با موفقیت اضافه شد'
            ]);
        } else {
            wp_send_json_error('خطا در اضافه کردن ورودی زمان');
        }
    }
    
    public function update_time_entry() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $entry_id = intval($_POST['entry_id'] ?? 0);
        $user_id = get_current_user_id();
        
        $entry = $this->get_entry_by_id($entry_id);
        if (!$entry || $entry->user_id != $user_id) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $update_data = [];
        
        if (isset($_POST['description'])) {
            $update_data['description'] = sanitize_textarea_field($_POST['description']);
        }
        
        if (isset($_POST['category'])) {
            $update_data['category'] = sanitize_text_field($_POST['category']);
        }
        
        if (isset($_POST['start_time'])) {
            $update_data['start_time'] = sanitize_text_field($_POST['start_time']);
        }
        
        if (isset($_POST['end_time'])) {
            $update_data['end_time'] = sanitize_text_field($_POST['end_time']);
        }
        
        if (isset($_POST['is_billable'])) {
            $update_data['is_billable'] = (bool) $_POST['is_billable'];
        }
        
        // Recalculate duration if times changed
        if (isset($update_data['start_time']) || isset($update_data['end_time'])) {
            $start_time = $update_data['start_time'] ?? $entry->start_time;
            $end_time = $update_data['end_time'] ?? $entry->end_time;
            $update_data['duration'] = $this->calculate_duration($start_time, $end_time, 0);
        }
        
        $result = $this->update_time_entry_data($entry_id, $update_data);
        
        if ($result) {
            wp_send_json_success(['message' => 'ورودی زمان با موفقیت به‌روزرسانی شد']);
        } else {
            wp_send_json_error('خطا در به‌روزرسانی ورودی زمان');
        }
    }
    
    public function delete_time_entry() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $entry_id = intval($_POST['entry_id'] ?? 0);
        $user_id = get_current_user_id();
        
        $entry = $this->get_entry_by_id($entry_id);
        if (!$entry || $entry->user_id != $user_id) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $result = $this->delete_time_entry_data($entry_id);
        
        if ($result) {
            wp_send_json_success(['message' => 'ورودی زمان با موفقیت حذف شد']);
        } else {
            wp_send_json_error('خطا در حذف ورودی زمان');
        }
    }
    
    public function get_time_reports() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $project_id = intval($_GET['project_id'] ?? 0);
        $date_from = sanitize_text_field($_GET['date_from'] ?? '');
        $date_to = sanitize_text_field($_GET['date_to'] ?? '');
        $group_by = sanitize_text_field($_GET['group_by'] ?? 'day');
        
        $reports = $this->generate_time_reports([
            'user_id' => $user_id,
            'project_id' => $project_id,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'group_by' => $group_by
        ]);
        
        wp_send_json_success($reports);
    }
    
    public function get_active_timer() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $timer = $this->get_active_timer_for_user($user_id);
        
        if ($timer) {
            $current_duration = $this->calculate_duration($timer->start_time, current_time('mysql'), $timer->paused_duration);
            
            wp_send_json_success([
                'session_id' => $timer->id,
                'project_id' => $timer->project_id,
                'task_id' => $timer->task_id,
                'description' => $timer->description,
                'category' => $timer->category,
                'start_time' => $timer->start_time,
                'current_duration' => $current_duration,
                'formatted_duration' => $this->format_duration($current_duration),
                'status' => $timer->status
            ]);
        } else {
            wp_send_json_success(null);
        }
    }
    
    public function auto_save_time() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $session_id = intval($_POST['session_id'] ?? 0);
        
        $session = $this->get_session_by_id($session_id);
        if (!$session || $session->user_id != $user_id) {
            wp_send_json_error('جلسه یافت نشد');
        }
        
        if ($session->status === 'running') {
            $current_duration = $this->calculate_duration($session->start_time, current_time('mysql'), $session->paused_duration);
            
            wp_send_json_success([
                'duration' => $current_duration,
                'formatted_duration' => $this->format_duration($current_duration)
            ]);
        }
        
        wp_send_json_success(['duration' => 0]);
    }
    
    public function cleanup_sessions() {
        // Cleanup old sessions (older than 24 hours)
        global $wpdb;
        
        $old_sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->sessions_table} 
             WHERE status = 'running' 
             AND start_time < %s",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ));
        
        foreach ($old_sessions as $session) {
            // Auto-stop old sessions
            $this->update_session($session->id, [
                'status' => 'auto_stopped',
                'end_time' => current_time('mysql'),
                'duration' => $this->calculate_duration($session->start_time, current_time('mysql'), $session->paused_duration)
            ]);
        }
        
        wp_send_json_success(['cleaned' => count($old_sessions)]);
    }
    
    private function create_time_session($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->sessions_table,
            [
                'user_id' => $data['user_id'],
                'project_id' => $data['project_id'],
                'task_id' => $data['task_id'],
                'description' => $data['description'],
                'category' => $data['category'],
                'start_time' => $data['start_time'],
                'status' => $data['status'],
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    private function create_time_entry($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id' => $data['user_id'],
                'project_id' => $data['project_id'],
                'task_id' => $data['task_id'],
                'session_id' => $data['session_id'] ?? 0,
                'description' => $data['description'],
                'category' => $data['category'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'duration' => $data['duration'],
                'is_billable' => $data['is_billable'] ? 1 : 0,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    private function update_session($session_id, $data) {
        global $wpdb;
        
        $format = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['user_id', 'project_id', 'task_id', 'duration', 'paused_duration'])) {
                $format[] = '%d';
            } else {
                $format[] = '%s';
            }
        }
        
        return $wpdb->update(
            $this->sessions_table,
            $data,
            ['id' => $session_id],
            $format,
            ['%d']
        );
    }
    
    private function update_time_entry_data($entry_id, $data) {
        global $wpdb;
        
        $format = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['user_id', 'project_id', 'task_id', 'session_id', 'duration', 'is_billable'])) {
                $format[] = '%d';
            } else {
                $format[] = '%s';
            }
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $entry_id],
            $format,
            ['%d']
        );
    }
    
    private function delete_time_entry_data($entry_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $entry_id],
            ['%d']
        );
    }
    
    private function get_active_timer_for_user($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->sessions_table} 
             WHERE user_id = %d AND status IN ('running', 'paused') 
             ORDER BY start_time DESC LIMIT 1",
            $user_id
        ));
    }
    
    private function get_session_by_id($session_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->sessions_table} WHERE id = %d",
            $session_id
        ));
    }
    
    private function get_entry_by_id($entry_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $entry_id
        ));
    }
    
    private function query_time_entries($args = []) {
        global $wpdb;
        
        $defaults = [
            'user_id' => 0,
            'project_id' => 0,
            'task_id' => 0,
            'date_from' => '',
            'date_to' => '',
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
        
        if ($args['project_id'] > 0) {
            $where_conditions[] = 'project_id = %d';
            $where_values[] = $args['project_id'];
        }
        
        if ($args['task_id'] > 0) {
            $where_conditions[] = 'task_id = %d';
            $where_values[] = $args['task_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where_conditions[] = 'DATE(start_time) >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_conditions[] = 'DATE(start_time) <= %s';
            $where_values[] = $args['date_to'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total = $wpdb->get_var($count_query);
        
        // Get entries
        $offset = ($args['page'] - 1) * $args['per_page'];
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY start_time DESC LIMIT %d OFFSET %d";
        
        $query_values = array_merge($where_values, [$args['per_page'], $offset]);
        $query = $wpdb->prepare($query, $query_values);
        
        $entries = $wpdb->get_results($query);
        
        // Format entries
        $formatted_entries = [];
        foreach ($entries as $entry) {
            $formatted_entries[] = $this->format_time_entry($entry);
        }
        
        return [
            'entries' => $formatted_entries,
            'total' => intval($total),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
            'total_pages' => ceil($total / $args['per_page'])
        ];
    }
    
    private function format_time_entry($entry) {
        $user = get_user_by('ID', $entry->user_id);
        $project = $entry->project_id ? get_post($entry->project_id) : null;
        $task = $entry->task_id ? get_post($entry->task_id) : null;
        
        return [
            'id' => $entry->id,
            'user_id' => $entry->user_id,
            'user_name' => $user ? $user->display_name : 'کاربر ناشناس',
            'project_id' => $entry->project_id,
            'project_title' => $project ? $project->post_title : '',
            'task_id' => $entry->task_id,
            'task_title' => $task ? $task->post_title : '',
            'description' => $entry->description,
            'category' => $entry->category,
            'category_label' => $this->get_category_label($entry->category),
            'start_time' => $entry->start_time,
            'end_time' => $entry->end_time,
            'formatted_start_time' => $this->format_datetime($entry->start_time),
            'formatted_end_time' => $this->format_datetime($entry->end_time),
            'duration' => $entry->duration,
            'formatted_duration' => $this->format_duration($entry->duration),
            'is_billable' => (bool) $entry->is_billable,
            'created_at' => $entry->created_at
        ];
    }
    
    private function generate_time_reports($args) {
        global $wpdb;
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        if ($args['user_id'] > 0) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        if ($args['project_id'] > 0) {
            $where_conditions[] = 'project_id = %d';
            $where_values[] = $args['project_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where_conditions[] = 'DATE(start_time) >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_conditions[] = 'DATE(start_time) <= %s';
            $where_values[] = $args['date_to'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $group_by_field = $args['group_by'] === 'day' ? 'DATE(start_time)' : 
                         ($args['group_by'] === 'week' ? 'YEARWEEK(start_time)' : 
                         ($args['group_by'] === 'month' ? 'DATE_FORMAT(start_time, "%Y-%m")' : 'DATE(start_time)'));
        
        $query = "SELECT 
                    {$group_by_field} as period,
                    SUM(duration) as total_duration,
                    COUNT(*) as entry_count,
                    SUM(CASE WHEN is_billable = 1 THEN duration ELSE 0 END) as billable_duration
                  FROM {$this->table_name} 
                  WHERE {$where_clause} 
                  GROUP BY {$group_by_field} 
                  ORDER BY period DESC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $results = $wpdb->get_results($query);
        
        $reports = [];
        foreach ($results as $result) {
            $reports[] = [
                'period' => $result->period,
                'total_duration' => intval($result->total_duration),
                'formatted_duration' => $this->format_duration($result->total_duration),
                'entry_count' => intval($result->entry_count),
                'billable_duration' => intval($result->billable_duration),
                'formatted_billable_duration' => $this->format_duration($result->billable_duration)
            ];
        }
        
        return $reports;
    }
    
    private function calculate_duration($start_time, $end_time, $paused_duration = 0) {
        $start = strtotime($start_time);
        $end = strtotime($end_time);
        $duration = $end - $start;
        
        return max(0, $duration - $paused_duration);
    }
    
    private function format_duration($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%02d:%02d', $minutes, $seconds);
        }
    }
    
    private function format_datetime($datetime) {
        return date('Y/m/d H:i', strtotime($datetime));
    }
    
    private function get_category_label($category) {
        $labels = [
            'work' => 'کار',
            'meeting' => 'جلسه',
            'break' => 'استراحت',
            'training' => 'آموزش',
            'other' => 'سایر'
        ];
        
        return $labels[$category] ?? $category;
    }
    
    private function is_billable($project_id) {
        if (!$project_id) return false;
        
        $billable = get_post_meta($project_id, '_is_billable', true);
        return $billable === 'yes' || $billable === '1';
    }
    
    public static function create_time_tracking_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Time entries table
        $entries_table = $wpdb->prefix . 'puzzling_time_entries';
        $entries_sql = "CREATE TABLE $entries_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            project_id int(11) DEFAULT 0,
            task_id int(11) DEFAULT 0,
            session_id int(11) DEFAULT 0,
            description text,
            category varchar(50) DEFAULT 'work',
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            duration int(11) NOT NULL,
            is_billable tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY project_id (project_id),
            KEY task_id (task_id),
            KEY start_time (start_time)
        ) $charset_collate;";
        
        // Time sessions table
        $sessions_table = $wpdb->prefix . 'puzzling_time_sessions';
        $sessions_sql = "CREATE TABLE $sessions_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            project_id int(11) DEFAULT 0,
            task_id int(11) DEFAULT 0,
            description text,
            category varchar(50) DEFAULT 'work',
            start_time datetime NOT NULL,
            end_time datetime NULL,
            pause_time datetime NULL,
            duration int(11) DEFAULT 0,
            paused_duration int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'running',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY project_id (project_id),
            KEY task_id (task_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($entries_sql);
        dbDelta($sessions_sql);
    }
}
