<?php
/**
 * Plugin Name:       PuzzlingCRM
 * Plugin URI:        https://Puzzlingco.com/
 * Description:       A complete CRM and Project Management solution for Social Marketing agencies.
 * Version:           0.0.297
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
define( 'PUZZLINGCRM_VERSION', '1.4.0' );
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


// === [START] FINAL SOLUTION: Correctly Enqueue Scripts and Styles ===

/**
 * Registers and enqueues the plugin's scripts and styles with correct dependencies.
 * This function solves all JavaScript-related issues including the form submission problem.
 */
function puzzling_enqueue_assets() {
    // --- STYLES ---
    wp_enqueue_style(
        'puzzling-datepicker-css',
        PUZZLINGCRM_PLUGIN_URL . 'assets/css/puzzling-datepicker.css',
        [],
        PUZZLINGCRM_VERSION
    );
    wp_enqueue_style(
        'puzzlingcrm-styles',
        PUZZLINGCRM_PLUGIN_URL . 'assets/css/puzzlingcrm-styles.css',
        [],
        PUZZLINGCRM_VERSION
    );

    // --- SCRIPTS ---
    // 1. Register the Persian Datepicker script, making it dependent on jQuery.
    wp_register_script(
        'puzzling-datepicker',
        PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzling-datepicker.js',
        ['jquery'], // Dependency
        PUZZLINGCRM_VERSION,
        true // Load in footer
    );

    // 2. Register the main CRM script, making it dependent on BOTH jQuery and our datepicker script.
    wp_register_script(
        'puzzlingcrm-scripts',
        PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzlingcrm-scripts.js',
        ['jquery', 'puzzling-datepicker'], // Dependencies
        PUZZLINGCRM_VERSION,
        true // Load in footer
    );

    // 3. Now enqueue the scripts. WordPress will automatically handle the dependency order.
    wp_enqueue_script('puzzling-datepicker');
    wp_enqueue_script('puzzlingcrm-scripts');

    // 4. Pass PHP variables (like AJAX URL and nonces) to our main script.
    wp_localize_script('puzzlingcrm-scripts', 'puzzlingcrm_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('puzzling_add_lead_nonce'), // A specific nonce for security
        'lang'     => [
            'ok_button'     => __('باشه', 'puzzlingcrm'),
            'success_title' => __('موفق', 'puzzlingcrm'),
            'error_title'   => __('خطا', 'puzzlingcrm'),
            'server_error'  => __('یک خطای سرور رخ داد.', 'puzzlingcrm'),
        ]
    ]);
}
// Hook this function to run on both the admin area and the front-end (for shortcodes).
add_action('wp_enqueue_scripts', 'puzzling_enqueue_assets');
add_action('admin_enqueue_scripts', 'puzzling_enqueue_assets');

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