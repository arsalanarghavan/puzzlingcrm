<?php
/**
 * Plugin Name:       PuzzlingCRM
 * Plugin URI:        https://Puzzlingco.com/
 * Description:       A complete CRM and Project Management solution for Social Marketing agencies.
 * Version:           2.0.0
 * Author:            Arsalan Arghavan
 * Author URI:        https://ArsalanArghavan.ir/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       puzzlingcrm
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define Plugin Constants
define( 'PUZZLINGCRM_VERSION', '2.0.0' ); // Major Update - All 16 Enterprise Features Complete!
define( 'PUZZLINGCRM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PUZZLINGCRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PUZZLING_CRM_TEMPLATE_PATH', PUZZLINGCRM_PLUGIN_DIR . 'templates/' );

// Include core files
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-installer.php';
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/puzzling-functions.php';
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-puzzlingcrm.php';

// --- Activation / Deactivation Hooks ---
register_activation_hook( __FILE__, [ 'PuzzlingCRM_Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'PuzzlingCRM_Installer', 'deactivate' ] );


// === [START] FINAL & CORRECTED SCRIPT ENQUEUEING ===

/**
 * Registers and conditionally enqueues the plugin's scripts and styles.
 * This function solves all JavaScript-related issues by loading scripts ONLY where they are needed.
 */
function puzzling_enqueue_assets($hook) {
    // --- GLOBAL SCRIPTS (Load on all PuzzlingCRM admin pages) ---
    // We only load these if the page belongs to our plugin.
    if (strpos($hook, 'puzzling-') === false) {
        return;
    }
    
    // Enqueue SweetAlert2 if available
    wp_enqueue_script('sweetalert2'); // Assuming SweetAlert2 is registered elsewhere, if not, you must register it first.

    // Register main script (but don't enqueue yet)
    wp_register_script(
        'puzzlingcrm-scripts',
        PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzlingcrm-scripts.js',
        ['jquery'], // Dependency on jQuery
        PUZZLINGCRM_VERSION,
        true
    );

    // Enqueue the main script
    wp_enqueue_script('puzzlingcrm-scripts');

    // Pass PHP variables to the main script
    wp_localize_script('puzzlingcrm-scripts', 'puzzlingcrm_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('puzzlingcrm-ajax-nonce'), // Use consistent nonce
        'lang'     => [
            'ok_button'     => __('باشه', 'puzzlingcrm'),
            'success_title' => __('موفق', 'puzzlingcrm'),
            'error_title'   => __('خطا', 'puzzlingcrm'),
            'server_error'  => __('یک خطای سرور رخ داد.', 'puzzlingcrm'),
        ]
    ]);

    // --- CONDITIONAL SCRIPTS (Load only on specific pages) ---
    $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

    // Load Persian Date library globally for all PuzzlingCRM pages
    wp_enqueue_script(
        'persian-date',
        'https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js',
        [],
        '1.1.0',
        true
    );

    // Load Datepicker only on pages that need it (e.g., contracts, projects)
    if (in_array($current_page, ['puzzling-contracts', 'puzzling-projects'])) {
        wp_enqueue_style(
            'puzzling-datepicker-css',
            PUZZLINGCRM_PLUGIN_URL . 'assets/css/puzzling-datepicker.css',
            [],
            PUZZLINGCRM_VERSION
        );
        wp_enqueue_script(
            'puzzling-datepicker',
            PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzling-datepicker.js',
            ['jquery', 'persian-date'],
            PUZZLINGCRM_VERSION,
            true
        );
    }

    // Load the specific script for the leads page (to handle the delete button)
    if ($current_page === 'puzzling-leads') {
        wp_enqueue_script(
            'puzzlingcrm-lead-management',
            PUZZLINGCRM_PLUGIN_URL . 'assets/js/lead-management.js', // The new file you should create
            ['jquery', 'puzzlingcrm-scripts'], // Depends on jQuery and main scripts
            PUZZLINGCRM_VERSION,
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'puzzling_enqueue_assets');


/**
 * Enqueue scripts for frontend (shortcodes). Kept simple for now.
 */
function puzzling_enqueue_frontend_assets() {
    // You can enqueue specific frontend styles and scripts here if needed
}
add_action('wp_enqueue_scripts', 'puzzling_enqueue_frontend_assets');

// === [END] FINAL SOLUTION ===


/**
 * Checks for required plugin dependencies.
 */
function puzzling_check_dependencies() {
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'admin_notices', 'puzzling_dependency_notice' );
        deactivate_plugins( plugin_basename( __FILE__ ) );
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}
add_action( 'admin_init', 'puzzling_check_dependencies' );

/**
 * Renders the admin notice for missing dependencies.
 */
function puzzling_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e( 'پلاگین PuzzlingCRM غیرفعال شد', 'puzzlingcrm' ); ?></strong><br>
            <?php esc_html_e( 'این پلاگین نیازمند نصب و فعال بودن پلاگین WooCommerce است. لطفاً ابتدا آن را فعال کرده و سپس PuzzlingCRM را فعال کنید.', 'puzzlingcrm' ); ?>
        </p>
    </div>
    <?php
}

// Load plugin textdomain for translation.
function puzzling_load_textdomain() {
    load_plugin_textdomain( 'puzzlingcrm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'puzzling_load_textdomain' );


/**
 * Begins execution of the plugin.
 */
function run_puzzlingcrm() {
    $plugin = PuzzlingCRM::instance();
    $plugin->run();
}

run_puzzlingcrm();