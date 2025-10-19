<?php
/**
 * Task Template Manager
 * Manages task templates and recurring tasks
 * 
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

class PuzzlingCRM_Task_Template_Manager {

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('init', [$this, 'create_default_templates']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // AJAX handlers
        add_action('wp_ajax_puzzling_get_templates', [$this, 'ajax_get_templates']);
        add_action('wp_ajax_puzzling_create_from_template', [$this, 'ajax_create_from_template']);
        add_action('wp_ajax_puzzling_save_template', [$this, 'ajax_save_template']);
        
        // Recurring tasks cron
        add_action('puzzling_check_recurring_tasks', [$this, 'process_recurring_tasks']);
        
        if (!wp_next_scheduled('puzzling_check_recurring_tasks')) {
            wp_schedule_event(time(), 'daily', 'puzzling_check_recurring_tasks');
        }
    }

    /**
     * Register Task Template Post Type
     */
    public function register_post_type() {
        register_post_type('task_template', [
            'labels' => [
                'name' => 'قالب‌های وظیفه',
                'singular_name' => 'قالب وظیفه',
                'add_new' => 'افزودن قالب جدید',
                'add_new_item' => 'افزودن قالب وظیفه جدید',
                'edit_item' => 'ویرایش قالب',
                'view_item' => 'مشاهده قالب',
                'all_items' => 'همه قالب‌ها',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => ['title', 'editor'],
            'has_archive' => false,
        ]);
    }

    /**
     * Register Template Taxonomies
     */
    public function register_taxonomies() {
        register_taxonomy('template_category', 'task_template', [
            'labels' => [
                'name' => 'دسته‌بندی قالب',
                'singular_name' => 'دسته‌بندی',
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
        ]);
    }

    /**
     * Create Default Templates
     */
    public function create_default_templates() {
        if (get_option('puzzling_templates_created')) {
            return;
        }

        // E-commerce Templates
        $ecommerce_templates = [
            [
                'title' => 'راه‌اندازی فروشگاه',
                'tasks' => [
                    ['title' => 'نصب و پیکربندی افزونه فروشگاه', 'duration' => 2],
                    ['title' => 'ایجاد صفحات اصلی (فروشگاه، سبد خرید، تسویه حساب)', 'duration' => 1],
                    ['title' => 'پیکربندی درگاه پرداخت', 'duration' => 1],
                    ['title' => 'تنظیم روش‌های ارسال', 'duration' => 1],
                    ['title' => 'افزودن محصولات نمونه', 'duration' => 2],
                    ['title' => 'پیکربندی مالیات و ارز', 'duration' => 1],
                    ['title' => 'تست فرآیند خرید', 'duration' => 1],
                ]
            ],
            [
                'title' => 'مدیریت محصولات',
                'tasks' => [
                    ['title' => 'دسته‌بندی محصولات', 'duration' => 1],
                    ['title' => 'افزودن تصاویر محصولات', 'duration' => 2],
                    ['title' => 'تنظیم موجودی', 'duration' => 1],
                    ['title' => 'بررسی قیمت‌ها', 'duration' => 1],
                ]
            ],
        ];

        // Social Media Templates (Daily Recurring)
        $social_templates = [
            [
                'title' => 'مدیریت روزانه سوشال مدیا',
                'recurring' => 'daily',
                'tasks' => [
                    ['title' => 'پست صبحگاهی اینستاگرام', 'duration' => 0.5],
                    ['title' => 'پاسخ به کامنت‌ها', 'duration' => 0.5],
                    ['title' => 'پست استوری', 'duration' => 0.25],
                    ['title' => 'بررسی آمار روز قبل', 'duration' => 0.25],
                    ['title' => 'پست عصرگاهی', 'duration' => 0.5],
                ]
            ],
            [
                'title' => 'برنامه هفتگی محتوا',
                'recurring' => 'weekly',
                'tasks' => [
                    ['title' => 'برنامه‌ریزی محتوای هفته', 'duration' => 2],
                    ['title' => 'تهیه محتوای گرافیکی', 'duration' => 4],
                    ['title' => 'نوشتن کپشن‌ها', 'duration' => 2],
                    ['title' => 'زمان‌بندی پست‌ها', 'duration' => 1],
                ]
            ],
        ];

        // Website Development Templates
        $website_templates = [
            [
                'title' => 'راه‌اندازی وب‌سایت جدید',
                'tasks' => [
                    ['title' => 'خرید و نصب هاست و دامنه', 'duration' => 1],
                    ['title' => 'نصب وردپرس', 'duration' => 0.5],
                    ['title' => 'نصب و فعال‌سازی قالب', 'duration' => 1],
                    ['title' => 'نصب افزونه‌های ضروری', 'duration' => 1],
                    ['title' => 'طراحی صفحه اصلی', 'duration' => 3],
                    ['title' => 'ایجاد صفحات داخلی', 'duration' => 2],
                    ['title' => 'پیکربندی SEO', 'duration' => 1],
                    ['title' => 'تنظیم فرم تماس', 'duration' => 0.5],
                    ['title' => 'تست و رفع باگ', 'duration' => 2],
                    ['title' => 'انتشار سایت', 'duration' => 0.5],
                ]
            ],
            [
                'title' => 'نگهداری ماهیانه وب‌سایت',
                'recurring' => 'monthly',
                'tasks' => [
                    ['title' => 'بک‌آپ کامل سایت', 'duration' => 0.5],
                    ['title' => 'به‌روزرسانی وردپرس و افزونه‌ها', 'duration' => 1],
                    ['title' => 'بررسی امنیتی', 'duration' => 1],
                    ['title' => 'بهینه‌سازی دیتابیس', 'duration' => 0.5],
                    ['title' => 'بررسی سرعت سایت', 'duration' => 0.5],
                    ['title' => 'بررسی لینک‌های شکسته', 'duration' => 0.5],
                ]
            ],
        ];

        $category_map = [
            'ecommerce' => 'فروشگاه اینترنتی',
            'social' => 'سوشال مدیا',
            'website' => 'وب‌سایت',
        ];

        // Create categories
        foreach ($category_map as $slug => $name) {
            if (!term_exists($slug, 'template_category')) {
                wp_insert_term($name, 'template_category', ['slug' => $slug]);
            }
        }

        // Create templates
        $all_templates = [
            'ecommerce' => $ecommerce_templates,
            'social' => $social_templates,
            'website' => $website_templates,
        ];

        foreach ($all_templates as $cat_slug => $templates) {
            foreach ($templates as $template_data) {
                $template_id = wp_insert_post([
                    'post_type' => 'task_template',
                    'post_title' => $template_data['title'],
                    'post_status' => 'publish',
                ]);

                if ($template_id) {
                    // Set category
                    wp_set_object_terms($template_id, $cat_slug, 'template_category');
                    
                    // Save tasks
                    update_post_meta($template_id, '_template_tasks', $template_data['tasks']);
                    
                    // Save recurring settings
                    if (isset($template_data['recurring'])) {
                        update_post_meta($template_id, '_recurring_type', $template_data['recurring']);
                        update_post_meta($template_id, '_is_recurring', 1);
                    }
                }
            }
        }

        update_option('puzzling_templates_created', true);
    }

    /**
     * Add Admin Menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'puzzling-crm',
            'قالب‌های وظیفه',
            'قالب‌های وظیفه',
            'manage_options',
            'puzzling-task-templates',
            [$this, 'render_templates_page']
        );
    }

    /**
     * Render Templates Page
     */
    public function render_templates_page() {
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/admin/page-task-templates.php';
    }

    /**
     * AJAX: Get Templates
     */
    public function ajax_get_templates() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $category = isset($_POST['category']) ? sanitize_key($_POST['category']) : '';
        
        $args = [
            'post_type' => 'task_template',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ];
        
        if ($category) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'template_category',
                    'field' => 'slug',
                    'terms' => $category,
                ]
            ];
        }
        
        $templates = get_posts($args);
        $result = [];
        
        foreach ($templates as $template) {
            $tasks = get_post_meta($template->ID, '_template_tasks', true);
            $is_recurring = get_post_meta($template->ID, '_is_recurring', true);
            $recurring_type = get_post_meta($template->ID, '_recurring_type', true);
            
            $categories = wp_get_post_terms($template->ID, 'template_category');
            $category_name = $categories ? $categories[0]->name : '';
            
            $result[] = [
                'id' => $template->ID,
                'title' => $template->post_title,
                'tasks' => $tasks ? $tasks : [],
                'tasks_count' => is_array($tasks) ? count($tasks) : 0,
                'category' => $category_name,
                'is_recurring' => $is_recurring ? true : false,
                'recurring_type' => $recurring_type,
            ];
        }
        
        wp_send_json_success(['templates' => $result]);
    }

    /**
     * AJAX: Create Tasks from Template
     */
    public function ajax_create_from_template() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('edit_tasks')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d');
        
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'task_template') {
            wp_send_json_error(['message' => 'قالب یافت نشد']);
        }
        
        $tasks = get_post_meta($template_id, '_template_tasks', true);
        if (!is_array($tasks) || empty($tasks)) {
            wp_send_json_error(['message' => 'این قالب وظیفه‌ای ندارد']);
        }
        
        $created_tasks = [];
        $current_date = strtotime($start_date);
        
        foreach ($tasks as $task_data) {
            $task_id = wp_insert_post([
                'post_type' => 'task',
                'post_title' => $task_data['title'],
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
            ]);
            
            if ($task_id) {
                // Set project
                if ($project_id > 0) {
                    update_post_meta($task_id, '_project_id', $project_id);
                }
                
                // Set dates
                $duration_days = isset($task_data['duration']) ? floatval($task_data['duration']) : 1;
                $due_date = date('Y-m-d', strtotime("+{$duration_days} days", $current_date));
                
                update_post_meta($task_id, '_start_date', date('Y-m-d', $current_date));
                update_post_meta($task_id, '_due_date', $due_date);
                
                // Set default status (todo)
                $todo_term = get_term_by('slug', 'todo', 'task_status');
                if ($todo_term) {
                    wp_set_object_terms($task_id, $todo_term->term_id, 'task_status');
                }
                
                $created_tasks[] = $task_id;
                
                // Update current date for next task
                $current_date = strtotime($due_date);
            }
        }
        
        wp_send_json_success([
            'message' => sprintf('%d وظیفه از قالب ایجاد شد', count($created_tasks)),
            'task_ids' => $created_tasks,
        ]);
    }

    /**
     * AJAX: Save Template
     */
    public function ajax_save_template() {
        check_ajax_referer('puzzlingcrm-ajax-nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $tasks = isset($_POST['tasks']) ? json_decode(stripslashes($_POST['tasks']), true) : [];
        $category = isset($_POST['category']) ? sanitize_key($_POST['category']) : '';
        $is_recurring = isset($_POST['is_recurring']) ? (bool)$_POST['is_recurring'] : false;
        $recurring_type = isset($_POST['recurring_type']) ? sanitize_key($_POST['recurring_type']) : '';
        
        if (empty($title)) {
            wp_send_json_error(['message' => 'عنوان الزامی است']);
        }
        
        $post_data = [
            'post_type' => 'task_template',
            'post_title' => $title,
            'post_status' => 'publish',
        ];
        
        if ($template_id > 0) {
            $post_data['ID'] = $template_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'خطا در ذخیره قالب']);
        }
        
        $template_id = $template_id > 0 ? $template_id : $result;
        
        // Save metadata
        update_post_meta($template_id, '_template_tasks', $tasks);
        update_post_meta($template_id, '_is_recurring', $is_recurring ? 1 : 0);
        update_post_meta($template_id, '_recurring_type', $recurring_type);
        
        // Set category
        if ($category) {
            wp_set_object_terms($template_id, $category, 'template_category');
        }
        
        wp_send_json_success([
            'message' => 'قالب با موفقیت ذخیره شد',
            'template_id' => $template_id,
        ]);
    }

    /**
     * Process Recurring Tasks (Cron Job)
     */
    public function process_recurring_tasks() {
        $templates = get_posts([
            'post_type' => 'task_template',
            'posts_per_page' => -1,
            'meta_query' => [
                ['key' => '_is_recurring', 'value' => '1'],
            ],
        ]);
        
        foreach ($templates as $template) {
            $recurring_type = get_post_meta($template->ID, '_recurring_type', true);
            $last_run = get_post_meta($template->ID, '_last_recurring_run', true);
            
            $should_run = false;
            
            switch ($recurring_type) {
                case 'daily':
                    if (!$last_run || strtotime($last_run) < strtotime('today')) {
                        $should_run = true;
                    }
                    break;
                case 'weekly':
                    if (!$last_run || strtotime($last_run) < strtotime('last monday')) {
                        $should_run = true;
                    }
                    break;
                case 'monthly':
                    if (!$last_run || date('Y-m', strtotime($last_run)) < date('Y-m')) {
                        $should_run = true;
                    }
                    break;
            }
            
            if ($should_run) {
                // Get projects that use this template
                $projects = get_posts([
                    'post_type' => 'project',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        ['key' => '_recurring_template', 'value' => $template->ID],
                    ],
                ]);
                
                foreach ($projects as $project) {
                    $this->create_tasks_from_template($template->ID, $project->ID);
                }
                
                update_post_meta($template->ID, '_last_recurring_run', current_time('mysql'));
            }
        }
    }

    /**
     * Create tasks from template (internal)
     */
    private function create_tasks_from_template($template_id, $project_id = 0, $start_date = null) {
        $tasks = get_post_meta($template_id, '_template_tasks', true);
        if (!is_array($tasks)) return;
        
        $start_date = $start_date ? $start_date : date('Y-m-d');
        $current_date = strtotime($start_date);
        
        foreach ($tasks as $task_data) {
            $task_id = wp_insert_post([
                'post_type' => 'task',
                'post_title' => $task_data['title'],
                'post_status' => 'publish',
            ]);
            
            if ($task_id) {
                if ($project_id > 0) {
                    update_post_meta($task_id, '_project_id', $project_id);
                }
                
                $duration = isset($task_data['duration']) ? floatval($task_data['duration']) : 1;
                $due_date = date('Y-m-d', strtotime("+{$duration} days", $current_date));
                
                update_post_meta($task_id, '_start_date', date('Y-m-d', $current_date));
                update_post_meta($task_id, '_due_date', $due_date);
                
                $current_date = strtotime($due_date);
            }
        }
    }
}

// Initialize
new PuzzlingCRM_Task_Template_Manager();

