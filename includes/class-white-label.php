<?php
/**
 * White Labeling System
 * 
 * Allows complete branding customization of the CRM
 *
 * @package    PuzzlingCRM
 * @subpackage PuzzlingCRM/includes
 */

class PuzzlingCRM_White_Label {

    /**
     * Initialize White Label Handler
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page'], 100);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_custom_branding']);
        add_action('login_enqueue_scripts', [$this, 'customize_login_page']);
        add_filter('admin_footer_text', [$this, 'custom_admin_footer']);
        add_filter('update_footer', [$this, 'custom_version_footer'], 11);
        add_action('wp_head', [$this, 'add_custom_css']);
        add_action('admin_head', [$this, 'add_custom_admin_css']);
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'puzzling-crm',
            'تنظیمات White Label',
            'White Label',
            'manage_options',
            'puzzling-white-label',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Branding
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_company_name');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_company_logo');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_company_icon');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_login_logo');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_primary_color');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_secondary_color');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_accent_color');
        
        // Text Customization
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_dashboard_title');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_welcome_message');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_footer_text');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_support_email');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_support_phone');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_support_url');
        
        // Advanced
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_custom_css');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_custom_js');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_hide_branding');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_custom_menu_icon');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_email_logo');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_email_header_color');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_email_footer_text');
        
        // Domain/URL Customization
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_custom_domain');
        register_setting('puzzlingcrm_white_label', 'puzzlingcrm_wl_app_url_slug');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php" enctype="multipart/form-data">
                <?php settings_fields('puzzlingcrm_white_label'); ?>
                
                <div class="puzzling-white-label-settings">
                    
                    <!-- Branding Tab -->
                    <div class="postbox">
                        <div class="inside">
                            <h2>برندینگ و لوگو</h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th>نام شرکت</th>
                                    <td>
                                        <input type="text" name="puzzlingcrm_wl_company_name" 
                                               value="<?php echo esc_attr(get_option('puzzlingcrm_wl_company_name', 'PuzzlingCRM')); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>لوگوی داشبورد</th>
                                    <td>
                                        <input type="text" name="puzzlingcrm_wl_company_logo" 
                                               id="company_logo" 
                                               value="<?php echo esc_url(get_option('puzzlingcrm_wl_company_logo')); ?>" 
                                               class="regular-text">
                                        <button type="button" class="button puzzling-upload-btn" data-target="company_logo">
                                            آپلود لوگو
                                        </button>
                                        <?php if (get_option('puzzlingcrm_wl_company_logo')): ?>
                                            <br><img src="<?php echo esc_url(get_option('puzzlingcrm_wl_company_logo')); ?>" 
                                                     style="max-width: 200px; margin-top: 10px;">
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>آیکون (Favicon)</th>
                                    <td>
                                        <input type="text" name="puzzlingcrm_wl_company_icon" 
                                               id="company_icon" 
                                               value="<?php echo esc_url(get_option('puzzlingcrm_wl_company_icon')); ?>" 
                                               class="regular-text">
                                        <button type="button" class="button puzzling-upload-btn" data-target="company_icon">
                                            آپلود آیکون
                                        </button>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>لوگوی صفحه ورود</th>
                                    <td>
                                        <input type="text" name="puzzlingcrm_wl_login_logo" 
                                               id="login_logo" 
                                               value="<?php echo esc_url(get_option('puzzlingcrm_wl_login_logo')); ?>" 
                                               class="regular-text">
                                        <button type="button" class="button puzzling-upload-btn" data-target="login_logo">
                                            آپلود لوگو
                                        </button>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>رنگ اصلی</th>
                                    <td>
                                        <input type="color" name="puzzlingcrm_wl_primary_color" 
                                               value="<?php echo esc_attr(get_option('puzzlingcrm_wl_primary_color', '#4CAF50')); ?>">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>رنگ ثانویه</th>
                                    <td>
                                        <input type="color" name="puzzlingcrm_wl_secondary_color" 
                                               value="<?php echo esc_attr(get_option('puzzlingcrm_wl_secondary_color', '#2196F3')); ?>">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>رنگ تاکیدی</th>
                                    <td>
                                        <input type="color" name="puzzlingcrm_wl_accent_color" 
                                               value="<?php echo esc_attr(get_option('puzzlingcrm_wl_accent_color', '#FF5722')); ?>">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Text Customization -->
                    <div class="postbox">
                        <div class="inside">
                            <h2>سفارشی‌سازی متن</h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th>عنوان داشبورد</th>
                                    <td>
                                        <input type="text" name="puzzlingcrm_wl_dashboard_title" 
                                               value="<?php echo esc_attr(get_option('puzzlingcrm_wl_dashboard_title', 'داشبورد')); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>پیام خوش‌آمدگویی</th>
                                    <td>
                                        <textarea name="puzzlingcrm_wl_welcome_message" 
                                                  class="large-text" 
                                                  rows="3"><?php echo esc_textarea(get_option('puzzlingcrm_wl_welcome_message')); ?></textarea>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>متن فوتر</th>
                                    <td>
                                        <input type="text" name="puzzlingcrm_wl_footer_text" 
                                               value="<?php echo esc_attr(get_option('puzzlingcrm_wl_footer_text')); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>ایمیل پشتیبانی</th>
                                    <td>
                                        <input type="email" name="puzzlingcrm_wl_support_email" 
                                               value="<?php echo esc_attr(get_option('puzzlingcrm_wl_support_email')); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>تلفن پشتیبانی</th>
                                    <td>
                                        <input type="text" name="puzzlingcrm_wl_support_phone" 
                                               value="<?php echo esc_attr(get_option('puzzlingcrm_wl_support_phone')); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>URL پشتیبانی</th>
                                    <td>
                                        <input type="url" name="puzzlingcrm_wl_support_url" 
                                               value="<?php echo esc_url(get_option('puzzlingcrm_wl_support_url')); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Advanced Settings -->
                    <div class="postbox">
                        <div class="inside">
                            <h2>تنظیمات پیشرفته</h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th>CSS سفارشی</th>
                                    <td>
                                        <textarea name="puzzlingcrm_wl_custom_css" 
                                                  class="large-text code" 
                                                  rows="10"><?php echo esc_textarea(get_option('puzzlingcrm_wl_custom_css')); ?></textarea>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>JavaScript سفارشی</th>
                                    <td>
                                        <textarea name="puzzlingcrm_wl_custom_js" 
                                                  class="large-text code" 
                                                  rows="10"><?php echo esc_textarea(get_option('puzzlingcrm_wl_custom_js')); ?></textarea>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th>مخفی کردن برندینگ</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="puzzlingcrm_wl_hide_branding" 
                                                   value="1" <?php checked(get_option('puzzlingcrm_wl_hide_branding'), 1); ?>>
                                            مخفی کردن تمام نشانه‌های "PuzzlingCRM"
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                </div>
                
                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.puzzling-upload-btn').on('click', function(e) {
                e.preventDefault();
                var target = $(this).data('target');
                var mediaUploader = wp.media({
                    title: 'انتخاب تصویر',
                    button: { text: 'انتخاب' },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#' + target).val(attachment.url);
                });
                
                mediaUploader.open();
            });
        });
        </script>
        
        <style>
        .puzzling-white-label-settings .postbox {
            margin-bottom: 20px;
        }
        .puzzling-white-label-settings h2 {
            padding: 15px;
            background: #f5f5f5;
            margin: 0 0 20px 0;
            border-bottom: 1px solid #ddd;
        }
        </style>
        <?php
    }

    /**
     * Enqueue custom branding
     */
    public function enqueue_custom_branding() {
        if (strpos($_SERVER['REQUEST_URI'], 'puzzling-') === false) {
            return;
        }

        $primary_color = get_option('puzzlingcrm_wl_primary_color', '#4CAF50');
        $secondary_color = get_option('puzzlingcrm_wl_secondary_color', '#2196F3');
        $accent_color = get_option('puzzlingcrm_wl_accent_color', '#FF5722');

        $custom_css = "
            :root {
                --puzzling-primary: {$primary_color};
                --puzzling-secondary: {$secondary_color};
                --puzzling-accent: {$accent_color};
            }
            .puzzling-primary-bg { background-color: {$primary_color} !important; }
            .puzzling-primary-color { color: {$primary_color} !important; }
            .btn-primary { background-color: {$primary_color} !important; border-color: {$primary_color} !important; }
        ";

        wp_add_inline_style('puzzlingcrm-styles', $custom_css);

        // Custom JS
        $custom_js = get_option('puzzlingcrm_wl_custom_js');
        if ($custom_js) {
            wp_add_inline_script('puzzlingcrm-scripts', $custom_js);
        }
    }

    /**
     * Customize login page
     */
    public function customize_login_page() {
        $logo = get_option('puzzlingcrm_wl_login_logo');
        $primary_color = get_option('puzzlingcrm_wl_primary_color', '#4CAF50');
        
        ?>
        <style type="text/css">
            #login h1 a, .login h1 a {
                <?php if ($logo): ?>
                background-image: url(<?php echo esc_url($logo); ?>);
                <?php endif; ?>
                height: 80px;
                width: 320px;
                background-size: contain;
                background-repeat: no-repeat;
                padding-bottom: 30px;
            }
            .wp-core-ui .button-primary {
                background: <?php echo esc_attr($primary_color); ?>;
                border-color: <?php echo esc_attr($primary_color); ?>;
            }
            .wp-core-ui .button-primary:hover {
                background: <?php echo esc_attr($primary_color); ?>;
                filter: brightness(1.1);
            }
        </style>
        <?php
    }

    /**
     * Custom admin footer
     */
    public function custom_admin_footer($text) {
        $footer_text = get_option('puzzlingcrm_wl_footer_text');
        
        if ($footer_text && strpos($_SERVER['REQUEST_URI'], 'puzzling-') !== false) {
            return $footer_text;
        }
        
        return $text;
    }

    /**
     * Custom version footer
     */
    public function custom_version_footer($text) {
        if (get_option('puzzlingcrm_wl_hide_branding') && strpos($_SERVER['REQUEST_URI'], 'puzzling-') !== false) {
            return '';
        }
        
        return $text;
    }

    /**
     * Add custom CSS to frontend
     */
    public function add_custom_css() {
        $custom_css = get_option('puzzlingcrm_wl_custom_css');
        
        if ($custom_css) {
            echo '<style type="text/css">' . wp_strip_all_tags($custom_css) . '</style>';
        }
    }

    /**
     * Add custom CSS to admin
     */
    public function add_custom_admin_css() {
        if (strpos($_SERVER['REQUEST_URI'], 'puzzling-') === false) {
            return;
        }

        $custom_css = get_option('puzzlingcrm_wl_custom_css');
        $logo = get_option('puzzlingcrm_wl_company_logo');
        
        ?>
        <style type="text/css">
            <?php if ($logo): ?>
            .puzzlingcrm-logo {
                background-image: url(<?php echo esc_url($logo); ?>) !important;
            }
            <?php endif; ?>
            
            <?php echo wp_strip_all_tags($custom_css); ?>
        </style>
        <?php
    }

    /**
     * Get company name
     */
    public static function get_company_name() {
        return get_option('puzzlingcrm_wl_company_name', 'PuzzlingCRM');
    }

    /**
     * Get company logo
     */
    public static function get_company_logo() {
        return get_option('puzzlingcrm_wl_company_logo', PUZZLINGCRM_PLUGIN_URL . 'assets/images/logo.png');
    }

    /**
     * Get primary color
     */
    public static function get_primary_color() {
        return get_option('puzzlingcrm_wl_primary_color', '#4CAF50');
    }

    /**
     * Get support email
     */
    public static function get_support_email() {
        return get_option('puzzlingcrm_wl_support_email', get_option('admin_email'));
    }

    /**
     * Get support phone
     */
    public static function get_support_phone() {
        return get_option('puzzlingcrm_wl_support_phone', '');
    }
}

