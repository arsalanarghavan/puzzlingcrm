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
    }

    public function register_post_types() {

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

        // Task CPT - **UPDATED for Sub-tasks**
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
            'supports'      => ['title', 'editor', 'author', 'custom-fields', 'comments', 'page-attributes'], // Added page-attributes for parent selection
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

        // Ticket Status Taxonomy
        register_taxonomy('ticket_status', 'ticket', ['label' => __( 'Ticket Status', 'puzzlingcrm' ), 'hierarchical' => true]);
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