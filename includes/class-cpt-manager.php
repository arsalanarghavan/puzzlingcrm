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
                'name'          => 'پروژه‌ها',
                'singular_name' => 'پروژه',
                'add_new_item'  => 'افزودن پروژه جدید',
                'edit_item'     => 'ویرایش پروژه',
                'view_item'     => 'مشاهده پروژه',
                'search_items'  => 'جستجوی پروژه‌ها',
                'not_found'     => 'هیچ پروژه‌ای یافت نشد',
            ],
            'public'        => true,
            'show_in_menu'  => false, // Managed via our custom frontend shortcodes
            'rewrite'       => ['slug' => 'project'],
            'show_in_rest'  => true,
            'supports'      => ['title', 'editor', 'author', 'comments', 'custom-fields', 'thumbnail'],
            'has_archive'   => true,
            'menu_icon'     => 'dashicons-portfolio',
        ]);

        // Task Custom Post Type
        register_post_type( 'task', [
            'labels'        => ['name' => 'وظایف', 'singular_name' => 'وظیفه'],
            'public'        => false,
            'show_ui'       => false, // Managed via our custom frontend shortcodes
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
        ]);

        // Contract Custom Post Type
        register_post_type( 'contract', [
            'labels'        => ['name' => 'قراردادها', 'singular_name' => 'قرارداد'],
            'public'        => false,
            'show_ui'       => false, // Managed via our custom frontend shortcodes
            'supports'      => ['title', 'author', 'custom-fields'],
        ]);
        
        // Log & Notification Custom Post Type
        register_post_type( 'puzzling_log', [
            'labels'        => ['name' => 'لاگ‌ها و اعلان‌ها', 'singular_name' => 'لاگ'],
            'public'        => false,
            'show_ui'       => false, // Managed via our custom frontend shortcodes
            'supports'      => ['title', 'editor', 'author'],
        ]);

        // Ticket Custom Post Type
        register_post_type( 'ticket', [
            'labels'        => ['name' => 'تیکت‌ها', 'singular_name' => 'تیکت'],
            'public'        => false,
            'show_ui'       => false, // Managed via our custom frontend shortcodes
            'supports'      => ['title', 'editor', 'author', 'comments'],
        ]);

        // Subscription Custom Post Type
        register_post_type( 'pzl_subscription', [
            'labels'        => ['name' => 'اشتراک‌ها', 'singular_name' => 'اشتراک'],
            'public'        => false,
            'show_ui'       => false, // Managed via our custom frontend shortcodes
            'supports'      => ['title', 'author', 'custom-fields'],
        ]);

        // Appointment Custom Post Type
        register_post_type( 'pzl_appointment', [
            'labels'        => ['name' => 'قرار ملاقات‌ها', 'singular_name' => 'قرار ملاقات'],
            'public'        => false,
            'show_ui'       => false, // Managed via our custom frontend shortcodes
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
        ]);
    }

    /**
     * Registers all custom taxonomies used by the plugin.
     */
    public function register_taxonomies() {
        // Task Status Taxonomy
        register_taxonomy('task_status', 'task', ['label' => 'وضعیت وظیفه', 'hierarchical' => true]);
        
        // Task Priority Taxonomy
        register_taxonomy('task_priority', 'task', ['label' => 'اهمیت وظیفه', 'hierarchical' => true]);
        
        // Ticket Status Taxonomy
        register_taxonomy('ticket_status', 'ticket', ['label' => 'وضعیت تیکت', 'hierarchical' => true]);
        
        // Subscription Status Taxonomy
        register_taxonomy('subscription_status', 'pzl_subscription', ['label' => 'وضعیت اشتراک', 'hierarchical' => true]);
        
        // Subscription Plan Taxonomy (used to define different plans)
        register_taxonomy('subscription_plan', 'pzl_subscription', [
            'label'         => 'پلن‌های اشتراک',
            'hierarchical'  => false, // Non-hierarchical, like tags
            'public'        => false,
            'show_ui'       => true, // Make it visible in admin for easy management if needed
        ]);
    }

    /**
     * Creates default terms for taxonomies upon plugin activation.
     * This ensures the plugin has necessary statuses from the start.
     */
    public static function create_default_terms() {
        // Task Statuses
        $task_statuses = ['انجام نشده' => 'to-do', 'در حال انجام' => 'in-progress', 'انجام شده' => 'done'];
        foreach ($task_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'task_status' ) ) {
                wp_insert_term( $name, 'task_status', ['slug' => $slug] );
            }
        }
        
        // Task Priorities
        $task_priorities = ['زیاد' => 'high', 'متوسط' => 'medium', 'کم' => 'low'];
        foreach ($task_priorities as $name => $slug) {
            if ( ! term_exists( $slug, 'task_priority' ) ) {
                wp_insert_term( $name, 'task_priority', ['slug' => $slug] );
            }
        }
        
        // Ticket Statuses
        $ticket_statuses = ['باز' => 'open', 'در حال بررسی' => 'in-progress', 'پاسخ داده شد' => 'answered', 'بسته شده' => 'closed'];
        foreach ($ticket_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'ticket_status' ) ) {
                wp_insert_term( $name, 'ticket_status', ['slug' => $slug] );
            }
        }

        // Subscription Statuses
        $subscription_statuses = ['فعال' => 'active', 'منقضی شده' => 'expired', 'لغو شده' => 'cancelled', 'معوق' => 'overdue'];
        foreach ($subscription_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'subscription_status' ) ) {
                wp_insert_term( $name, 'subscription_status', ['slug' => $slug] );
            }
        }
    }
}