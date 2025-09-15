<?php
/**
 * PuzzlingCRM Custom Post Type & Taxonomy Manager
 *
 * This class handles the registration of all custom post types and taxonomies
 * required for the plugin to function.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class PuzzlingCRM_CPT_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_types' ], 0 );
        add_action( 'init', [ $this, 'register_taxonomies' ], 0 );
        add_action( 'add_meta_boxes', [ $this, 'add_task_meta_boxes' ] );
        add_action( 'save_post_task', [ $this, 'save_task_meta_boxes' ] );
    }

    public function register_post_types() {

        // Epic CPT (NEW)
        register_post_type( 'epic', [
            'labels'        => [
                'name'          => __( 'Epics', 'puzzlingcrm' ),
                'singular_name' => __( 'Epic', 'puzzlingcrm' ),
                'add_new_item'  => __( 'Add New Epic', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'rewrite'       => ['slug' => 'epic'],
            'show_in_rest'  => true,
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
        ]);
        
        // Project CPT
        register_post_type( 'project', [
            'labels'        => [
                'name'          => __( 'Projects', 'puzzlingcrm' ),
                'singular_name' => __( 'Project', 'puzzlingcrm' ),
                'add_new_item'  => __( 'Add New Project', 'puzzlingcrm' ),
                'edit_item'     => __( 'Edit Project', 'puzzlingcrm' ),
                'view_item'     => __( 'View Project', 'puzzlingcrm' ),
                'search_items'  => __( 'Search Projects', 'puzzlingcrm' ),
                'not_found'     => __( 'No projects found', 'puzzlingcrm' ),
            ],
            'public'        => true,
            'show_in_menu'  => false,
            'rewrite'       => ['slug' => 'project'],
            'show_in_rest'  => true,
            'supports'      => ['title', 'editor', 'author', 'custom-fields', 'thumbnail'],
            'has_archive'   => true,
            'menu_icon'     => 'dashicons-portfolio',
        ]);

        // Task CPT - **UPDATED for Sub-tasks & Covers**
        register_post_type( 'task', [
            'labels'        => [
                'name'          => __( 'Tasks', 'puzzlingcrm' ),
                'singular_name' => __( 'Task', 'puzzlingcrm' ),
                'add_new_item'  => __( 'Add New Task', 'puzzlingcrm' ),
                'parent_item_colon' => __( 'Parent Task:', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'hierarchical'  => true, // <-- Enables parent-child relationships for sub-tasks
            'show_in_menu'  => false,
            'supports'      => ['title', 'editor', 'author', 'custom-fields', 'comments', 'page-attributes', 'thumbnail'], // Added thumbnail for Cover Images
        ]);

        // Contract CPT
        register_post_type( 'contract', [
            'labels'        => [
                'name'          => __( 'Contracts', 'puzzlingcrm' ),
                'singular_name' => __( 'Contract', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'supports'      => ['title', 'author', 'custom-fields'],
        ]);
        
        // Log & Notification CPT
        register_post_type( 'puzzling_log', [
            'labels'        => [
                'name'          => __( 'Logs & Notifications', 'puzzlingcrm' ),
                'singular_name' => __( 'Log', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'supports'      => ['title', 'editor', 'author'],
        ]);

        // Ticket CPT
        register_post_type( 'ticket', [
            'labels'        => [
                'name'          => __( 'Tickets', 'puzzlingcrm' ),
                'singular_name' => __( 'Ticket', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'supports'      => ['title', 'editor', 'author', 'comments'],
        ]);

        // Appointment CPT
        register_post_type( 'pzl_appointment', [
            'labels'        => [
                'name'          => __( 'Appointments', 'puzzlingcrm' ),
                'singular_name' => __( 'Appointment', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
        ]);

        // Pro-forma Invoice CPT
        register_post_type( 'pzl_pro_invoice', [
            'labels'        => [
                'name'          => __( 'Pro-forma Invoices', 'puzzlingcrm' ),
                'singular_name' => __( 'Pro-forma Invoice', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => 'puzzling-dashboard',
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
            'menu_icon'     => 'dashicons-media-text',
        ]);
    }

    public function register_taxonomies() {
        // Task Status Taxonomy
        register_taxonomy('task_status', 'task', ['label' => __( 'Task Status', 'puzzlingcrm' ), 'hierarchical' => true, 'show_in_rest' => true]);
        
        // Task Priority Taxonomy
        register_taxonomy('task_priority', 'task', ['label' => __( 'Task Priority', 'puzzlingcrm' ), 'hierarchical' => true, 'show_in_rest' => true]);
        
        // **NEW: Task Labels Taxonomy**
        register_taxonomy('task_label', 'task', [
            'label' => __( 'Labels', 'puzzlingcrm' ),
            'hierarchical' => false, // <-- Makes it behave like tags
            'rewrite' => ['slug' => 'task-label'],
            'show_admin_column' => true,
            'show_in_rest' => true,
            'labels' => [
                'name' => __( 'Labels', 'puzzlingcrm' ),
                'singular_name' => __( 'Label', 'puzzlingcrm' ),
                'search_items' => __( 'Search Labels', 'puzzlingcrm' ),
                'all_items' => __( 'All Labels', 'puzzlingcrm' ),
                'popular_items' => __( 'Popular Labels', 'puzzlingcrm' ),
                'edit_item' => __( 'Edit Label', 'puzzlingcrm' ),
                'update_item' => __( 'Update Label', 'puzzlingcrm' ),
                'add_new_item' => __( 'Add New Label', 'puzzlingcrm' ),
                'new_item_name' => __( 'New Label Name', 'puzzlingcrm' ),
                'menu_name' => __( 'Labels', 'puzzlingcrm' ),
            ]
        ]);

        // **NEW: Task Components Taxonomy**
        register_taxonomy('task_component', 'task', [
            'label' => __( 'Components', 'puzzlingcrm' ),
            'hierarchical' => true, // Makes it behave like categories
            'rewrite' => ['slug' => 'task-component'],
            'show_admin_column' => true,
            'show_in_rest' => true,
            'labels' => [
                'name' => __( 'Components', 'puzzlingcrm' ),
                'singular_name' => __( 'Component', 'puzzlingcrm' ),
                'search_items' => __( 'Search Components', 'puzzlingcrm' ),
                'all_items' => __( 'All Components', 'puzzlingcrm' ),
                'edit_item' => __( 'Edit Component', 'puzzlingcrm' ),
                'update_item' => __( 'Update Component', 'puzzlingcrm' ),
                'add_new_item' => __( 'Add New Component', 'puzzlingcrm' ),
                'new_item_name' => __( 'New Component Name', 'puzzlingcrm' ),
                'menu_name' => __( 'Components', 'puzzlingcrm' ),
            ]
        ]);

        // Ticket Status Taxonomy
        register_taxonomy('ticket_status', 'ticket', ['label' => __( 'Ticket Status', 'puzzlingcrm' ), 'hierarchical' => true]);
    }
    
    /**
     * NEW: Adds meta boxes for advanced task relationships.
     */
    public function add_task_meta_boxes() {
        add_meta_box(
            'puzzling_task_links_meta_box',
            __('Linked Tasks & Epic', 'puzzlingcrm'),
            [$this, 'render_task_links_meta_box'],
            'task',
            'side',
            'default'
        );
    }

    /**
     * NEW: Renders the content of the task links meta box.
     */
    public function render_task_links_meta_box($post) {
        wp_nonce_field('puzzling_save_task_links', 'puzzling_task_links_nonce');

        // Epic Selection
        $epics = get_posts(['post_type' => 'epic', 'numberposts' => -1]);
        $current_epic = get_post_meta($post->ID, '_task_epic_id', true);
        echo '<p><strong>' . __('Epic', 'puzzlingcrm') . '</strong></p>';
        echo '<select name="task_epic_id" style="width:100%;">';
        echo '<option value="">-- ' . __('No Epic', 'puzzlingcrm') . ' --</option>';
        foreach ($epics as $epic) {
            echo '<option value="' . esc_attr($epic->ID) . '" ' . selected($current_epic, $epic->ID, false) . '>' . esc_html($epic->post_title) . '</option>';
        }
        echo '</select><hr>';

        // Task Linking
        $linked_tasks = get_post_meta($post->ID, '_task_links', true) ?: [];
        echo '<p><strong>' . __('Task Links', 'puzzlingcrm') . '</strong></p>';
        // (UI for adding/viewing linked tasks would be added here via JS, for now we save it)
        echo '<p class="description">' . __('Advanced task linking can be managed via API or future UI updates.', 'puzzlingcrm') . '</p>';
    }

    /**
     * NEW: Saves the data from the task links meta box.
     */
    public function save_task_meta_boxes($post_id) {
        if (!isset($_POST['puzzling_task_links_nonce']) || !wp_verify_nonce($_POST['puzzling_task_links_nonce'], 'puzzling_save_task_links')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save Epic
        if (isset($_POST['task_epic_id'])) {
            update_post_meta($post_id, '_task_epic_id', intval($_POST['task_epic_id']));
        }
    }

    public static function create_default_terms() {
        // Task Statuses
        $task_statuses = [__('To Do', 'puzzlingcrm') => 'to-do', __('In Progress', 'puzzlingcrm') => 'in-progress', __('Done', 'puzzlingcrm') => 'done'];
        foreach ($task_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'task_status' ) ) {
                wp_insert_term( $name, 'task_status', ['slug' => $slug] );
            }
        }
        
        // Task Priorities
        $task_priorities = [__('High', 'puzzlingcrm') => 'high', __('Medium', 'puzzlingcrm') => 'medium', __('Low', 'puzzlingcrm') => 'low'];
        foreach ($task_priorities as $name => $slug) {
            if ( ! term_exists( $slug, 'task_priority' ) ) {
                wp_insert_term( $name, 'task_priority', ['slug' => $slug] );
            }
        }
        
        // Ticket Statuses
        $ticket_statuses = [__('Open', 'puzzlingcrm') => 'open', __('In Progress', 'puzzlingcrm') => 'in-progress', __('Answered', 'puzzlingcrm') => 'answered', __('Closed', 'puzzlingcrm') => 'closed'];
        foreach ($ticket_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'ticket_status' ) ) {
                wp_insert_term( $name, 'ticket_status', ['slug' => $slug] );
            }
        }
    }
}