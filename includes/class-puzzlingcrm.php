<?php
class PuzzlingCRM {

    /**
     * The single instance of the class.
     */
    protected static $_instance = null;

    /**
     * Main PuzzlingCRM Instance.
     */
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
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/puzzling-functions.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-installer.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-admin-menu.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cpt-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-roles-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-user-profile.php'; // Corrected constant name
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
        // Activation & Deactivation hooks
        register_activation_hook( PUZZLINGCRM_PLUGIN_DIR . 'puzzlingcrm.php', [ 'PuzzlingCRM_Installer', 'activate' ] );
        register_deactivation_hook( PUZZLINGCRM_PLUGIN_DIR . 'puzzlingcrm.php', [ 'PuzzlingCRM_Installer', 'deactivate' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_dashboard_assets' ] );
        
        new PuzzlingCRM_Admin_Menu();
        new PuzzlingCRM_CPT_Manager();
        new PuzzlingCRM_Roles_Manager();
        new PuzzlingCRM_User_Profile(); // Instantiate User Profile class
        new PuzzlingCRM_Shortcode_Manager();
        new PuzzlingCRM_Form_Handler();
        new PuzzlingCRM_Ajax_Handler();
        new PuzzlingCRM_Cron_Handler();
    }

    /**
     * Conditionally enqueues scripts and styles.
     */
    public function enqueue_dashboard_assets() {
        // Only load assets on the dashboard page
        $dashboard_page_id = get_option('puzzling_dashboard_page_id');
        if ( is_page( $dashboard_page_id ) || (is_singular('project') && has_shortcode(get_post($dashboard_page_id)->post_content, 'puzzling_dashboard')) ) {
            wp_enqueue_style( 'puzzlingcrm-styles', PUZZLINGCRM_PLUGIN_URL . 'assets/css/puzzlingcrm-styles.css', [], PUZZLINGCRM_VERSION );
            
            wp_enqueue_script( 'puzzlingcrm-scripts', PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzlingcrm-scripts.js', ['jquery'], PUZZLINGCRM_VERSION, true );
            
            wp_localize_script('puzzlingcrm-scripts', 'puzzlingcrm_ajax_obj', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('puzzlingcrm-ajax-nonce'),
                'lang'     => [
                    'confirm_delete_task' => __('Are you sure you want to permanently delete this task? This action cannot be undone.', 'puzzlingcrm'),
                    'confirm_delete_project' => __('Are you sure you want to permanently delete this project? This will also delete all associated contracts and data. This action cannot be undone.', 'puzzlingcrm'),
                ]
            ]);
        }
    }
    
    public function run() {
        // The plugin is running
    }
}