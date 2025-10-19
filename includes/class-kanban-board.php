<?php
/**
 * Kanban Board Handler
 * Drag & Drop Kanban board for project management
 *
 * @package PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 * @author     Arsalan Arghavan
 */

class PuzzlingCRM_Kanban_Board {
    
    private $table_name;
    private $columns = ['backlog', 'in_progress', 'review', 'completed'];
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'puzzling_kanban_cards';
        
        add_action('wp_ajax_puzzling_get_kanban_cards', [$this, 'get_kanban_cards']);
        add_action('wp_ajax_puzzling_move_kanban_card', [$this, 'move_kanban_card']);
        add_action('wp_ajax_puzzling_create_kanban_card', [$this, 'create_kanban_card']);
        add_action('wp_ajax_puzzling_update_kanban_card', [$this, 'update_kanban_card']);
        add_action('wp_ajax_puzzling_delete_kanban_card', [$this, 'delete_kanban_card']);
        add_action('wp_ajax_puzzling_get_kanban_columns', [$this, 'get_kanban_columns']);
        add_action('wp_ajax_puzzling_update_kanban_column', [$this, 'update_kanban_column']);
        
        // Auto-create cards from projects
        add_action('save_post', [$this, 'auto_create_kanban_card'], 10, 3);
        add_action('transition_post_status', [$this, 'update_kanban_card_status'], 10, 3);
    }
    
    public function get_kanban_cards() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $project_id = intval($_GET['project_id'] ?? 0);
        $user_id = get_current_user_id();
        
        $cards = $this->query_kanban_cards($project_id, $user_id);
        
        wp_send_json_success($cards);
    }
    
    public function move_kanban_card() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $card_id = intval($_POST['card_id'] ?? 0);
        $new_column = sanitize_text_field($_POST['new_column'] ?? '');
        $new_position = intval($_POST['new_position'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$this->can_edit_card($card_id, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        if (!in_array($new_column, $this->columns)) {
            wp_send_json_error('Invalid column');
        }
        
        $result = $this->move_card($card_id, $new_column, $new_position);
        
        if ($result) {
            // Update project status based on card column
            $this->update_project_status_from_card($card_id, $new_column);
            
            wp_send_json_success(['message' => 'کارت با موفقیت منتقل شد']);
        } else {
            wp_send_json_error('خطا در انتقال کارت');
        }
    }
    
    public function create_kanban_card() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $project_id = intval($_POST['project_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $column = sanitize_text_field($_POST['column'] ?? 'backlog');
        $priority = sanitize_text_field($_POST['priority'] ?? 'medium');
        $assignee_id = intval($_POST['assignee_id'] ?? 0);
        $due_date = sanitize_text_field($_POST['due_date'] ?? '');
        $tags = $_POST['tags'] ?? [];
        
        if (empty($title)) {
            wp_send_json_error('عنوان کارت الزامی است');
        }
        
        if (!in_array($column, $this->columns)) {
            wp_send_json_error('ستون نامعتبر است');
        }
        
        $card_id = $this->save_kanban_card([
            'project_id' => $project_id,
            'title' => $title,
            'description' => $description,
            'column' => $column,
            'priority' => $priority,
            'assignee_id' => $assignee_id,
            'due_date' => $due_date,
            'tags' => $tags,
            'created_by' => $user_id,
            'position' => $this->get_next_position($project_id, $column)
        ]);
        
        if ($card_id) {
            wp_send_json_success(['card_id' => $card_id, 'message' => 'کارت با موفقیت ایجاد شد']);
        } else {
            wp_send_json_error('خطا در ایجاد کارت');
        }
    }
    
    public function update_kanban_card() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $card_id = intval($_POST['card_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$this->can_edit_card($card_id, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $update_data = [];
        
        if (isset($_POST['title'])) {
            $update_data['title'] = sanitize_text_field($_POST['title']);
        }
        
        if (isset($_POST['description'])) {
            $update_data['description'] = sanitize_textarea_field($_POST['description']);
        }
        
        if (isset($_POST['priority'])) {
            $update_data['priority'] = sanitize_text_field($_POST['priority']);
        }
        
        if (isset($_POST['assignee_id'])) {
            $update_data['assignee_id'] = intval($_POST['assignee_id']);
        }
        
        if (isset($_POST['due_date'])) {
            $update_data['due_date'] = sanitize_text_field($_POST['due_date']);
        }
        
        if (isset($_POST['tags'])) {
            $update_data['tags'] = json_encode($_POST['tags']);
        }
        
        $result = $this->update_card($card_id, $update_data);
        
        if ($result) {
            wp_send_json_success(['message' => 'کارت با موفقیت به‌روزرسانی شد']);
        } else {
            wp_send_json_error('خطا در به‌روزرسانی کارت');
        }
    }
    
    public function delete_kanban_card() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $card_id = intval($_POST['card_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$this->can_edit_card($card_id, $user_id)) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $result = $this->delete_card($card_id);
        
        if ($result) {
            wp_send_json_success(['message' => 'کارت با موفقیت حذف شد']);
        } else {
            wp_send_json_error('خطا در حذف کارت');
        }
    }
    
    public function get_kanban_columns() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        $columns = [
            [
                'id' => 'backlog',
                'title' => 'بک‌لاگ',
                'color' => '#6c757d',
                'icon' => 'ri-inbox-line',
                'description' => 'وظایف در انتظار شروع'
            ],
            [
                'id' => 'in_progress',
                'title' => 'در حال انجام',
                'color' => '#17a2b8',
                'icon' => 'ri-play-circle-line',
                'description' => 'وظایف در حال انجام'
            ],
            [
                'id' => 'review',
                'title' => 'بررسی',
                'color' => '#ffc107',
                'icon' => 'ri-eye-line',
                'description' => 'وظایف آماده بررسی'
            ],
            [
                'id' => 'completed',
                'title' => 'تکمیل شده',
                'color' => '#28a745',
                'icon' => 'ri-checkbox-circle-line',
                'description' => 'وظایف تکمیل شده'
            ]
        ];
        
        wp_send_json_success($columns);
    }
    
    public function update_kanban_column() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions', 'Forbidden', ['response' => 403]);
        }
        
        $column_id = sanitize_text_field($_POST['column_id'] ?? '');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $color = sanitize_hex_color($_POST['color'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        if (!in_array($column_id, $this->columns)) {
            wp_send_json_error('ستون نامعتبر است');
        }
        
        $result = $this->update_column_settings($column_id, [
            'title' => $title,
            'color' => $color,
            'description' => $description
        ]);
        
        if ($result) {
            wp_send_json_success(['message' => 'ستون با موفقیت به‌روزرسانی شد']);
        } else {
            wp_send_json_error('خطا در به‌روزرسانی ستون');
        }
    }
    
    public function auto_create_kanban_card($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        if ($post->post_type !== 'project') {
            return;
        }
        
        // Check if card already exists
        $existing_card = $this->get_card_by_project_id($post_id);
        if ($existing_card) {
            return;
        }
        
        // Create card based on project
        $this->save_kanban_card([
            'project_id' => $post_id,
            'title' => $post->post_title,
            'description' => wp_trim_words($post->post_content, 20),
            'column' => 'backlog',
            'priority' => 'medium',
            'assignee_id' => get_post_meta($post_id, '_assigned_to', true),
            'due_date' => get_post_meta($post_id, '_due_date', true),
            'created_by' => get_current_user_id(),
            'position' => $this->get_next_position($post_id, 'backlog')
        ]);
    }
    
    public function update_kanban_card_status($new_status, $old_status, $post) {
        if ($post->post_type !== 'project') {
            return;
        }
        
        $card = $this->get_card_by_project_id($post->ID);
        if (!$card) {
            return;
        }
        
        $column_mapping = [
            'draft' => 'backlog',
            'pending' => 'backlog',
            'publish' => 'in_progress',
            'private' => 'review',
            'trash' => 'completed'
        ];
        
        $new_column = $column_mapping[$new_status] ?? 'backlog';
        
        if ($new_column !== $card->column) {
            $this->move_card($card->id, $new_column, $this->get_next_position($post->ID, $new_column));
        }
    }
    
    private function query_kanban_cards($project_id, $user_id) {
        global $wpdb;
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        if ($project_id > 0) {
            $where_conditions[] = 'project_id = %d';
            $where_values[] = $project_id;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY column_name, position ASC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $cards = $wpdb->get_results($query);
        
        // Group cards by column
        $grouped_cards = [];
        foreach ($this->columns as $column) {
            $grouped_cards[$column] = [];
        }
        
        foreach ($cards as $card) {
            $formatted_card = $this->format_card($card);
            $grouped_cards[$card->column_name][] = $formatted_card;
        }
        
        return $grouped_cards;
    }
    
    private function format_card($card) {
        $assignee = $card->assignee_id ? get_user_by('ID', $card->assignee_id) : null;
        $creator = get_user_by('ID', $card->created_by);
        $project = get_post($card->project_id);
        
        return [
            'id' => $card->id,
            'project_id' => $card->project_id,
            'project_title' => $project ? $project->post_title : '',
            'title' => $card->title,
            'description' => $card->description,
            'column' => $card->column_name,
            'priority' => $card->priority,
            'priority_label' => $this->get_priority_label($card->priority),
            'assignee_id' => $card->assignee_id,
            'assignee_name' => $assignee ? $assignee->display_name : '',
            'assignee_avatar' => $assignee ? get_avatar_url($assignee->ID, ['size' => 32]) : '',
            'due_date' => $card->due_date,
            'formatted_due_date' => $card->due_date ? $this->format_date($card->due_date) : '',
            'time_ago' => $this->get_time_ago($card->due_date),
            'tags' => json_decode($card->tags, true) ?: [],
            'created_by' => $card->created_by,
            'creator_name' => $creator ? $creator->display_name : '',
            'position' => $card->position,
            'created_at' => $card->created_at,
            'updated_at' => $card->updated_at,
            'is_overdue' => $this->is_overdue($card->due_date),
            'progress' => $this->calculate_card_progress($card)
        ];
    }
    
    private function move_card($card_id, $new_column, $new_position) {
        global $wpdb;
        
        // Update positions of other cards in the new column
        $this->update_positions_for_column($new_column, $new_position);
        
        // Move the card
        return $wpdb->update(
            $this->table_name,
            [
                'column_name' => $new_column,
                'position' => $new_position,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $card_id],
            ['%s', '%d', '%s'],
            ['%d']
        );
    }
    
    private function update_positions_for_column($column, $from_position) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} 
             SET position = position + 1 
             WHERE column_name = %s AND position >= %d",
            $column,
            $from_position
        ));
    }
    
    private function get_next_position($project_id, $column) {
        global $wpdb;
        
        $max_position = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(position) FROM {$this->table_name} 
             WHERE project_id = %d AND column_name = %s",
            $project_id,
            $column
        ));
        
        return ($max_position ?: 0) + 1;
    }
    
    private function save_kanban_card($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'project_id' => $data['project_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'column_name' => $data['column'],
                'priority' => $data['priority'],
                'assignee_id' => $data['assignee_id'],
                'due_date' => $data['due_date'],
                'tags' => json_encode($data['tags']),
                'created_by' => $data['created_by'],
                'position' => $data['position'],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    private function update_card($card_id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        $format = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['assignee_id', 'position'])) {
                $format[] = '%d';
            } else {
                $format[] = '%s';
            }
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $card_id],
            $format,
            ['%d']
        );
    }
    
    private function delete_card($card_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $card_id],
            ['%d']
        );
    }
    
    private function get_card_by_project_id($project_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE project_id = %d",
            $project_id
        ));
    }
    
    private function can_edit_card($card_id, $user_id) {
        global $wpdb;
        
        $card = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $card_id
        ));
        
        if (!$card) {
            return false;
        }
        
        // Check if user is the creator, assignee, or has manage_options capability
        return $card->created_by == $user_id || 
               $card->assignee_id == $user_id || 
               current_user_can('manage_options');
    }
    
    private function update_project_status_from_card($card_id, $column) {
        global $wpdb;
        
        $card = $wpdb->get_row($wpdb->prepare(
            "SELECT project_id FROM {$this->table_name} WHERE id = %d",
            $card_id
        ));
        
        if (!$card) {
            return;
        }
        
        $status_mapping = [
            'backlog' => 'draft',
            'in_progress' => 'publish',
            'review' => 'private',
            'completed' => 'publish'
        ];
        
        $new_status = $status_mapping[$column] ?? 'draft';
        
        wp_update_post([
            'ID' => $card->project_id,
            'post_status' => $new_status
        ]);
    }
    
    private function update_column_settings($column_id, $settings) {
        $option_name = "puzzlingcrm_kanban_column_{$column_id}";
        return update_option($option_name, $settings);
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
    
    private function format_date($date) {
        return date('Y/m/d', strtotime($date));
    }
    
    private function get_time_ago($date) {
        if (!$date) return '';
        
        $time = time() - strtotime($date);
        
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
    
    private function is_overdue($due_date) {
        if (!$due_date) return false;
        
        return strtotime($due_date) < time();
    }
    
    private function calculate_card_progress($card) {
        // Simple progress calculation based on column
        $progress_mapping = [
            'backlog' => 0,
            'in_progress' => 50,
            'review' => 80,
            'completed' => 100
        ];
        
        return $progress_mapping[$card->column_name] ?? 0;
    }
    
    public static function create_kanban_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'puzzling_kanban_cards';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            project_id int(11) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            column_name varchar(50) NOT NULL,
            priority varchar(20) DEFAULT 'medium',
            assignee_id int(11) DEFAULT 0,
            due_date date NULL,
            tags text,
            created_by int(11) NOT NULL,
            position int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY column_name (column_name),
            KEY assignee_id (assignee_id),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
