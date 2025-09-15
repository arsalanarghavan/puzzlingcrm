<?php
class PuzzlingCRM {

    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    private function load_dependencies() {
        // Core Classes
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-admin-menu.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cpt-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-roles-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-user-profile.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-shortcode-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-frontend-dashboard.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-form-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cron-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-settings-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-logger.php';
        
        // SMS Interface and Integrations
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-sms-service-interface.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-zarinpal-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-melipayamak-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-parsgreen-handler.php';
    }

    private function define_hooks() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_dashboard_assets' ] );
        
        new PuzzlingCRM_Admin_Menu();
        new PuzzlingCRM_CPT_Manager();
        new PuzzlingCRM_Roles_Manager();
        new PuzzlingCRM_User_Profile();
        new PuzzlingCRM_Shortcode_Manager();
        new PuzzlingCRM_Form_Handler();
        new PuzzlingCRM_Ajax_Handler();
        new PuzzlingCRM_Cron_Handler();
    }

    /**
     * Enqueues scripts and styles.
     * FINAL FIX (v4): Added FullCalendar and DHTMLX Gantt for advanced views.
     */
    public function enqueue_dashboard_assets() {
        // Enqueue Font Awesome from a reliable CDN
        wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', [], '6.5.1' );
        
        wp_enqueue_style( 'puzzlingcrm-styles', PUZZLINGCRM_PLUGIN_URL . 'assets/css/puzzlingcrm-styles.css', [], PUZZLINGCRM_VERSION );
        
        // Enqueue jQuery UI Sortable & Datepicker
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-datepicker');

        // **NEW: Enqueue FullCalendar assets**
        wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', ['jquery'], '6.1.11', true);

        // **NEW: Enqueue DHTMLX Gantt assets**
        wp_enqueue_script('dhtmlx-gantt', 'https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js', [], '8.0', true);
        wp_enqueue_style('dhtmlx-gantt', 'https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css', [], '8.0');


        wp_enqueue_script( 'puzzlingcrm-scripts', PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzlingcrm-scripts.js', ['jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker', 'fullcalendar', 'dhtmlx-gantt'], PUZZLINGCRM_VERSION, true );
        
        // Fetch users and labels for quick edit functionality
        $all_users = get_users(['role__in' => ['team_member', 'system_manager', 'administrator']]);
        $users_for_js = [];
        foreach($all_users as $user) {
            $users_for_js[] = ['id' => $user->ID, 'text' => $user->display_name];
        }

        $all_labels = get_terms(['taxonomy' => 'task_label', 'hide_empty' => false]);
        $labels_for_js = [];
        foreach($all_labels as $label) {
            $labels_for_js[] = ['id' => $label->term_id, 'text' => $label->name];
        }

        wp_localize_script('puzzlingcrm-scripts', 'puzzlingcrm_ajax_obj', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('puzzlingcrm-ajax-nonce'),
            'lang'     => [
                'confirm_delete_task' => __('آیا از حذف این وظیفه مطمئن هستید؟ این عمل قابل بازگشت نیست.', 'puzzlingcrm'),
                'confirm_delete_project' => __('آیا از حذف این پروژه مطمئن هستید؟ تمام قراردادها و اطلاعات مرتبط با آن نیز حذف خواهند شد. این عمل قابل بازگشت نیست.', 'puzzlingcrm'),
            ],
            'users' => $users_for_js,
            'labels' => $labels_for_js,
        ]);
    }
    
    public function run() {
        // The plugin is running
    }
}