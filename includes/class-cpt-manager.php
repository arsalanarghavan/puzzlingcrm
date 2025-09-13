<?php
/**
 * PuzzlingCRM Custom Post Type & Taxonomy Manager
 *
 * This class handles the registration of all custom post types and taxonomies
 * required for the plugin to function. It ensures that WordPress is aware of
 * entities like Projects, Tasks, Contracts, etc.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class PuzzlingCRM_CPT_Manager {

    /**
     * Constructor. Hooks into WordPress 'init' action.
     */
    public function __construct() {
        // Register post types and taxonomies on WordPress initialization.
        // Priority 0 ensures they are registered before other hooks might need them.
        add_action( 'init', [ $this, 'register_post_types' ], 0 );
        add_action( 'init', [ $this, 'register_taxonomies' ], 0 );
    }

    /**
     * Registers all custom post types used by the plugin.
     */
    public function register_post_types() {

        // Project Custom Post Type
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
            'show_in_menu'  => false, // Managed via our custom admin menu
            'rewrite'       => ['slug' => 'project'],
            'show_in_rest'  => true,
            'supports'      => ['title', 'editor', 'author', 'comments', 'custom-fields', 'thumbnail'],
            'has_archive'   => true,
            'menu_icon'     => 'dashicons-portfolio',
        ]);

        // Task Custom Post Type
        register_post_type( 'task', [
            'labels'        => ['name' => __( 'Tasks', 'puzzlingcrm' ), 'singular_name' => __( 'Task', 'puzzlingcrm' )],
            'public'        => false,
            'show_ui'       => true, // Show in admin for direct management
            'show_in_menu'  => false,
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
        ]);

        // Contract Custom Post Type
        register_post_type( 'contract', [
            'labels'        => ['name' => __( 'Contracts', 'puzzlingcrm' ), 'singular_name' => __( 'Contract', 'puzzlingcrm' )],
            'public'        => false,
            'show_ui'       => true, // Show in admin for direct management
            'show_in_menu'  => false,
            'supports'      => ['title', 'author', 'custom-fields'],
        ]);
        
        // Log & Notification Custom Post Type
        register_post_type( 'puzzling_log', [
            'labels'        => ['name' => __( 'Logs & Notifications', 'puzzlingcrm' ), 'singular_name' => __( 'Log', 'puzzlingcrm' )],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'supports'      => ['title', 'editor', 'author'],
        ]);

        // Ticket Custom Post Type
        register_post_type( 'ticket', [
            'labels'        => ['name' => __( 'Tickets', 'puzzlingcrm' ), 'singular_name' => __( 'Ticket', 'puzzlingcrm' )],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'supports'      => ['title', 'editor', 'author', 'comments'],
        ]);

        // Appointment Custom Post Type
        register_post_type( 'pzl_appointment', [
            'labels'        => ['name' => __( 'Appointments', 'puzzlingcrm' ), 'singular_name' => __( 'Appointment', 'puzzlingcrm' )],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
        ]);
    }

    /**
     * Registers all custom taxonomies used by the plugin.
     */
    public function register_taxonomies() {
        // Task Status Taxonomy
        register_taxonomy('task_status', 'task', ['label' => __( 'Task Status', 'puzzlingcrm' ), 'hierarchical' => true]);
        
        // Task Priority Taxonomy
        register_taxonomy('task_priority', 'task', ['label' => __( 'Task Priority', 'puzzlingcrm' ), 'hierarchical' => true]);
        
        // Ticket Status Taxonomy
        register_taxonomy('ticket_status', 'ticket', ['label' => __( 'Ticket Status', 'puzzlingcrm' ), 'hierarchical' => true]);
    }

    /**
     * Creates default terms for taxonomies upon plugin activation.
     * This ensures the plugin has necessary statuses from the start.
     */
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