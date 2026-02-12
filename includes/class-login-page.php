<?php
/**
 * PuzzlingCRM Login Page Handler (Exact Xintra Template)
 * Manages custom login page with SMS OTP
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Login_Page {

    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'template_redirect']);
        add_shortcode('puzzling_login', [$this, 'render_login_page']);
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^login/?$', 'index.php?puzzling_login=1', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'puzzling_login';
        return $vars;
    }

    public function template_redirect() {
        if (get_query_var('puzzling_login')) {
            $this->load_login_template();
            exit;
        }
    }

    private function load_login_template() {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $redirect_url = $this->get_redirect_url_for_user($user);
            wp_redirect($redirect_url);
            exit;
        }

        $this->render_login_with_xintra();
    }

    private function render_login_with_xintra() {
        $build_dir = PUZZLINGCRM_PLUGIN_DIR . 'assets/dashboard-build/';
        $build_url = PUZZLINGCRM_PLUGIN_URL . 'assets/dashboard-build/';
        $assets_url = PUZZLINGCRM_PLUGIN_URL . 'assets/';
        $version = defined('PUZZLINGCRM_VERSION') ? PUZZLINGCRM_VERSION : '1.0.0';
        $login_js_mtime = file_exists($build_dir . 'login.js') ? filemtime($build_dir . 'login.js') : $version;
        $login_css_mtime = file_exists($build_dir . 'dashboard-tabs.css') ? filemtime($build_dir . 'dashboard-tabs.css') : $version;

        if (class_exists('PuzzlingCRM_White_Label')) {
            $logo_url = PuzzlingCRM_White_Label::get_login_logo();
        } else {
            $custom_logo_id = get_theme_mod('custom_logo');
            $logo_url = '';
            if ($custom_logo_id) {
                $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                $logo_url = $logo ? $logo[0] : '';
            }
        }

        $cookie_lang = isset($_COOKIE['pzl_language']) ? sanitize_text_field(wp_unslash($_COOKIE['pzl_language'])) : '';
        $locale = get_locale();
        if ($cookie_lang === 'en') {
            $locale = 'en_US';
        } elseif ($cookie_lang === 'fa') {
            $locale = 'fa_IR';
        }
        $is_rtl = ($locale === 'fa_IR');
        $direction = $is_rtl ? 'rtl' : 'ltr';
        $lang = substr($locale, 0, 2);
        $site_name = get_bloginfo('name');
        if (class_exists('PuzzlingCRM_White_Label')) {
            $site_name = PuzzlingCRM_White_Label::get_company_name();
        }

        $favicon_url = $assets_url . 'images/brand-logos/favicon.ico';
        if (class_exists('PuzzlingCRM_White_Label')) {
            $favicon_url = PuzzlingCRM_White_Label::get_favicon();
        }

        $login_config = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('puzzlingcrm-ajax-nonce'),
            'logoUrl' => $logo_url ?: '',
            'siteName' => $site_name,
            'dir' => $direction,
            'lang' => $lang,
            'lostPasswordUrl' => wp_lostpassword_url(),
            'registerUrl' => wp_registration_url(),
        ];
        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr($lang); ?>" dir="<?php echo esc_attr($direction); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="Description" content="ورود به سیستم <?php echo esc_attr(get_bloginfo('name')); ?>">
    <title>ورود - <?php echo esc_html($site_name); ?></title>
    <link rel="icon" href="<?php echo esc_url($favicon_url); ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo esc_url($assets_url); ?>css/fonts.css?v=<?php echo esc_attr($version); ?>">
    <link rel="stylesheet" href="<?php echo esc_url($build_url); ?>dashboard-tabs.css?v=<?php echo esc_attr($login_css_mtime); ?>">
</head>
<body>
    <div id="pzl-login-root"></div>
    <script>
        window.PuzzlingLoginConfig = <?php echo wp_json_encode($login_config); ?>;
    </script>
    <script type="module" src="<?php echo esc_url($build_url); ?>login.js?v=<?php echo esc_attr($login_js_mtime); ?>"></script>
</body>
</html>
        <?php
    }

    public function render_login_page($atts = []) {
        ob_start();
        $this->render_login_with_xintra();
        return ob_get_clean();
    }

    private function get_redirect_url_for_user($user) {
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        }
        
        if (in_array('system_manager', $user->roles) || in_array('team_member', $user->roles)) {
            return home_url('/dashboard');
        }
        
        if (in_array('client', $user->roles) || in_array('customer', $user->roles)) {
            return home_url('/dashboard');
        }

        return home_url('/dashboard');
    }

    public static function activate() {
        $instance = new self();
        $instance->add_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
