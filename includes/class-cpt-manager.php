<?php
class PuzzlingCRM_CPT_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_types' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
    }

    public function register_post_types() {
        // Project CPT
        $project_labels = [ 'name' => 'پروژه‌ها', 'singular_name' => 'پروژه' ];
        $project_args = [
            'labels'        => $project_labels,
            'public'        => true,
            'rewrite'       => ['slug' => 'project'],
            'show_in_rest'  => true,
            'supports'      => ['title', 'editor', 'author', 'comments', 'custom-fields'],
            'menu_icon'     => 'dashicons-portfolio',
        ];
        register_post_type( 'project', $project_args );

        // Task CPT
        $task_labels = [ 'name' => 'تسک‌ها', 'singular_name' => 'تسک' ];
        $task_args = [
            'labels'        => $task_labels,
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => 'edit.php?post_type=project',
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
        ];
        register_post_type( 'task', $task_args );

        // Contract CPT
        $contract_labels = [ 'name' => 'قراردادها', 'singular_name' => 'قرارداد' ];
        $contract_args = [
            'labels'        => $contract_labels,
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => 'edit.php?post_type=project',
            'supports'      => ['title', 'author', 'custom-fields'],
        ];
        register_post_type( 'contract', $contract_args );
    }

    public function register_taxonomies() {
        // Task Status Taxonomy
        register_taxonomy('task_status', 'task', [
            'label' => 'وضعیت تسک',
            'rewrite' => ['slug' => 'task-status'],
            'hierarchical' => true,
        ]);

        // Task Priority Taxonomy
        register_taxonomy('task_priority', 'task', [
            'label' => 'اهمیت تسک',
            'rewrite' => ['slug' => 'task-priority'],
            'hierarchical' => true,
        ]);
    }

    /**
     * Creates the default terms for our custom taxonomies.
     * This method should be called once on plugin activation.
     */
    public static function create_default_terms() {
        $task_statuses = [
            'انجام نشده' => 'to-do',
            'در حال انجام' => 'in-progress',
            'انجام شده' => 'done',
        ];

        foreach ($task_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'task_status' ) ) {
                wp_insert_term( $name, 'task_status', ['slug' => $slug] );
            }
        }
        
        $task_priorities = [
            'زیاد' => 'high',
            'متوسط' => 'medium',
            'کم' => 'low',
        ];

        foreach ($task_priorities as $name => $slug) {
            if ( ! term_exists( $slug, 'task_priority' ) ) {
                wp_insert_term( $name, 'task_priority', ['slug' => $slug] );
            }
        }
    }
}