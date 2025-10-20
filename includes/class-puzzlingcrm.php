<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package    PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 * @author     Arsalan Arghavan
 */

class PuzzlingCRM {

    /**
     * The single instance of the class.
     * @var PuzzlingCRM
     */
    protected static $_instance = null;

    /**
     * Main PuzzlingCRM Instance.
     * Ensures only one instance of PuzzlingCRM is loaded or can be loaded.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * PuzzlingCRM Constructor.
     */
    public function __construct() {
        // Register autoloader for enterprise features (lazy loading)
        spl_autoload_register([__CLASS__, 'autoload_enterprise_features']);
        
        $this->load_dependencies();
        $this->define_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     * **MODIFIED**: Now loads the main AJAX handler instead of the old monolithic one.
     */
    private function load_dependencies() {
        // Core Classes
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-admin-menu.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cpt-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-roles-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-user-profile.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-shortcode-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-frontend-dashboard.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-form-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-main-ajax-handler.php'; // **CORRECTED**
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cron-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-settings-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-logger.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-agile-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-automation-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-pdf-reporter.php';
        
        // SMS Interface and Integrations
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-sms-service-interface.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-zarinpal-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-melipayamak-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-parsgreen-handler.php';
		// NEW: Telegram Handler
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-telegram-handler.php';
        
        // Login Page and AJAX Handler
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-login-page.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-login-ajax-handler.php';
        
        // Dashboard Router
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-dashboard-router.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-task-template-manager.php';
        
        // Cache & Performance Optimizer
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cache-optimizer.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-database-optimizer.php';
        
        // ENTERPRISE FEATURES (Lazy loaded - only when needed)
        // These will be loaded on-demand through autoloader
    }

    /**
     * Autoloader for Enterprise Features (Lazy Loading)
     */
    public static function autoload_enterprise_features($class_name) {
        // Only load our classes
        if (strpos($class_name, 'PuzzlingCRM_') !== 0) {
            return;
        }

        $class_map = [
            'PuzzlingCRM_WebSocket_Handler' => 'class-websocket-handler.php',
            'PuzzlingCRM_Elasticsearch_Handler' => 'class-elasticsearch-handler.php',
            'PuzzlingCRM_Activity_Timeline' => 'class-activity-timeline.php',
            'PuzzlingCRM_Smart_Reminders' => 'class-smart-reminders.php',
            'PuzzlingCRM_PWA_Handler' => 'class-pwa-handler.php',
            'PuzzlingCRM_Kanban_Handler' => 'class-kanban-handler.php',
            'PuzzlingCRM_Time_Tracking' => 'class-time-tracking.php',
            'PuzzlingCRM_Document_Management' => 'class-document-management.php',
            'PuzzlingCRM_White_Label' => 'class-white-label.php',
            'PuzzlingCRM_Advanced_Analytics' => 'class-advanced-analytics.php',
            'PuzzlingCRM_Team_Chat' => 'class-team-chat.php',
            'PuzzlingCRM_Session_Management' => 'class-session-management.php',
            'PuzzlingCRM_Data_Encryption' => 'class-data-encryption.php',
            'PuzzlingCRM_Field_Security' => 'class-field-security.php',
        ];

        if (isset($class_map[$class_name])) {
            $file = PUZZLINGCRM_PLUGIN_DIR . 'includes/' . $class_map[$class_name];
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Register all of the hooks related to the functionality of the plugin.
     * **MODIFIED**: Initializes the new main AJAX handler.
     */
    private function define_hooks() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_dashboard_assets' ] );
        
        // Initialize all core classes
        new PuzzlingCRM_Admin_Menu();
        new PuzzlingCRM_CPT_Manager();
        new PuzzlingCRM_Roles_Manager();
        new PuzzlingCRM_User_Profile();
        new PuzzlingCRM_Shortcode_Manager();
        new PuzzlingCRM_Form_Handler();
        new PuzzlingCRM_Main_Ajax_Handler(); // **CORRECTED**
        new PuzzlingCRM_Cron_Handler();
        new PuzzlingCRM_Agile_Handler();
        new PuzzlingCRM_Automation_Handler();
        new PuzzlingCRM_Login_Page();
        new PuzzlingCRM_Login_Ajax_Handler();
        new PuzzlingCRM_Dashboard_Router();
        new PuzzlingCRM_Cache_Optimizer();
        new PuzzlingCRM_Database_Optimizer();
        
        // Initialize ONLY Essential Enterprise Features
        // Others will be lazy-loaded on-demand
        new PuzzlingCRM_Session_Management();
        new PuzzlingCRM_PWA_Handler();
        new PuzzlingCRM_White_Label();
        
        // Load Activity Timeline and Smart Reminders on 'init' hook (when pluggable functions are available)
        add_action('init', function() {
            if (is_admin() || is_user_logged_in()) {
                new PuzzlingCRM_Activity_Timeline();
                new PuzzlingCRM_Smart_Reminders();
            }
        });
        
        // Load advanced features only when needed
        add_action('admin_enqueue_scripts', function($hook) {
            // WebSocket - only on dashboard pages
            if (strpos($hook, 'puzzling-') !== false) {
                new PuzzlingCRM_WebSocket_Handler();
            }
            
            // Kanban - only on kanban/project pages
            if (strpos($hook, 'puzzling-kanban') !== false || strpos($hook, 'puzzling-projects') !== false) {
                new PuzzlingCRM_Kanban_Handler();
            }
            
            // Analytics - only on analytics/dashboard pages
            if (strpos($hook, 'puzzling-analytics') !== false || strpos($hook, 'puzzling-dashboard') !== false) {
                new PuzzlingCRM_Advanced_Analytics();
            }
            
            // Chat - only when chat is open
            if (isset($_GET['chat']) || strpos($hook, 'puzzling-chat') !== false) {
                new PuzzlingCRM_Team_Chat();
            }
            
            // Documents - only on document pages
            if (strpos($hook, 'puzzling-documents') !== false || isset($_GET['documents'])) {
                new PuzzlingCRM_Document_Management();
            }
            
            // Time Tracking - only when needed
            if (strpos($hook, 'puzzling-time-tracking') !== false || isset($_GET['time_tracking'])) {
                new PuzzlingCRM_Time_Tracking();
            }
        });
        
        // Data Encryption - Always load for security
        new PuzzlingCRM_Data_Encryption();
        new PuzzlingCRM_Field_Security();
    }

    /**
     * Enqueues scripts and styles for the frontend dashboard.
     * OPTIMIZED: Conditional loading based on current page/shortcode
     */
    public function enqueue_dashboard_assets() {
        // Only load on dashboard shortcode pages
        global $post;
        
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'puzzling_dashboard')) {
            return;
        }
        
        // Enqueue Font Awesome - Deferred
        wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', [], '6.5.1' );
        
        // SweetAlert - Essential
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], '11', true);

        // Main scripts file - Minified dependencies
        wp_enqueue_script( 'puzzlingcrm-scripts', PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzlingcrm-scripts.js', ['jquery', 'sweetalert2'], PUZZLINGCRM_VERSION, true );
        
        // Load heavy libraries ONLY when needed
        $current_action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        $current_page = isset($_GET['pzl_page']) ? sanitize_key($_GET['pzl_page']) : 'dashboard';
        
        // Calendar - Only on calendar/appointments pages
        if (in_array($current_page, ['appointments', 'calendar', 'schedule'])) {
            wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', ['jquery'], '6.1.11', true);
        }
        
        // Gantt - Only on project/gantt pages
        if (in_array($current_page, ['projects', 'gantt'])) {
            wp_enqueue_script('dhtmlx-gantt', 'https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js', [], '8.0', true);
            wp_enqueue_style('dhtmlx-gantt', 'https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css', [], '8.0');
        }
        
        // Persian Date & Datepicker - Only when date inputs exist
        if (in_array($current_page, ['contracts', 'projects', 'appointments', 'tasks'])) {
            wp_enqueue_script('persian-date', 'https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js', [], '1.1.0', true);
            wp_enqueue_style( 'puzzling-datepicker-styles', PUZZLINGCRM_PLUGIN_URL . 'assets/css/puzzling-datepicker.css', [], PUZZLINGCRM_VERSION );
            wp_enqueue_script( 'puzzling-datepicker-scripts', PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzling-datepicker.js', ['jquery', 'persian-date'], PUZZLINGCRM_VERSION, true );
        }
        
        // Sortable - Only on kanban/task pages
        if (in_array($current_page, ['tasks', 'kanban', 'projects'])) {
            wp_enqueue_script('jquery-ui-sortable');
        }
        
        // Page-specific scripts
        if ($current_page === 'users' || $current_page === 'staff' || $current_page === 'customers') {
            wp_enqueue_script( 'puzzling-user-management', PUZZLINGCRM_PLUGIN_URL . 'assets/js/user-management.js', ['jquery', 'sweetalert2', 'puzzlingcrm-scripts'], PUZZLINGCRM_VERSION, true );
        }
        
        // SMS Modal - Only when SMS features are used
        if (in_array($current_page, ['leads', 'customers', 'users'])) {
            wp_enqueue_script( 'puzzling-sms-modal', PUZZLINGCRM_PLUGIN_URL . 'assets/js/sms-modal.js', ['jquery', 'sweetalert2', 'puzzlingcrm-scripts'], PUZZLINGCRM_VERSION, true );
        }
        
        // Dark Mode - Always load (lightweight)
        wp_enqueue_style('puzzlingcrm-dark-mode', PUZZLINGCRM_PLUGIN_URL . 'assets/css/dark-mode.css', [], PUZZLINGCRM_VERSION);
        wp_enqueue_script('puzzlingcrm-dark-mode', PUZZLINGCRM_PLUGIN_URL . 'assets/js/dark-mode.js', ['jquery'], PUZZLINGCRM_VERSION, true);
        
        // Touch Optimizations - Always load for mobile
        wp_enqueue_style('puzzlingcrm-touch', PUZZLINGCRM_PLUGIN_URL . 'assets/css/touch-optimizations.css', [], PUZZLINGCRM_VERSION);
        
        // Empty States CSS - Always load (lightweight)
        wp_enqueue_style('puzzlingcrm-empty-states', PUZZLINGCRM_PLUGIN_URL . 'assets/css/empty-states.css', [], PUZZLINGCRM_VERSION);
        
        // Data for JS - Cache in transient for 5 minutes to reduce DB queries
        $cache_key = 'puzzlingcrm_js_data_' . get_current_user_id();
        $cached_data = get_transient($cache_key);
        
        if ($cached_data === false) {
            $all_users = get_users(['role__in' => ['team_member', 'system_manager', 'administrator'], 'fields' => ['ID', 'display_name']]);
            $users_for_js = [];
            foreach($all_users as $user) {
                $users_for_js[] = ['id' => $user->ID, 'text' => $user->display_name];
            }

            $all_labels = get_terms(['taxonomy' => 'task_label', 'hide_empty' => false, 'fields' => 'id=>name']);
            $labels_for_js = [];
            foreach($all_labels as $id => $name) {
                $labels_for_js[] = ['id' => $id, 'text' => $name];
            }
            
            $cached_data = [
                'users' => $users_for_js,
                'labels' => $labels_for_js
            ];
            
            set_transient($cache_key, $cached_data, 5 * MINUTE_IN_SECONDS);
        }

        wp_localize_script('puzzlingcrm-scripts', 'puzzlingcrm_ajax_obj', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('puzzlingcrm-ajax-nonce'),
            'lang'     => [
                'confirm_title' => __('آیا مطمئن هستید؟', 'puzzlingcrm'),
                'confirm_delete_project' => __('تمام قراردادها و اطلاعات مرتبط با این پروژه نیز حذف خواهند شد. این عمل قابل بازگشت نیست.', 'puzzlingcrm'),
                'confirm_button' => __('بله، حذف کن!', 'puzzlingcrm'),
                'cancel_button' => __('انصراف', 'puzzlingcrm'),
                'success_title' => __('موفقیت‌آمیز', 'puzzlingcrm'),
                'error_title' => __('خطا', 'puzzlingcrm'),
                'info_title' => __('راهنمایی', 'puzzlingcrm'),
                'server_error' => __('یک خطای ناشناخته در ارتباط با سرور رخ داد.', 'puzzlingcrm'),
                'ok_button' => __('باشه', 'puzzlingcrm'),
            ],
            'users' => $cached_data['users'],
            'labels' => $cached_data['labels'],
        ]);
    }
    
    /**
     * The main execution function of the plugin.
     */
    public function run() {
        // The plugin is running. This function can be expanded later if needed.
    }
}