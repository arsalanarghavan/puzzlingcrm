<?php
/**
 * Activity Timeline Handler
 * Tracks and displays user activities across the system
 *
 * @package PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 * @author     Arsalan Arghavan
 */

class PuzzlingCRM_Activity_Timeline {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'puzzling_activities';
        
        add_action('wp_ajax_puzzling_get_activities', [$this, 'get_activities']);
        add_action('wp_ajax_puzzling_get_activity_feed', [$this, 'get_activity_feed']);
        add_action('wp_ajax_puzzling_clear_activities', [$this, 'clear_activities']);
        
        // Activity tracking hooks
        add_action('save_post', [$this, 'track_post_activity'], 10, 3);
        add_action('delete_post', [$this, 'track_post_deletion']);
        add_action('user_register', [$this, 'track_user_registration']);
        add_action('profile_update', [$this, 'track_user_update']);
        add_action('wp_login', [$this, 'track_user_login'], 10, 2);
        add_action('wp_logout', [$this, 'track_user_logout']);
        add_action('comment_post', [$this, 'track_comment_activity'], 10, 3);
        add_action('transition_post_status', [$this, 'track_status_change'], 10, 3);
    }
    
    public function track_activity($user_id, $action, $object_type, $object_id, $description, $metadata = []) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'action' => $action,
                'object_type' => $object_type,
                'object_id' => $object_id,
                'description' => $description,
                'metadata' => json_encode($metadata),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    public function track_post_activity($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        $post_types = ['project', 'task', 'contract', 'lead', 'ticket'];
        if (!in_array($post->post_type, $post_types)) {
            return;
        }
        
        $user_id = get_current_user_id();
        $action = $update ? 'updated' : 'created';
        $description = $this->get_post_description($post, $action);
        
        $metadata = [
            'post_title' => $post->post_title,
            'post_status' => $post->post_status,
            'post_type' => $post->post_type
        ];
        
        $this->track_activity($user_id, $action, $post->post_type, $post_id, $description, $metadata);
    }
    
    public function track_post_deletion($post_id) {
        $post = get_post($post_id);
        if (!$post) return;
        
        $post_types = ['project', 'task', 'contract', 'lead', 'ticket'];
        if (!in_array($post->post_type, $post_types)) {
            return;
        }
        
        $user_id = get_current_user_id();
        $description = sprintf('حذف %s: %s', $this->get_post_type_label($post->post_type), $post->post_title);
        
        $metadata = [
            'post_title' => $post->post_title,
            'post_type' => $post->post_type
        ];
        
        $this->track_activity($user_id, 'deleted', $post->post_type, $post_id, $description, $metadata);
    }
    
    public function track_user_registration($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return;
        
        $description = sprintf('کاربر جدید ثبت نام کرد: %s', $user->display_name);
        
        $metadata = [
            'user_email' => $user->user_email,
            'user_roles' => $user->roles
        ];
        
        $this->track_activity($user_id, 'registered', 'user', $user_id, $description, $metadata);
    }
    
    public function track_user_update($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return;
        
        $current_user_id = get_current_user_id();
        $description = sprintf('پروفایل کاربر %s به‌روزرسانی شد', $user->display_name);
        
        $metadata = [
            'user_email' => $user->user_email,
            'updated_by' => $current_user_id
        ];
        
        $this->track_activity($current_user_id, 'updated', 'user', $user_id, $description, $metadata);
    }
    
    public function track_user_login($user_login, $user) {
        $description = sprintf('کاربر وارد سیستم شد: %s', $user->display_name);
        
        $metadata = [
            'user_email' => $user->user_email,
            'login_method' => 'password'
        ];
        
        $this->track_activity($user->ID, 'login', 'user', $user->ID, $description, $metadata);
    }
    
    public function track_user_logout() {
        $user_id = get_current_user_id();
        if (!$user_id) return;
        
        $user = get_user_by('ID', $user_id);
        $description = sprintf('کاربر از سیستم خارج شد: %s', $user->display_name);
        
        $this->track_activity($user_id, 'logout', 'user', $user_id, $description);
    }
    
    public function track_comment_activity($comment_id, $comment_approved, $commentdata) {
        $post_id = $commentdata['comment_post_ID'];
        $post = get_post($post_id);
        
        if (!$post || !in_array($post->post_type, ['project', 'task', 'contract', 'lead', 'ticket'])) {
            return;
        }
        
        $user_id = get_current_user_id();
        $description = sprintf('دیدگاه جدید در %s: %s', $this->get_post_type_label($post->post_type), $post->post_title);
        
        $metadata = [
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'comment_content' => wp_trim_words($commentdata['comment_content'], 20)
        ];
        
        $this->track_activity($user_id, 'commented', $post->post_type, $post_id, $description, $metadata);
    }
    
    public function track_status_change($new_status, $old_status, $post) {
        if ($new_status === $old_status) return;
        
        $post_types = ['project', 'task', 'contract', 'lead', 'ticket'];
        if (!in_array($post->post_type, $post_types)) {
            return;
        }
        
        $user_id = get_current_user_id();
        $description = sprintf('وضعیت %s تغییر کرد از %s به %s', 
            $this->get_post_type_label($post->post_type), 
            $this->get_status_label($old_status), 
            $this->get_status_label($new_status)
        );
        
        $metadata = [
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'old_status' => $old_status,
            'new_status' => $new_status
        ];
        
        $this->track_activity($user_id, 'status_changed', $post->post_type, $post->ID, $description, $metadata);
    }
    
    public function get_activities() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = intval($_GET['user_id'] ?? 0);
        $object_type = sanitize_text_field($_GET['object_type'] ?? '');
        $object_id = intval($_GET['object_id'] ?? 0);
        $action = sanitize_text_field($_GET['action'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $per_page = intval($_GET['per_page'] ?? 20);
        $date_from = sanitize_text_field($_GET['date_from'] ?? '');
        $date_to = sanitize_text_field($_GET['date_to'] ?? '');
        
        $activities = $this->query_activities([
            'user_id' => $user_id,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'action' => $action,
            'page' => $page,
            'per_page' => $per_page,
            'date_from' => $date_from,
            'date_to' => $date_to
        ]);
        
        wp_send_json_success($activities);
    }
    
    public function get_activity_feed() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $limit = intval($_GET['limit'] ?? 10);
        
        $activities = $this->query_activities([
            'user_id' => 0, // All users
            'per_page' => $limit,
            'order_by' => 'created_at',
            'order' => 'DESC'
        ]);
        
        wp_send_json_success($activities);
    }
    
    public function clear_activities() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $days = intval($_POST['days'] ?? 30);
        $result = $this->clear_old_activities($days);
        
        if ($result) {
            wp_send_json_success(['message' => 'فعالیت‌های قدیمی پاک شدند']);
        } else {
            wp_send_json_error('خطا در پاک کردن فعالیت‌ها');
        }
    }
    
    private function query_activities($args = []) {
        global $wpdb;
        
        $defaults = [
            'user_id' => 0,
            'object_type' => '',
            'object_id' => 0,
            'action' => '',
            'page' => 1,
            'per_page' => 20,
            'date_from' => '',
            'date_to' => '',
            'order_by' => 'created_at',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        if ($args['user_id'] > 0) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        if (!empty($args['object_type'])) {
            $where_conditions[] = 'object_type = %s';
            $where_values[] = $args['object_type'];
        }
        
        if ($args['object_id'] > 0) {
            $where_conditions[] = 'object_id = %d';
            $where_values[] = $args['object_id'];
        }
        
        if (!empty($args['action'])) {
            $where_conditions[] = 'action = %s';
            $where_values[] = $args['action'];
        }
        
        if (!empty($args['date_from'])) {
            $where_conditions[] = 'created_at >= %s';
            $where_values[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $where_conditions[] = 'created_at <= %s';
            $where_values[] = $args['date_to'] . ' 23:59:59';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total = $wpdb->get_var($count_query);
        
        // Get activities
        $offset = ($args['page'] - 1) * $args['per_page'];
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$args['order_by']} {$args['order']} LIMIT %d OFFSET %d";
        
        $query_values = array_merge($where_values, [$args['per_page'], $offset]);
        $query = $wpdb->prepare($query, $query_values);
        
        $activities = $wpdb->get_results($query);
        
        // Format activities
        $formatted_activities = [];
        foreach ($activities as $activity) {
            $formatted_activities[] = $this->format_activity($activity);
        }
        
        return [
            'activities' => $formatted_activities,
            'total' => intval($total),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
            'total_pages' => ceil($total / $args['per_page'])
        ];
    }
    
    private function format_activity($activity) {
        $user = get_user_by('ID', $activity->user_id);
        $metadata = json_decode($activity->metadata, true) ?: [];
        
        return [
            'id' => $activity->id,
            'user_id' => $activity->user_id,
            'user_name' => $user ? $user->display_name : 'کاربر ناشناس',
            'user_avatar' => $user ? get_avatar_url($user->ID, ['size' => 32]) : '',
            'action' => $activity->action,
            'object_type' => $activity->object_type,
            'object_id' => $activity->object_id,
            'description' => $activity->description,
            'metadata' => $metadata,
            'ip_address' => $activity->ip_address,
            'created_at' => $activity->created_at,
            'time_ago' => $this->get_time_ago($activity->created_at),
            'icon' => $this->get_action_icon($activity->action),
            'color' => $this->get_action_color($activity->action)
        ];
    }
    
    private function clear_old_activities($days) {
        global $wpdb;
        
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->delete(
            $this->table_name,
            ['created_at' => $date],
            ['%s'],
            ['created_at' => '<']
        );
    }
    
    private function get_post_description($post, $action) {
        $type_label = $this->get_post_type_label($post->post_type);
        
        switch ($action) {
            case 'created':
                return sprintf('ایجاد %s جدید: %s', $type_label, $post->post_title);
            case 'updated':
                return sprintf('به‌روزرسانی %s: %s', $type_label, $post->post_title);
            default:
                return sprintf('%s %s: %s', $action, $type_label, $post->post_title);
        }
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
    
    private function get_status_label($status) {
        $labels = [
            'publish' => 'منتشر شده',
            'draft' => 'پیش‌نویس',
            'pending' => 'در انتظار',
            'private' => 'خصوصی',
            'trash' => 'حذف شده'
        ];
        
        return $labels[$status] ?? $status;
    }
    
    private function get_action_icon($action) {
        $icons = [
            'created' => 'ri-add-circle-line',
            'updated' => 'ri-edit-line',
            'deleted' => 'ri-delete-bin-line',
            'registered' => 'ri-user-add-line',
            'login' => 'ri-login-box-line',
            'logout' => 'ri-logout-box-line',
            'commented' => 'ri-chat-3-line',
            'status_changed' => 'ri-refresh-line'
        ];
        
        return $icons[$action] ?? 'ri-information-line';
    }
    
    private function get_action_color($action) {
        $colors = [
            'created' => '#28a745',
            'updated' => '#17a2b8',
            'deleted' => '#dc3545',
            'registered' => '#6f42c1',
            'login' => '#20c997',
            'logout' => '#6c757d',
            'commented' => '#fd7e14',
            'status_changed' => '#ffc107'
        ];
        
        return $colors[$action] ?? '#6c757d';
    }
    
    private function get_time_ago($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'همین الان';
        } elseif ($time < 3600) {
            return floor($time / 60) . ' دقیقه پیش';
        } elseif ($time < 86400) {
            return floor($time / 3600) . ' ساعت پیش';
        } elseif ($time < 2592000) {
            return floor($time / 86400) . ' روز پیش';
        } else {
            return date('Y/m/d', strtotime($datetime));
        }
    }
    
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    public static function create_activities_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'puzzling_activities';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            action varchar(50) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id int(11) NOT NULL,
            description text NOT NULL,
            metadata longtext,
            ip_address varchar(45) DEFAULT '',
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY object_type (object_type),
            KEY object_id (object_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
