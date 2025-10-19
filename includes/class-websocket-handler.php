<?php
/**
 * WebSocket Handler for Real-time Notifications
 *
 * @package PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 * @author     Arsalan Arghavan
 */

class PuzzlingCRM_WebSocket_Handler {
    
    /**
     * WebSocket server instance
     */
    private $websocket_server;
    
    /**
     * Connected clients
     */
    private $clients = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'init_websocket']);
        add_action('wp_ajax_puzzling_websocket_auth', [$this, 'handle_websocket_auth']);
        add_action('wp_ajax_nopriv_puzzling_websocket_auth', [$this, 'handle_websocket_auth']);
        add_action('wp_ajax_puzzling_send_notification', [$this, 'send_notification']);
        add_action('wp_ajax_puzzling_get_notifications', [$this, 'get_notifications']);
        add_action('wp_ajax_puzzling_mark_notification_read', [$this, 'mark_notification_read']);
    }
    
    /**
     * Initialize WebSocket server
     */
    public function init_websocket() {
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        
        // Start WebSocket server on a different port
        if (!wp_doing_ajax() && !defined('DOING_CRON')) {
            $this->start_websocket_server();
        }
    }
    
    /**
     * Start WebSocket server
     */
    private function start_websocket_server() {
        // WebSocket server will run on port 8080
        $port = 8080;
        $host = '0.0.0.0';
        
        // Check if server is already running
        if ($this->is_server_running($host, $port)) {
            return;
        }
        
        // Start WebSocket server in background
        $this->start_background_websocket($host, $port);
    }
    
    /**
     * Check if WebSocket server is running
     */
    private function is_server_running($host, $port) {
        $connection = @fsockopen($host, $port, $errno, $errstr, 1);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }
    
    /**
     * Start WebSocket server in background
     */
    private function start_background_websocket($host, $port) {
        $script_path = PUZZLINGCRM_PLUGIN_DIR . 'includes/websocket-server.php';
        
        if (file_exists($script_path)) {
            $command = "php {$script_path} {$host} {$port} > /dev/null 2>&1 &";
            exec($command);
        }
    }
    
    /**
     * Handle WebSocket authentication
     */
    public function handle_websocket_auth() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized', 'Unauthorized', ['response' => 401]);
        }
        
        $user_id = get_current_user_id();
        $token = $this->generate_websocket_token($user_id);
        
        wp_send_json_success([
            'token' => $token,
            'websocket_url' => 'ws://' . $_SERVER['HTTP_HOST'] . ':8080',
            'user_id' => $user_id
        ]);
    }
    
    /**
     * Generate WebSocket authentication token
     */
    private function generate_websocket_token($user_id) {
        $token_data = [
            'user_id' => $user_id,
            'timestamp' => time(),
            'nonce' => wp_create_nonce('websocket_auth_' . $user_id)
        ];
        
        return base64_encode(json_encode($token_data));
    }
    
    /**
     * Send notification to specific user
     */
    public function send_notification() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $user_id = intval($_POST['user_id']);
        $title = sanitize_text_field($_POST['title']);
        $message = sanitize_textarea_field($_POST['message']);
        $type = sanitize_text_field($_POST['type'] ?? 'info');
        $action_url = esc_url_raw($_POST['action_url'] ?? '');
        
        $notification_id = $this->create_notification($user_id, $title, $message, $type, $action_url);
        
        if ($notification_id) {
            // Send via WebSocket if user is online
            $this->push_notification($user_id, [
                'id' => $notification_id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'action_url' => $action_url,
                'timestamp' => current_time('mysql')
            ]);
            
            wp_send_json_success(['notification_id' => $notification_id]);
        } else {
            wp_send_json_error('Failed to create notification');
        }
    }
    
    /**
     * Get notifications for current user
     */
    public function get_notifications() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized', 'Unauthorized', ['response' => 401]);
        }
        
        $user_id = get_current_user_id();
        $page = intval($_GET['page'] ?? 1);
        $per_page = intval($_GET['per_page'] ?? 20);
        $unread_only = $_GET['unread_only'] === 'true';
        
        $notifications = $this->get_user_notifications($user_id, $page, $per_page, $unread_only);
        
        wp_send_json_success($notifications);
    }
    
    /**
     * Mark notification as read
     */
    public function mark_notification_read() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized', 'Unauthorized', ['response' => 401]);
        }
        
        $notification_id = intval($_POST['notification_id']);
        $user_id = get_current_user_id();
        
        $result = $this->mark_notification_as_read($notification_id, $user_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to mark notification as read');
        }
    }
    
    /**
     * Create notification in database
     */
    private function create_notification($user_id, $title, $message, $type, $action_url) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'puzzling_notifications';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'action_url' => $action_url,
                'is_read' => 0,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get user notifications
     */
    private function get_user_notifications($user_id, $page = 1, $per_page = 20, $unread_only = false) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'puzzling_notifications';
        $offset = ($page - 1) * $per_page;
        
        $where_clause = "WHERE user_id = %d";
        $params = [$user_id];
        
        if ($unread_only) {
            $where_clause .= " AND is_read = 0";
        }
        
        $notifications = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                array_merge($params, [$per_page, $offset])
            )
        );
        
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} {$where_clause}",
                $params
            )
        );
        
        return [
            'notifications' => $notifications,
            'total' => intval($total),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
    
    /**
     * Mark notification as read
     */
    private function mark_notification_as_read($notification_id, $user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'puzzling_notifications';
        
        return $wpdb->update(
            $table_name,
            ['is_read' => 1, 'read_at' => current_time('mysql')],
            ['id' => $notification_id, 'user_id' => $user_id],
            ['%d', '%s'],
            ['%d', '%d']
        );
    }
    
    /**
     * Push notification via WebSocket
     */
    private function push_notification($user_id, $notification) {
        // This would be implemented to send data to the WebSocket server
        // For now, we'll store it in a transient for the WebSocket server to pick up
        $transient_key = 'puzzling_ws_notification_' . $user_id . '_' . time();
        set_transient($transient_key, $notification, 60); // 1 minute expiry
    }
    
    /**
     * Create notifications table
     */
    public static function create_notifications_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'puzzling_notifications';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            type varchar(50) DEFAULT 'info',
            action_url varchar(500) DEFAULT '',
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            read_at datetime NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
