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
        $assets_url = PUZZLINGCRM_PLUGIN_URL . 'assets/';
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = '';
        if ($custom_logo_id) {
            $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
            $logo_url = $logo[0];
        }
        ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">

    <head>

        <!-- Meta Data -->
        <meta charset="UTF-8">
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="Description" content="ورود به سیستم <?php bloginfo('name'); ?>">
        
		<!-- Title -->
        <title>ورود - <?php bloginfo('name'); ?></title>

        <!-- Favicon -->
        <link rel="icon" href="<?php echo $assets_url; ?>images/brand-logos/favicon.ico" type="image/x-icon">

        <!-- Start::custom-styles -->
            
        <!-- Main Theme Js -->
        <script src="<?php echo $assets_url; ?>js/authentication-main.js"></script>

        <!-- Bootstrap Css -->
        <link id="style" href="<?php echo $assets_url; ?>libs/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">

        <!-- Fonts (قبل از همه) -->
        <link href="<?php echo $assets_url; ?>css/fonts.css" rel="stylesheet">

        <!-- Style Css -->
        <link href="<?php echo $assets_url; ?>css/styles.css" rel="stylesheet">

        <!-- Icons Css -->
        <link href="<?php echo $assets_url; ?>css/icons.css" rel="stylesheet">
        
        <!-- RTL Complete Fix -->
        <link href="<?php echo $assets_url; ?>css/rtl-complete-fix.css" rel="stylesheet">
        
        <!-- SweetAlert2 -->
        <link rel="stylesheet" href="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/libs/sweetalert2/sweetalert2.min.css">
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
        
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        
        <!-- PuzzlingCRM AJAX Config -->
        <script>
        var puzzlingcrm_ajax_obj = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('puzzlingcrm-ajax-nonce'); ?>'
        };
        </script>
        
        <!-- PuzzlingCRM Login Scripts -->
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/login-page.js"></script>
        
        <!-- End::custom-styles -->

    </head>

	<body class="bg-white">

        <div class="row authentication authentication-cover-main mx-0">
            <div class="col-xxl-6 col-xl-7">
                <div class="row justify-content-center align-items-center h-100">
                    <div class="col-xxl-7 col-xl-9 col-lg-6 col-md-6 col-sm-8 col-12">
                        <div class="card custom-card my-auto border">
                            <div class="card-body p-5">
                                <?php if ($logo_url): ?>
                                <div class="text-center mb-4">
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" style="max-width: 150px; height: auto;">
                                </div>
                                <?php endif; ?>
                                
                                <p class="h5 mb-2 text-center">ورود / ثبت‌نام</p>
                                <p class="mb-4 text-muted op-7 fw-normal text-center">به پنل مدیریت خوش آمدید</p>
                                
                                <!-- Nav Tabs -->
                                <ul class="nav nav-tabs nav-justified mb-4" id="loginTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms-content" type="button" role="tab" aria-controls="sms-content" aria-selected="true">
                                            <i class="ri-smartphone-line me-1"></i> ورود با پیامک
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-content" type="button" role="tab" aria-controls="password-content" aria-selected="false">
                                            <i class="ri-lock-password-line me-1"></i> ورود با رمز عبور
                                        </button>
                                    </li>
                                </ul>
                                
                                <!-- Tab Content -->
                                <div class="tab-content" id="loginTabContent">
                                    
                                    <!-- SMS Login -->
                                    <div class="tab-pane fade show active" id="sms-content" role="tabpanel" aria-labelledby="sms-tab" tabindex="0">
                                        <form id="puzzling-otp-form">
                                            
                                            <!-- Step 1: Phone Number -->
                                            <div id="step-phone" class="otp-step">
                                                <div class="col-xl-12 mb-3">
                                                    <label for="phone_number" class="form-label text-default">شماره موبایل</label>
                                                    <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="09123456789" pattern="09[0-9]{9}" required>
                                                    <small class="form-text text-muted">شماره موبایل خود را برای ورود یا ثبت‌نام وارد کنید</small>
                                                </div>
                                                
                                                <div class="d-grid gap-2">
                                                    <button type="button" id="puzzling-send-otp-btn" class="btn btn-primary">
                                                        <i class="ri-send-plane-line me-1"></i> دریافت کد تایید
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <!-- Step 2: OTP Code -->
                                            <div id="step-otp" class="otp-step" style="display: none;">
                                                <div class="alert alert-success text-center mb-3" role="alert">
                                                    <i class="ri-check-line me-1"></i>
                                                    کد تایید به شماره موبایل شما ارسال شد
                                                </div>
                                                
                                                <div class="col-xl-12 mb-3">
                                                    <label for="otp_code" class="form-label text-default">کد تایید</label>
                                                    <input type="text" class="form-control text-center fs-4 letter-spacing-2" id="otp_code" name="otp_code" placeholder="- - - - - -" maxlength="8" pattern="[0-9]{4,8}" autocomplete="one-time-code">
                                                    <small class="form-text text-muted">کد ارسال شده را وارد کنید (کد به صورت خودکار تایید می‌شود)</small>
                                                </div>
                                                
                                                <div class="alert alert-info text-center mb-3" role="alert">
                                                    <i class="ri-timer-line me-1"></i>
                                                    زمان باقیمانده: <strong id="puzzling-timer">5:00</strong>
                                                </div>
                                                
                                                <div class="d-grid gap-2">
                                                    <button type="submit" id="puzzling-verify-otp-btn" class="btn btn-primary">
                                                        <i class="ri-login-box-line me-1"></i> تایید کد
                                                    </button>
                                                    <button type="button" id="puzzling-resend-otp-btn" class="btn btn-outline-secondary" style="display: none;">
                                                        <i class="ri-refresh-line me-1"></i> ارسال مجدد کد
                                                    </button>
                                                    <button type="button" id="puzzling-change-phone-btn" class="btn btn-link">
                                                        <i class="ri-arrow-right-line me-1"></i> تغییر شماره موبایل
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <!-- Step 3: Set Password (for new users) -->
                                            <div id="step-password" class="otp-step" style="display: none;">
                                                <div class="alert alert-success text-center mb-3" role="alert">
                                                    <i class="ri-check-line me-1"></i>
                                                    کد تایید صحیح است. لطفاً رمز عبور خود را تنظیم کنید.
                                                </div>
                                                
                                                <div class="col-xl-12 mb-3">
                                                    <label for="new_password" class="form-label text-default">رمز عبور جدید</label>
                                                    <div class="position-relative">
                                                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="رمز عبور">
                                                        <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('new_password',this)" id="button-addon3"><i class="ri-eye-off-line align-middle"></i></a>
                                                    </div>
                                                    <small class="form-text text-muted">حداقل 6 کاراکتر</small>
                                                </div>
                                                
                                                <div class="col-xl-12 mb-3">
                                                    <label for="confirm_password" class="form-label text-default">تکرار رمز عبور</label>
                                                    <div class="position-relative">
                                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="تکرار رمز عبور">
                                                        <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('confirm_password',this)" id="button-addon4"><i class="ri-eye-off-line align-middle"></i></a>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-grid gap-2">
                                                    <button type="button" id="puzzling-set-password-btn" class="btn btn-primary">
                                                        <i class="ri-save-line me-1"></i> تنظیم رمز عبور و ورود
                                                    </button>
                                                    <button type="button" id="puzzling-back-to-otp-btn" class="btn btn-outline-secondary">
                                                        <i class="ri-arrow-right-line me-1"></i> بازگشت به کد تایید
                                                    </button>
                                                </div>
                                            </div>
                                            
                                        </form>
                                    </div>
                                    
                                    <!-- Password Login -->
                                    <div class="tab-pane fade" id="password-content" role="tabpanel" aria-labelledby="password-tab" tabindex="0">
                                        <form id="puzzling-password-form">
                                            <div class="col-xl-12 mb-3">
                                                <label for="username" class="form-label text-default">نام کاربری یا ایمیل</label>
                                                <input type="text" class="form-control" id="username" name="username" placeholder="نام کاربری" required autocomplete="username">
                                            </div>
                                            
                                            <div class="col-xl-12 mb-2">
                                                <label for="password" class="form-label text-default d-block">
                                                    رمز عبور
                                                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="float-end fw-normal text-muted fs-12">فراموشی رمز عبور؟</a>
                                                </label>
                                                <div class="position-relative">
                                                    <input type="password" class="form-control create-password-input" id="password" name="password" placeholder="رمز عبور" required autocomplete="current-password">
                                                    <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('password',this)" id="button-addon2"><i class="ri-eye-off-line align-middle"></i></a>
                                                </div>
                                                <div class="mt-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                                                        <label class="form-check-label text-muted fw-normal" for="remember">
                                                            مرا به خاطر بسپار
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="d-grid mt-4">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-login-box-line me-1"></i> ورود
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                </div>
                                
                                <!-- ورود و ثبت‌نام یکپارچه هستند - نیازی به دکمه جداگانه نیست -->
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 col-xl-5 col-lg-12 d-xl-block d-none px-0">
                <div class="authentication-cover overflow-hidden">
                    <div class="authentication-cover-logo">
                        <a href="<?php echo esc_url(home_url()); ?>">
                            <?php if ($logo_url): ?>
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="authentication-brand desktop-white" style="max-width: 200px; height: auto;">
                            <?php else: ?>
                                <img src="<?php echo $assets_url; ?>images/brand-logos/desktop-white.png" alt="logo" class="authentication-brand desktop-white">
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="aunthentication-cover-content d-flex align-items-center justify-content-center">
                        <div class="text-center px-5">
                            <h3 class="text-fixed-white mb-3 fw-semibold">خوش آمدید!</h3>
                            <h6 class="text-fixed-white mb-3 fw-normal">ورود به <?php echo esc_html(get_bloginfo('name')); ?></h6>
                            <p class="text-fixed-white mb-0 op-7 fw-normal">
                                به سیستم مدیریت خوش آمدید. لطفاً برای دسترسی به پنل مدیریتی و نظارت بر فعالیت‌ها وارد شوید.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Start::custom-scripts -->
        
        <!-- Bootstrap JS -->
        <script src="<?php echo $assets_url; ?>libs/bootstrap/js/bootstrap.bundle.min.js"></script>
        <!-- End::custom-scripts -->

        	
        <!-- Show Password JS -->
        <script src="<?php echo $assets_url; ?>js/show-password.js"></script>


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
