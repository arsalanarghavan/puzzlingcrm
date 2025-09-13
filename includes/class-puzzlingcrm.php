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
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cpt-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-roles-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-shortcode-manager.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-frontend-dashboard.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-form-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-cron-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-settings-handler.php';
        
        // Integrations
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-zarinpal-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-melipayamak-handler.php';
    }

    private function define_hooks() {
        register_activation_hook( PUZZLINGCRM_PLUGIN_DIR . 'puzzlingcrm.php', [ 'PuzzlingCRM_Installer', 'activate' ] );
        
        // Add hook for displaying form after purchase
        add_action( 'woocommerce_thankyou', [ $this, 'display_customer_info_form' ], 10, 1 );

        new PuzzlingCRM_CPT_Manager();
        new PuzzlingCRM_Roles_Manager();
        new PuzzlingCRM_Shortcode_Manager();
        new PuzzlingCRM_Form_Handler();
        new PuzzlingCRM_Ajax_Handler();
        new PuzzlingCRM_Cron_Handler();
    }

    /**
     * Conditionally enqueues scripts and styles only when the dashboard is being rendered.
     */
    public static function enqueue_dashboard_assets() {
        wp_enqueue_style( 'puzzlingcrm-styles', PUZZLINGCRM_PLUGIN_URL . 'assets/css/puzzlingcrm-styles.css', [], PUZZLINGCRM_VERSION );
        
        wp_enqueue_script( 'puzzlingcrm-scripts', PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzlingcrm-scripts.js', ['jquery'], PUZZLINGCRM_VERSION, true );
        
        // Pass data to JS, like the AJAX URL and a nonce for security
        wp_localize_script('puzzlingcrm-scripts', 'puzzlingcrm_ajax_obj', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('puzzlingcrm-ajax-nonce')
        ]);
    }
    
    public function display_customer_info_form( $order_id ) {
        if ( ! $order_id ) return;
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        $user_id = $order->get_user_id();
        if ( get_user_meta( $user_id, 'puzzling_crm_form_submitted', true ) ) {
            echo '<h4>اطلاعات تکمیلی شما قبلاً دریافت شده است. به زودی پروژه شما در داشبور قابل مشاهده خواهد بود.</h4>';
            return;
        }
        echo '<h2>لطفاً برای شروع پروژه، اطلاعات زیر را تکمیل کنید:</h2>';
        ?>
        <form id="puzzling-customer-info-form" action="<?php echo esc_url( get_permalink( get_page_by_title('PuzzlingCRM Dashboard') ) ); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="puzzling_form_order_id" value="<?php echo esc_attr( $order_id ); ?>">
            <?php wp_nonce_field( 'puzzling_save_customer_info', 'puzzling_customer_info_nonce' ); ?>
            <p style="margin-bottom: 15px;">
                <label for="business_name">نام کسب و کار:</label><br>
                <input type="text" id="business_name" name="business_name" style="width: 100%; padding: 8px;" required>
            </p>
            <p style="margin-bottom: 15px;">
                <label for="business_desc">توضیح کسب و کار:</label><br>
                <textarea id="business_desc" name="business_desc" rows="5" style="width: 100%; padding: 8px;" required></textarea>
            </p>
            <p style="margin-bottom: 15px;">
                <label for="business_logo">لوگو (اختیاری):</label><br>
                <input type="file" id="business_logo" name="business_logo" accept="image/*">
            </p>
            <p>
                <input type="submit" name="puzzling_submit_customer_info" value="ارسال اطلاعات و ورود به داشبورد">
            </p>
        </form>
        <?php
    }

    public function run() {
        // The plugin is running
    }
}