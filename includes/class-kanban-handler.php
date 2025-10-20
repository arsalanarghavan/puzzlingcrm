<?php
/**
 * Kanban Board Handler for Projects
 * 
 * Provides Kanban board functionality for project management
 *
 * @package    PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 */

class PuzzlingCRM_Kanban_Handler {

    /**
     * Initialize Kanban Handler
     */
    public function __construct() {
        add_action('wp_ajax_puzzlingcrm_get_kanban_data', [$this, 'ajax_get_kanban_data']);
        add_action('wp_ajax_puzzlingcrm_update_kanban_card', [$this, 'ajax_update_card']);
        add_action('wp_ajax_puzzlingcrm_create_kanban_card', [$this, 'ajax_create_card']);
        add_action('wp_ajax_puzzlingcrm_delete_kanban_card', [$this, 'ajax_delete_card']);
        add_action('wp_ajax_puzzlingcrm_create_kanban_column', [$this, 'ajax_create_column']);
        add_action('wp_ajax_puzzlingcrm_update_kanban_column', [$this, 'ajax_update_column']);
        add_action('wp_ajax_puzzlingcrm_delete_kanban_column', [$this, 'ajax_delete_column']);
        
        add_action('admin_enqueue_scripts', [$this, 'enqueue_kanban_assets']);
    }

    /**
     * Enqueue Kanban assets
     */
    public function enqueue_kanban_assets($hook) {
        if (strpos($hook, 'puzzling-kanban') === false && strpos($hook, 'puzzling-projects') === false) {
            return;
        }

        wp_enqueue_style(
            'puzzlingcrm-kanban',
            PUZZLINGCRM_PLUGIN_URL . 'assets/css/kanban-board.css',
            [],
            PUZZLINGCRM_VERSION
        );

        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
            [],
            '1.15.0',
            true
        );

        wp_enqueue_script(
            'puzzlingcrm-kanban',
            PUZZLINGCRM_PLUGIN_URL . 'assets/js/kanban-board.js',
            ['jquery', 'sortablejs'],
            PUZZLINGCRM_VERSION,
            true
        );
    }

    /**
     * Get default Kanban columns
     */
    public static function get_default_columns() {
        return [
            [
                'id' => 'backlog',
                'title' => 'Backlog',
                'color' => '#6c757d',
                'order' => 0,
                'limit' => null
            ],
            [
                'id' => 'todo',
                'title' => 'انجام نشده',
                'color' => '#007bff',
                'order' => 1,
                'limit' => null
            ],
            [
                'id' => 'in_progress',
                'title' => 'در حال انجام',
                'color' => '#ffc107',
                'order' => 2,
                'limit' => 5
            ],
            [
                'id' => 'review',
                'title' => 'بررسی',
                'color' => '#17a2b8',
                'order' => 3,
                'limit' => null
            ],
            [
                'id' => 'done',
                'title' => 'انجام شده',
                'color' => '#28a745',
                'order' => 4,
                'limit' => null
            ]
        ];
    }

    /**
     * Get Kanban board data
     */
    public static function get_board_data($board_type = 'project', $board_id = 0) {
        global $wpdb;

        // Get columns
        $columns = get_option("puzzlingcrm_kanban_columns_{$board_type}_{$board_id}");
        
        if (!$columns) {
            $columns = self::get_default_columns();
        }

        // Get cards based on board type
        $cards = [];
        
        switch ($board_type) {
            case 'project':
                $cards = self::get_project_tasks($board_id);
                break;
                
            case 'tasks':
                $cards = self::get_all_tasks();
                break;
                
            case 'leads':
                $cards = self::get_leads();
                break;
                
            default:
                $cards = [];
        }

        // Organize cards by column
        $board = [];
        foreach ($columns as $column) {
            $board[$column['id']] = [
                'column' => $column,
                'cards' => array_filter($cards, function($card) use ($column) {
                    return $card['status'] === $column['id'];
                })
            ];
        }

        return $board;
    }

    /**
     * Get project tasks as Kanban cards
     */
    private static function get_project_tasks($project_id) {
        $tasks = get_posts([
            'post_type' => 'puzzling_task',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => '_task_project',
                    'value' => $project_id,
                    'compare' => '='
                ]
            ],
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);

        return array_map(function($task) {
            return self::format_card($task, 'task');
        }, $tasks);
    }

    /**
     * Get all tasks as Kanban cards
     */
    private static function get_all_tasks() {
        $tasks = get_posts([
            'post_type' => 'puzzling_task',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);

        return array_map(function($task) {
            return self::format_card($task, 'task');
        }, $tasks);
    }

    /**
     * Get leads as Kanban cards
     */
    private static function get_leads() {
        $leads = get_posts([
            'post_type' => 'puzzling_lead',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        return array_map(function($lead) {
            return self::format_card($lead, 'lead');
        }, $leads);
    }

    /**
     * Format post as Kanban card
     */
    private static function format_card($post, $type = 'task') {
        $status = get_post_meta($post->ID, "_{$type}_status", true) ?: 'todo';
        $priority = get_post_meta($post->ID, "_{$type}_priority", true) ?: 'normal';
        $assignee = get_post_meta($post->ID, "_{$type}_assignee", true);
        $due_date = get_post_meta($post->ID, "_{$type}_due_date", true);
        
        $assignee_name = '';
        if ($assignee) {
            $user = get_userdata($assignee);
            $assignee_name = $user ? $user->display_name : '';
        }

        // Get labels/tags
        $labels = wp_get_post_terms($post->ID, $type . '_label', ['fields' => 'all']);

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'description' => $post->post_excerpt,
            'status' => $status,
            'priority' => $priority,
            'assignee' => $assignee,
            'assignee_name' => $assignee_name,
            'due_date' => $due_date,
            'labels' => $labels,
            'type' => $type,
            'order' => $post->menu_order,
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified
        ];
    }

    /**
     * Update card position and status
     */
    public static function update_card($card_id, $data) {
        $allowed_fields = ['status', 'order', 'column_id'];
        
        $update_data = [];
        
        // Update status if changed
        if (isset($data['status'])) {
            $post_type = get_post_type($card_id);
            $type = str_replace('puzzling_', '', $post_type);
            update_post_meta($card_id, "_{$type}_status", sanitize_key($data['status']));
            
            // Log activity
            PuzzlingCRM_Activity_Timeline::log([
                'action_type' => 'status_changed',
                'entity_type' => $post_type,
                'entity_id' => $card_id,
                'description' => sprintf('تغییر وضعیت به "%s"', $data['status'])
            ]);
        }

        // Update order
        if (isset($data['order'])) {
            $update_data['menu_order'] = intval($data['order']);
        }

        if (!empty($update_data)) {
            $update_data['ID'] = $card_id;
            wp_update_post($update_data);
        }

        return true;
    }

    /**
     * Create new Kanban card
     */
    public static function create_card($data) {
        $defaults = [
            'title' => '',
            'description' => '',
            'type' => 'task',
            'status' => 'todo',
            'priority' => 'normal',
            'assignee' => 0,
            'project_id' => 0,
            'due_date' => '',
            'labels' => []
        ];

        $data = wp_parse_args($data, $defaults);

        $post_type = 'puzzling_' . $data['type'];

        $post_id = wp_insert_post([
            'post_title' => $data['title'],
            'post_excerpt' => $data['description'],
            'post_type' => $post_type,
            'post_status' => 'publish'
        ]);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Set meta data
        update_post_meta($post_id, "_{$data['type']}_status", $data['status']);
        update_post_meta($post_id, "_{$data['type']}_priority", $data['priority']);
        
        if ($data['assignee']) {
            update_post_meta($post_id, "_{$data['type']}_assignee", $data['assignee']);
        }
        
        if ($data['project_id']) {
            update_post_meta($post_id, "_{$data['type']}_project", $data['project_id']);
        }
        
        if ($data['due_date']) {
            update_post_meta($post_id, "_{$data['type']}_due_date", $data['due_date']);
        }

        // Set labels
        if (!empty($data['labels'])) {
            wp_set_post_terms($post_id, $data['labels'], $data['type'] . '_label');
        }

        return $post_id;
    }

    /**
     * Delete Kanban card
     */
    public static function delete_card($card_id) {
        return wp_delete_post($card_id, true);
    }

    /**
     * Create Kanban column
     */
    public static function create_column($board_type, $board_id, $column_data) {
        $columns = get_option("puzzlingcrm_kanban_columns_{$board_type}_{$board_id}", self::get_default_columns());
        
        $new_column = [
            'id' => sanitize_key($column_data['id'] ?? uniqid('col_')),
            'title' => sanitize_text_field($column_data['title']),
            'color' => sanitize_hex_color($column_data['color'] ?? '#6c757d'),
            'order' => count($columns),
            'limit' => isset($column_data['limit']) ? intval($column_data['limit']) : null
        ];

        $columns[] = $new_column;

        update_option("puzzlingcrm_kanban_columns_{$board_type}_{$board_id}", $columns);

        return $new_column;
    }

    /**
     * Update Kanban column
     */
    public static function update_column($board_type, $board_id, $column_id, $column_data) {
        $columns = get_option("puzzlingcrm_kanban_columns_{$board_type}_{$board_id}", self::get_default_columns());
        
        foreach ($columns as &$column) {
            if ($column['id'] === $column_id) {
                if (isset($column_data['title'])) {
                    $column['title'] = sanitize_text_field($column_data['title']);
                }
                if (isset($column_data['color'])) {
                    $column['color'] = sanitize_hex_color($column_data['color']);
                }
                if (isset($column_data['order'])) {
                    $column['order'] = intval($column_data['order']);
                }
                if (isset($column_data['limit'])) {
                    $column['limit'] = $column_data['limit'] ? intval($column_data['limit']) : null;
                }
                break;
            }
        }

        update_option("puzzlingcrm_kanban_columns_{$board_type}_{$board_id}", $columns);

        return true;
    }

    /**
     * Delete Kanban column
     */
    public static function delete_column($board_type, $board_id, $column_id) {
        $columns = get_option("puzzlingcrm_kanban_columns_{$board_type}_{$board_id}", self::get_default_columns());
        
        $columns = array_filter($columns, function($column) use ($column_id) {
            return $column['id'] !== $column_id;
        });

        // Reorder
        $columns = array_values($columns);
        foreach ($columns as $i => &$column) {
            $column['order'] = $i;
        }

        update_option("puzzlingcrm_kanban_columns_{$board_type}_{$board_id}", $columns);

        return true;
    }

    /**
     * AJAX: Get Kanban data
     */
    public function ajax_get_kanban_data() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        $board_type = sanitize_key($_POST['board_type'] ?? 'tasks');
        $board_id = intval($_POST['board_id'] ?? 0);

        $data = self::get_board_data($board_type, $board_id);

        wp_send_json_success($data);
    }

    /**
     * AJAX: Update card
     */
    public function ajax_update_card() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        $card_id = intval($_POST['card_id'] ?? 0);
        $data = $_POST['data'] ?? [];

        $result = self::update_card($card_id, $data);

        if ($result) {
            wp_send_json_success(['message' => 'کارت با موفقیت بروزرسانی شد']);
        } else {
            wp_send_json_error(['message' => 'خطا در بروزرسانی کارت']);
        }
    }

    /**
     * AJAX: Create card
     */
    public function ajax_create_card() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        $data = $_POST['data'] ?? [];

        $card_id = self::create_card($data);

        if (is_wp_error($card_id)) {
            wp_send_json_error(['message' => $card_id->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'کارت با موفقیت ایجاد شد',
            'card_id' => $card_id
        ]);
    }

    /**
     * AJAX: Delete card
     */
    public function ajax_delete_card() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        $card_id = intval($_POST['card_id'] ?? 0);

        $result = self::delete_card($card_id);

        if ($result) {
            wp_send_json_success(['message' => 'کارت با موفقیت حذف شد']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف کارت']);
        }
    }

    /**
     * AJAX: Create column
     */
    public function ajax_create_column() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        $board_type = sanitize_key($_POST['board_type'] ?? 'tasks');
        $board_id = intval($_POST['board_id'] ?? 0);
        $data = $_POST['data'] ?? [];

        $column = self::create_column($board_type, $board_id, $data);

        wp_send_json_success([
            'message' => 'ستون با موفقیت ایجاد شد',
            'column' => $column
        ]);
    }

    /**
     * AJAX: Update column
     */
    public function ajax_update_column() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        $board_type = sanitize_key($_POST['board_type'] ?? 'tasks');
        $board_id = intval($_POST['board_id'] ?? 0);
        $column_id = sanitize_key($_POST['column_id'] ?? '');
        $data = $_POST['data'] ?? [];

        $result = self::update_column($board_type, $board_id, $column_id, $data);

        if ($result) {
            wp_send_json_success(['message' => 'ستون با موفقیت بروزرسانی شد']);
        } else {
            wp_send_json_error(['message' => 'خطا در بروزرسانی ستون']);
        }
    }

    /**
     * AJAX: Delete column
     */
    public function ajax_delete_column() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'nonce');

        $board_type = sanitize_key($_POST['board_type'] ?? 'tasks');
        $board_id = intval($_POST['board_id'] ?? 0);
        $column_id = sanitize_key($_POST['column_id'] ?? '');

        $result = self::delete_column($board_type, $board_id, $column_id);

        if ($result) {
            wp_send_json_success(['message' => 'ستون با موفقیت حذف شد']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف ستون']);
        }
    }
}

