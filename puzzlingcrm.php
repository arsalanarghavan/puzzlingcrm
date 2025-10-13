<?php
/**
 * Plugin Name:       PuzzlingCRM
 * Plugin URI:        https://Puzzlingco.com/
 * Description:       A complete CRM and Project Management solution for Social Marketing agencies.
 * Version:           0.0.263
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

// Include core files
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-installer.php';
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/puzzling-functions.php';
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-puzzlingcrm.php';

// --- Activation / Deactivation Hooks ---
register_activation_hook( __FILE__, [ 'PuzzlingCRM_Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'PuzzlingCRM_Installer', 'deactivate' ] );

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


// --- [شروع راهکار نهایی: Output Buffering سراسری برای AJAX] ---

/**
 * شروع بافر خروجی به صورت سراسری در هر درخواست AJAX.
 */
function puzzling_start_ajax_output_buffer() {
    // فقط در صورتی که درخواست AJAX باشد، بافر را شروع کن.
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        // شروع بافرینگ در سریع‌ترین حالت ممکن (اولویت ۱)
        ob_start();
    }
}
add_action( 'plugins_loaded', 'puzzling_start_ajax_output_buffer', 1 );


/**
 * پاکسازی بافر خروجی در هنگام خاتمه درخواست AJAX.
 */
function puzzling_clean_ajax_output_buffer() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        // مطمئن شوید که بافر در حال حاضر فعال است و محتوایی در آن جمع شده است.
        if ( ob_get_level() > 0 && ob_get_length() > 0 ) {
            // محتویات بافر را دور می‌ریزیم تا فقط JSON خالص ارسال شود.
            ob_end_clean();
        }
    }
}
// این اکشن باید با اولویت کم اجرا شود (بعد از اجرای همه توابع دیگر)
add_action( 'shutdown', 'puzzling_clean_ajax_output_buffer', 0 ); 

// --- [پایان راهکار نهایی: Output Buffering سراسری برای AJAX] ---


/**
 * Begins execution of the plugin.
 */
function run_puzzlingcrm() {
    $plugin = PuzzlingCRM::instance();
    $plugin->run();
}

run_puzzlingcrm();