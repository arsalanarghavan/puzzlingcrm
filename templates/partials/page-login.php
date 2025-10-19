<?php
/**
 * Login Page Template
 * Displays login form with SMS OTP and password options
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Redirect if already logged in
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    if (in_array('administrator', $user->roles)) {
        wp_redirect(admin_url());
    } elseif (in_array('system_manager', $user->roles) || in_array('team_member', $user->roles)) {
        wp_redirect(admin_url('admin.php?page=puzzling-dashboard'));
    } elseif (in_array('client', $user->roles)) {
        wp_redirect(home_url('/dashboard'));
    } else {
        wp_redirect(home_url());
    }
    exit;
}
?>

<div class="puzzling-login-wrapper">
    <div class="puzzling-login-container">
        <div class="puzzling-login-header">
            <?php
            $custom_logo_id = get_theme_mod('custom_logo');
            if ($custom_logo_id) {
                $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                echo '<img src="' . esc_url($logo[0]) . '" alt="' . get_bloginfo('name') . '" class="puzzling-login-logo">';
            } else {
                echo '<h1 class="puzzling-login-title">' . get_bloginfo('name') . '</h1>';
            }
            ?>
            <p class="puzzling-login-subtitle">به پنل مدیریت خوش آمدید</p>
        </div>

        <div class="puzzling-login-tabs">
            <button class="puzzling-tab-btn active" data-tab="sms">
                <i class="fas fa-mobile-alt"></i>
                ورود با پیامک
            </button>
            <button class="puzzling-tab-btn" data-tab="password">
                <i class="fas fa-lock"></i>
                ورود با رمز عبور
            </button>
        </div>

        <!-- SMS Login Form -->
        <div class="puzzling-login-form-container" id="sms-login-form">
            <form id="puzzling-otp-form" class="puzzling-login-form">
                <div class="puzzling-form-step active" data-step="1">
                    <div class="puzzling-form-group">
                        <label for="phone_number">
                            <i class="fas fa-phone"></i>
                            شماره موبایل
                        </label>
                        <input 
                            type="tel" 
                            id="phone_number" 
                            name="phone_number" 
                            class="puzzling-input" 
                            placeholder="09123456789"
                            pattern="09[0-9]{9}"
                            required
                            autocomplete="tel"
                        >
                        <small class="puzzling-help-text">شماره موبایل ثبت‌شده در سیستم را وارد کنید</small>
                    </div>

                    <button type="button" id="puzzling-send-otp-btn" class="puzzling-btn puzzling-btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        دریافت کد تایید
                    </button>
                </div>

                <div class="puzzling-form-step" data-step="2">
                    <div class="puzzling-form-group">
                        <label for="otp_code">
                            <i class="fas fa-key"></i>
                            کد تایید
                        </label>
                        <input 
                            type="text" 
                            id="otp_code" 
                            name="otp_code" 
                            class="puzzling-input puzzling-otp-input" 
                            placeholder="- - - - - -"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            autocomplete="one-time-code"
                        >
                        <small class="puzzling-help-text">
                            کد 6 رقمی ارسال شده به شماره موبایل را وارد کنید
                        </small>
                    </div>

                    <div class="puzzling-otp-timer">
                        <span id="puzzling-timer-text">زمان باقیمانده: <strong id="puzzling-timer">5:00</strong></span>
                    </div>

                    <button type="submit" id="puzzling-verify-otp-btn" class="puzzling-btn puzzling-btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        ورود
                    </button>

                    <button type="button" id="puzzling-resend-otp-btn" class="puzzling-btn puzzling-btn-link">
                        <i class="fas fa-redo"></i>
                        ارسال مجدد کد
                    </button>

                    <button type="button" id="puzzling-change-phone-btn" class="puzzling-btn puzzling-btn-link">
                        <i class="fas fa-edit"></i>
                        تغییر شماره موبایل
                    </button>
                </div>
            </form>
        </div>

        <!-- Password Login Form -->
        <div class="puzzling-login-form-container" id="password-login-form" style="display: none;">
            <form id="puzzling-password-form" class="puzzling-login-form">
                <div class="puzzling-form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        نام کاربری یا ایمیل
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="puzzling-input" 
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="puzzling-form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        رمز عبور
                    </label>
                    <div class="puzzling-password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="puzzling-input" 
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="puzzling-toggle-password" data-target="password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="puzzling-form-group puzzling-checkbox-group">
                    <label class="puzzling-checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span>مرا به خاطر بسپار</span>
                    </label>
                </div>

                <button type="submit" class="puzzling-btn puzzling-btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    ورود
                </button>

                <div class="puzzling-form-links">
                    <a href="<?php echo wp_lostpassword_url(); ?>" class="puzzling-link">
                        <i class="fas fa-question-circle"></i>
                        رمز عبور خود را فراموش کرده‌اید؟
                    </a>
                </div>
            </form>
        </div>

        <div class="puzzling-login-footer">
            <p>حساب کاربری ندارید؟ <a href="<?php echo wp_registration_url(); ?>" class="puzzling-link">ثبت‌نام کنید</a></p>
        </div>
    </div>
</div>

