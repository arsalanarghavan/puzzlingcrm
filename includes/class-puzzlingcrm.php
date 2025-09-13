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
     * Conditionally enqueues scripts and styles.
     * FIX: Broadened the condition to ensure styles load on any page with a Puzzling shortcode.
     */
    public function enqueue_dashboard_assets() {
        global $post;
        $load_assets = false;
        
        // List of all shortcodes that require the assets
        $shortcodes = [
            'puzzling_dashboard', 'puzzling_projects', 'puzzling_contracts', 'puzzling_invoices',
            'puzzling_pro_invoices', 'puzzling_appointments', 'puzzling_tickets', 'puzzling_tasks',
            'puzzling_customers', 'puzzling_staff', 'puzzling_subscriptions', 'puzzling_reports',
            'puzzling_settings', 'puzzling_logs'
        ];

        if ( is_a( $post, 'WP_Post' ) ) {
            foreach($shortcodes as $sc) {
                if ( has_shortcode( $post->post_content, $sc ) ) {
                    $load_assets = true;
                    break;
                }
            }
        }

        if ( $load_assets ) {
            wp_enqueue_style( 'puzzlingcrm-styles', PUZZLINGCRM_PLUGIN_URL . 'assets/css/puzzlingcrm-styles.css', [], PUZZLINGCRM_VERSION );
            
            wp_enqueue_script( 'puzzlingcrm-scripts', PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzlingcrm-scripts.js', ['jquery'], PUZZLINGCRM_VERSION, true );
            
            wp_localize_script('puzzlingcrm-scripts', 'puzzlingcrm_ajax_obj', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('puzzlingcrm-ajax-nonce'),
                'lang'     => [
                    'confirm_delete_task' => __('آیا از حذف این وظیفه مطمئن هستید؟ این عمل قابل بازگشت نیست.', 'puzzlingcrm'),
                    'confirm_delete_project' => __('آیا از حذف این پروژه مطمئن هستید؟ تمام قراردادها و اطلاعات مرتبط با آن نیز حذف خواهند شد. این عمل قابل بازگشت نیست.', 'puzzlingcrm'),
                ]
            ]);
        }
    }
    
    public function run() {
        // The plugin is running
    }
}