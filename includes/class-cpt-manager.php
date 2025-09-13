<?php
class PuzzlingCRM_CPT_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_types' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
    }

    public function register_post_types() {
        $menu_icon = 'dashicons-businessperson'; // A suitable icon for the CRM

        // Main CPT for Projects
        register_post_type( 'project', [
            'labels'        => ['name' => 'پروژه‌ها', 'singular_name' => 'پروژه'],
            'public'        => true,
            'show_in_menu'  => false, // Will be managed under our own menu
            'rewrite'       => ['slug' => 'project'],
            'show_in_rest'  => true,
            'supports'      => ['title', 'editor', 'author', 'comments', 'custom-fields'],
        ]);

        // Task CPT
        register_post_type( 'task', [
            'labels'        => ['name' => 'وظایف', 'singular_name' => 'وظیفه'],
            'public'        => false,
            'show_ui'       => false, // Will be managed under our own menu
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
        ]);

        // Contract CPT
        register_post_type( 'contract', [
            'labels'        => ['name' => 'قراردادها', 'singular_name' => 'قرارداد'],
            'public'        => false,
            'show_ui'       => false, // Will be managed under our own menu
            'supports'      => ['title', 'author', 'custom-fields'],
        ]);
        
        // Log & Notification CPT
        register_post_type( 'puzzling_log', [
            'labels'        => ['name' => 'لاگ‌ها و اعلان‌ها', 'singular_name' => 'لاگ'],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => 'puzzling-crm',
            'supports'      => ['title', 'editor'],
        ]);

        // Ticket CPT
        register_post_type( 'ticket', [
            'labels'        => ['name' => 'تیکت‌ها', 'singular_name' => 'تیکت'],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => 'puzzling-crm',
            'menu_icon'     => 'dashicons-sos',
            'supports'      => ['title', 'editor', 'author', 'comments'],
        ]);

        // **NEW: Subscription CPT**
        register_post_type( 'pzl_subscription', [
            'labels'        => ['name' => 'اشتراک‌ها', 'singular_name' => 'اشتراک'],
            'public'        => false,
            'show_ui'       => false, // Managed under our menu
            'supports'      => ['title', 'author', 'custom-fields'],
        ]);

        // **NEW: Appointment CPT**
        register_post_type( 'pzl_appointment', [
            'labels'        => ['name' => 'قرار ملاقات‌ها', 'singular_name' => 'قرار ملاقات'],
            'public'        => false,
            'show_ui'       => false, // Managed under our menu
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
        ]);
    }

    public function register_taxonomies() {
        // Task Status Taxonomy
        register_taxonomy('task_status', 'task', [ 'label' => 'وضعیت وظیفه', 'hierarchical' => true ]);
        register_taxonomy('task_priority', 'task', [ 'label' => 'اهمیت وظیفه', 'hierarchical' => true ]);
        register_taxonomy('ticket_status', 'ticket', [ 'label' => 'وضعیت تیکت', 'hierarchical' => true, 'show_admin_column' => true ]);
        
        // **NEW: Subscription Status Taxonomy**
        register_taxonomy('subscription_status', 'pzl_subscription', [ 'label' => 'وضعیت اشتراک', 'hierarchical' => true ]);
    }

    public static function create_default_terms() {
        $task_statuses = ['انجام نشده' => 'to-do', 'در حال انجام' => 'in-progress', 'انجام شده' => 'done'];
        foreach ($task_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'task_status' ) ) wp_insert_term( $name, 'task_status', ['slug' => $slug] );
        }
        
        $task_priorities = ['زیاد' => 'high', 'متوسط' => 'medium', 'کم' => 'low'];
        foreach ($task_priorities as $name => $slug) {
            if ( ! term_exists( $slug, 'task_priority' ) ) wp_insert_term( $name, 'task_priority', ['slug' => $slug] );
        }
        
        $ticket_statuses = ['باز' => 'open', 'در حال بررسی' => 'in-progress', 'پاسخ داده شد' => 'answered', 'بسته شده' => 'closed'];
        foreach ($ticket_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'ticket_status' ) ) wp_insert_term( $name, 'ticket_status', ['slug' => $slug] );
        }

        // **NEW: Subscription Statuses**
        $subscription_statuses = [
            'فعال' => 'active', 'آینده' => 'pending-activation', 'معوق' => 'overdue', 
            'پرداخت نشده' => 'unpaid', 'ناقص' => 'incomplete', 'لغو شده' => 'cancelled', 'منقضی شده' => 'expired'
        ];
        foreach ($subscription_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'subscription_status' ) ) wp_insert_term( $name, 'subscription_status', ['slug' => $slug] );
        }
    }
}