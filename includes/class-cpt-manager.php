<?php
/**
 * PuzzlingCRM Custom Post Type & Taxonomy Manager
 *
 * This class handles the registration of all custom post types and taxonomies
 * required for the plugin to function, including new Agile and Consultation features.
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
        // Add meta box for linking templates to products
        add_action( 'add_meta_boxes', [ $this, 'add_project_template_meta_box' ] );
        add_action( 'save_post_product', [ $this, 'save_project_template_meta_box' ] );
		// NEW: Add meta box for task templates
        add_action( 'add_meta_boxes', [ $this, 'add_task_template_meta_box' ] );
        add_action( 'save_post_pzl_task_template', [ $this, 'save_task_template_meta_box' ] );
    }

    public function register_post_types() {
        // Consultation CPT - NEW
        register_post_type( 'pzl_consultation', [
            'labels'        => [
                'name'          => __( 'مشاوره‌ها', 'puzzlingcrm' ),
                'singular_name' => __( 'مشاوره', 'puzzlingcrm' ),
                'add_new_item'  => __( 'افزودن مشاوره جدید', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true, // Show in admin for debugging, but managed via frontend
            'show_in_menu'  => 'puzzling-crm-info',
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
            'hierarchical'  => false,
        ]);

        // Project Template CPT
        register_post_type( 'pzl_project_template', [
            'labels'        => [
                'name'          => __( 'قالب‌های پروژه', 'puzzlingcrm' ),
                'singular_name' => __( 'قالب پروژه', 'puzzlingcrm' ),
                'add_new_item'  => __( 'افزودن قالب پروژه جدید', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => 'puzzling-crm-info', // Show under main CRM menu
            'supports'      => ['title'],
            'hierarchical'  => false,
        ]);

        // **MODIFIED: Form CPT - Hidden from backend UI**
        register_post_type( 'pzl_form', [
            'labels'        => [
                'name'          => __( 'Forms', 'puzzlingcrm' ),
                'singular_name' => __( 'Form', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => false, // Completely hide from admin UI
            'show_in_menu'  => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'supports'      => ['title', 'editor', 'author'],
        ]);

        // Epic CPT
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

        // Sprint CPT
        register_post_type( 'pzl_sprint', [
            'labels'        => [
                'name'          => __( 'Sprints', 'puzzlingcrm' ),
                'singular_name' => __( 'Sprint', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'supports'      => ['title', 'author', 'custom-fields'],
        ]);

        // Task Template CPT
        register_post_type( 'pzl_task_template', [
            'labels'        => [
                'name'          => __( 'قالب‌های تسک', 'puzzlingcrm' ),
                'singular_name' => __( 'قالب تسک', 'puzzlingcrm' ),
				'add_new_item'  => __( 'افزودن قالب تسک جدید', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => 'puzzling-crm-info',
            'supports'      => ['title', 'editor', 'custom-fields'],
        ]);
        
        // Project CPT
        register_post_type( 'project', [
            'labels'        => [
                'name'          => __( 'Projects', 'puzzlingcrm' ),
                'singular_name' => __( 'Project', 'puzzlingcrm' ),
            ],
            'public'        => true,
            'show_in_menu'  => false,
            'rewrite'       => ['slug' => 'project'],
            'show_in_rest'  => true,
            'supports'      => ['title', 'editor', 'author', 'custom-fields', 'thumbnail'],
            'has_archive'   => true,
        ]);

        // Task CPT
        register_post_type( 'task', [
            'labels'        => [
                'name'          => __( 'Tasks', 'puzzlingcrm' ),
                'singular_name' => __( 'Task', 'puzzlingcrm' ),
            ],
            'public'        => false,
            'show_ui'       => true,
            'hierarchical'  => true,
            'show_in_menu'  => false,
            'supports'      => ['title', 'editor', 'author', 'custom-fields', 'comments', 'page-attributes', 'thumbnail'],
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
            'supports'      => ['title', 'editor', 'author', 'comments', 'custom-fields'],
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
            'show_in_menu'  => false,
            'supports'      => ['title', 'editor', 'author', 'custom-fields'],
        ]);
    }

    public function register_taxonomies() {
        // Consultation Status Taxonomy
        register_taxonomy('consultation_status', 'pzl_consultation', [
            'label' => __( 'نتیجه مشاوره', 'puzzlingcrm' ),
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'labels' => [
                'name' => __( 'نتایج مشاوره', 'puzzlingcrm' ),
                'singular_name' => __( 'نتیجه مشاوره', 'puzzlingcrm' ),
            ]
        ]);
        
        // Appointment Status Taxonomy - NEW
        register_taxonomy('appointment_status', 'pzl_appointment', [
            'label' => __( 'وضعیت قرار', 'puzzlingcrm' ),
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'labels' => [
                'name' => __( 'وضعیت‌های قرار', 'puzzlingcrm' ),
                'singular_name' => __( 'وضعیت قرار', 'puzzlingcrm' ),
            ]
        ]);

        // Project Status Taxonomy
        register_taxonomy('project_status', 'project', [
            'label' => __( 'Project Status', 'puzzlingcrm' ),
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'labels' => [
                'name' => __( 'وضعیت‌های پروژه', 'puzzlingcrm' ),
                'singular_name' => __( 'وضعیت پروژه', 'puzzlingcrm' ),
                'menu_name' => __( 'وضعیت پروژه', 'puzzlingcrm' ),
            ]
        ]);

		// Task Category Taxonomy
		register_taxonomy('task_category', ['task', 'pzl_task_template'], [
            'label' => __( 'Task Category', 'puzzlingcrm' ),
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'labels' => [
                'name' => __( 'دسته‌بندی تسک', 'puzzlingcrm' ),
                'singular_name' => __( 'دسته‌بندی تسک', 'puzzlingcrm' ),
                'menu_name' => __( 'دسته‌بندی تسک', 'puzzlingcrm' ),
            ]
        ]);

        // Task Status Taxonomy
        register_taxonomy('task_status', 'task', ['label' => __( 'Task Status', 'puzzlingcrm' ), 'hierarchical' => true, 'show_in_rest' => true]);
        
        // Task Priority Taxonomy
        register_taxonomy('task_priority', 'task', ['label' => __( 'Task Priority', 'puzzlingcrm' ), 'hierarchical' => true, 'show_in_rest' => true]);
        
        // Task Labels Taxonomy
        register_taxonomy('task_label', 'task', [
            'label' => __( 'Labels', 'puzzlingcrm' ),
            'hierarchical' => false,
            'rewrite' => ['slug' => 'task-label'],
            'show_admin_column' => true,
            'show_in_rest' => true,
            'labels' => [
                'name' => __( 'Labels', 'puzzlingcrm' ), 'singular_name' => __( 'Label', 'puzzlingcrm' ),
                'search_items' => __( 'Search Labels', 'puzzlingcrm' ), 'all_items' => __( 'All Labels', 'puzzlingcrm' ),
                'popular_items' => __( 'Popular Labels', 'puzzlingcrm' ), 'edit_item' => __( 'Edit Label', 'puzzlingcrm' ),
                'update_item' => __( 'Update Label', 'puzzlingcrm' ), 'add_new_item' => __( 'Add New Label', 'puzzlingcrm' ),
                'new_item_name' => __( 'New Label Name', 'puzzlingcrm' ), 'menu_name' => __( 'Labels', 'puzzlingcrm' ),
            ]
        ]);
        
        // **MODIFIED**: Now applies to users AND tickets for departments
        register_taxonomy('organizational_position', ['user', 'ticket'], [
            'label' => __( 'Organizational Positions', 'puzzlingcrm' ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false, 
            'hierarchical' => true, 
            'rewrite' => false,
            'labels' => [
                'name' => __( 'جایگاه‌های سازمانی', 'puzzlingcrm' ), 'singular_name' => __( 'جایگاه سازمانی', 'puzzlingcrm' ),
                'search_items' => __( 'جستجوی جایگاه', 'puzzlingcrm' ), 'all_items' => __( 'تمام جایگاه‌ها', 'puzzlingcrm' ),
                'edit_item' => __( 'ویرایش جایگاه', 'puzzlingcrm' ), 'update_item' => __( 'بروزرسانی جایگاه', 'puzzlingcrm' ),
                'add_new_item' => __( 'افزودن جایگاه جدید', 'puzzlingcrm' ), 'new_item_name' => __( 'نام جایگاه جدید', 'puzzlingcrm' ),
                'menu_name' => __( 'جایگاه‌های سازمانی', 'puzzlingcrm' ),
                 'parent_item' => __( 'دپارتمان والد', 'puzzlingcrm' ),
                'parent_item_colon' => __( 'دپارتمان والد:', 'puzzlingcrm' ),
            ]
        ]);

        // Ticket Status Taxonomy
        register_taxonomy('ticket_status', 'ticket', ['label' => __( 'Ticket Status', 'puzzlingcrm' ), 'hierarchical' => true]);
    }
    
    public static function create_default_terms() {
        // Consultation Statuses
        $consultation_statuses = ['در حال پیگیری' => 'in-progress', 'تبدیل به پروژه' => 'converted', 'بسته شده' => 'closed'];
        foreach ($consultation_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'consultation_status' ) ) wp_insert_term( $name, 'consultation_status', ['slug' => $slug] );
        }

        // Appointment Statuses - NEW
        $appointment_statuses = ['در انتظار تایید' => 'pending', 'تایید شده' => 'confirmed', 'لغو شده' => 'cancelled'];
        foreach ($appointment_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'appointment_status' ) ) wp_insert_term( $name, 'appointment_status', ['slug' => $slug] );
        }

        // Project Statuses
        $project_statuses = ['فعال' => 'active', 'تکمیل شده' => 'completed', 'در انتظار' => 'on-hold', 'لغو شده' => 'cancelled'];
        foreach ($project_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'project_status' ) ) wp_insert_term( $name, 'project_status', ['slug' => $slug] );
        }

		// Task Categories
        $task_categories = ['روزانه' => 'daily', 'هفتگی' => 'weekly', 'پروژه‌ای' => 'project-based'];
        foreach ($task_categories as $name => $slug) {
            if ( ! term_exists( $slug, 'task_category' ) ) wp_insert_term( $name, 'task_category', ['slug' => $slug] );
        }

        // Task Statuses
        $task_statuses = ['انجام نشده' => 'to-do', 'در حال انجام' => 'in-progress', 'انجام شده' => 'done'];
        foreach ($task_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'task_status' ) ) wp_insert_term( $name, 'task_status', ['slug' => $slug] );
        }
        
        // Task Priorities
        $task_priorities = [__('High', 'puzzlingcrm') => 'high', __('Medium', 'puzzlingcrm') => 'medium', __('Low', 'puzzlingcrm') => 'low'];
        foreach ($task_priorities as $name => $slug) {
            if ( ! term_exists( $slug, 'task_priority' ) ) wp_insert_term( $name, 'task_priority', ['slug' => $slug] );
        }
        
        // Default Organizational Positions (Departments)
        $departments = ['پشتیبانی فنی' => 'technical-support', 'فروش و مالی' => 'sales-finance', 'مدیریت' => 'management'];
        foreach ($departments as $name => $slug) {
             if ( ! term_exists( $slug, 'organizational_position' ) ) {
                wp_insert_term( $name, 'organizational_position', ['slug' => $slug] );
            }
        }

        // Ticket Statuses
        $ticket_statuses = [__('Open', 'puzzlingcrm') => 'open', __('In Progress', 'puzzlingcrm') => 'in-progress', __('Answered', 'puzzlingcrm') => 'answered', __('Closed', 'puzzlingcrm') => 'closed'];
        foreach ($ticket_statuses as $name => $slug) {
            if ( ! term_exists( $slug, 'ticket_status' ) ) wp_insert_term( $name, 'ticket_status', ['slug' => $slug] );
        }
    }
    
    public function add_task_template_meta_box() {
        add_meta_box(
            'puzzling_task_template_options',
            __( 'تنظیمات قالب تسک', 'puzzlingcrm' ),
            [ $this, 'render_task_template_meta_box' ],
            'pzl_task_template', 'normal', 'high'
        );
    }

	public function render_task_template_meta_box( $post ) {
        wp_nonce_field('puzzling_save_task_template_options', 'puzzling_task_template_nonce');

        $assigned_role_id = get_post_meta($post->ID, '_assigned_role', true);
        $task_category_id = get_post_meta($post->ID, '_task_category', true);
        
        $positions = get_terms(['taxonomy' => 'organizational_position', 'hide_empty' => false]);
        $categories = get_terms(['taxonomy' => 'task_category', 'hide_empty' => false]);

        echo '<p><strong>' . __('دسته‌بندی (برای اتوماسیون):', 'puzzlingcrm') . '</strong></p>';
        echo '<select name="_task_category" style="width:100%;">';
        echo '<option value="">' . __('انتخاب کنید', 'puzzlingcrm') . '</option>';
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->term_id) . '" ' . selected($task_category_id, $category->term_id, false) . '>' . esc_html($category->name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('برای ساخت تسک‌های خودکار، "روزانه" را انتخاب کنید.', 'puzzlingcrm') . '</p>';

        echo '<hr style="margin: 20px 0;">';

        echo '<p><strong>' . __('نقش مسئول (برای اتوماسیون):', 'puzzlingcrm') . '</strong></p>';
        echo '<select name="_assigned_role" style="width:100%;">';
        echo '<option value="">' . __('انتخاب کنید', 'puzzlingcrm') . '</option>';
        foreach ($positions as $position) {
            echo '<option value="' . esc_attr($position->term_id) . '" ' . selected($assigned_role_id, $position->term_id, false) . '>' . esc_html($position->name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('این تسک روزانه برای تمام کارمندانی که این جایگاه شغلی را دارند ساخته خواهد شد.', 'puzzlingcrm') . '</p>';
    }

    public function save_task_template_meta_box( $post_id ) {
        if (!isset($_POST['puzzling_task_template_nonce']) || !wp_verify_nonce($_POST['puzzling_task_template_nonce'], 'puzzling_save_task_template_options')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['_task_category'])) {
            update_post_meta($post_id, '_task_category', intval($_POST['_task_category']));
        }
        if (isset($_POST['_assigned_role'])) {
            update_post_meta($post_id, '_assigned_role', intval($_POST['_assigned_role']));
        }
    }

    public function add_project_template_meta_box() {
        add_meta_box(
            'puzzling_project_template_link',
            __( 'اتوماسیون PuzzlingCRM', 'puzzlingcrm' ),
            [ $this, 'render_project_template_meta_box' ],
            'product', 'side', 'default'
        );
    }

    public function render_project_template_meta_box( $post ) {
        wp_nonce_field('puzzling_save_project_template_link', 'puzzling_project_template_nonce');
        $linked_template_id = get_post_meta($post->ID, '_puzzling_project_template_id', true);
        
        $templates = get_posts(['post_type' => 'pzl_project_template', 'posts_per_page' => -1]);

        echo '<p>' . __('این محصول (خدمت) پس از فروش، کدام قالب پروژه را ایجاد کند؟', 'puzzlingcrm') . '</p>';
        echo '<select name="_puzzling_project_template_id" style="width:100%;">';
        echo '<option value="">' . __('هیچکدام (پروژه‌ای ساخته نشود)', 'puzzlingcrm') . '</option>';
        if ($templates) {
            foreach ($templates as $template) {
                echo '<option value="' . esc_attr($template->ID) . '" ' . selected($linked_template_id, $template->ID, false) . '>' . esc_html($template->post_title) . '</option>';
            }
        }
        echo '</select>';
    }

    public function save_project_template_meta_box( $post_id ) {
        if (!isset($_POST['puzzling_project_template_nonce']) || !wp_verify_nonce($_POST['puzzling_project_template_nonce'], 'puzzling_save_project_template_link')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_product', $post_id)) return;

        if (isset($_POST['_puzzling_project_template_id'])) {
            update_post_meta($post_id, '_puzzling_project_template_id', intval($_POST['_puzzling_project_template_id']));
        }
    }
    
    public function add_task_meta_boxes() {
        add_meta_box('puzzling_task_details_meta_box', __('Task Details', 'puzzlingcrm'), [$this, 'render_task_details_meta_box'], 'task', 'side', 'default');
    }

    public function render_task_details_meta_box($post) {
        wp_nonce_field('puzzling_save_task_details', 'puzzling_task_details_nonce');

        $epics = get_posts(['post_type' => 'epic', 'numberposts' => -1]);
        $current_epic = get_post_meta($post->ID, '_task_epic_id', true);
        echo '<p><strong>' . __('Epic', 'puzzlingcrm') . '</strong></p>';
        echo '<select name="task_epic_id" style="width:100%;">';
        echo '<option value="">-- ' . __('No Epic', 'puzzlingcrm') . ' --</option>';
        foreach ($epics as $epic) {
            echo '<option value="' . esc_attr($epic->ID) . '" ' . selected($current_epic, $epic->ID, false) . '>' . esc_html($epic->post_title) . '</option>';
        }
        echo '</select><hr>';

        $story_points = get_post_meta($post->ID, '_story_points', true);
        echo '<p><strong>' . __('Story Points', 'puzzlingcrm') . '</strong></p>';
        echo '<input type="number" name="story_points" value="' . esc_attr($story_points) . '" style="width:100%;" />';
    }

    public function save_task_meta_boxes($post_id) {
        if (!isset($_POST['puzzling_task_details_nonce']) || !wp_verify_nonce($_POST['puzzling_task_details_nonce'], 'puzzling_save_task_details')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['task_epic_id'])) update_post_meta($post_id, '_task_epic_id', intval($_POST['task_epic_id']));
        if (isset($_POST['story_points'])) update_post_meta($post_id, '_story_points', sanitize_text_field($_POST['story_points']));
    }
}