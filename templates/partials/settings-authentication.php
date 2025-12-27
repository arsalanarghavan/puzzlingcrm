<?php
/**
 * تنظیمات احراز هویت و ورود/خروج
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

// Get current settings
$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
$login_phone_pattern = $settings['login_phone_pattern'] ?? '^09[0-9]{9}$';
$login_phone_length = intval($settings['login_phone_length'] ?? 11);
$login_sms_template = $settings['login_sms_template'] ?? 'کد ورود شما: %CODE%\nاعتبار: 5 دقیقه';
$otp_expiry_minutes = intval($settings['otp_expiry_minutes'] ?? 5);
$otp_max_attempts = intval($settings['otp_max_attempts'] ?? 3);
$otp_length = intval($settings['otp_length'] ?? 6);
$enable_password_login = isset($settings['enable_password_login']) ? $settings['enable_password_login'] : 1;
$enable_sms_login = isset($settings['enable_sms_login']) ? $settings['enable_sms_login'] : 1;
$login_redirect_url = $settings['login_redirect_url'] ?? '';
$logout_redirect_url = $settings['logout_redirect_url'] ?? '';
$force_logout_inactive = isset($settings['force_logout_inactive']) ? $settings['force_logout_inactive'] : 0;
$inactive_timeout_minutes = intval($settings['inactive_timeout_minutes'] ?? 30);

// SMS Pattern settings (moved from SMS tab)
$melipayamak_login_pattern = $settings['melipayamak_login_pattern'] ?? '';
$parsgreen_login_template = $settings['parsgreen_login_template'] ?? '';
?>

<h3><i class="ri-shield-keyhole-line"></i> تنظیمات احراز هویت</h3>

<form method="post" action="" class="pzl-ajax-form" id="pzl-authentication-settings-form" data-action="save_auth_settings">
    <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>

    <!-- بخش 1: تنظیمات شماره موبایل -->
    <div class="pzl-settings-section">
        <h4><i class="ri-smartphone-line"></i> تنظیمات شماره موبایل</h4>
        <div class="pzl-info-box">
            <i class="ri-information-line"></i>
            <p>تنظیمات مربوط به validation و فرمت شماره موبایل برای ورود کاربران</p>
        </div>
        
        <div class="pzl-form-row">
            <div class="form-group">
                <label for="login_phone_pattern">
                    <strong>پترن Regex شماره موبایل</strong>
                    <span class="pzl-help-text">
                        <i class="ri-information-line"></i>
                        <span class="pzl-tooltip">
                            این پترن برای validation شماره موبایل استفاده می‌شود.<br>
                            مثال: <code>^09[0-9]{9}$</code> برای شماره‌های ایرانی
                        </span>
                    </span>
                </label>
                <input 
                    type="text" 
                    id="login_phone_pattern" 
                    name="login_phone_pattern" 
                    value="<?php echo esc_attr($login_phone_pattern); ?>" 
                    class="ltr-input"
                    placeholder="^09[0-9]{9}$">
                <small class="form-text text-muted">
                    پترن Regex برای validation شماره موبایل (بدون / در ابتدا و انتها)
                </small>
            </div>
        </div>

        <div class="pzl-form-row">
            <div class="form-group half-width">
                <label for="login_phone_length">
                    <strong>طول شماره موبایل (تعداد ارقام)</strong>
                </label>
                <input 
                    type="number" 
                    id="login_phone_length" 
                    name="login_phone_length" 
                    value="<?php echo esc_attr($login_phone_length); ?>" 
                    min="10" 
                    max="15"
                    placeholder="11">
                <small class="form-text text-muted">تعداد ارقام مجاز برای شماره موبایل</small>
            </div>

            <div class="form-group half-width">
                <label for="otp_expiry_minutes">
                    <strong>زمان انقضای کد OTP (دقیقه)</strong>
                </label>
                <input 
                    type="number" 
                    id="otp_expiry_minutes" 
                    name="otp_expiry_minutes" 
                    value="<?php echo esc_attr($otp_expiry_minutes); ?>" 
                    min="1" 
                    max="30"
                    placeholder="5">
                <small class="form-text text-muted">کد OTP پس از این مدت منقضی می‌شود</small>
            </div>
        </div>

        <div class="pzl-form-row">
            <div class="form-group half-width">
                <label for="otp_length">
                    <strong>طول کد OTP (تعداد ارقام)</strong>
                </label>
                <select id="otp_length" name="otp_length">
                    <option value="4" <?php selected($otp_length, 4); ?>>4 رقمی</option>
                    <option value="5" <?php selected($otp_length, 5); ?>>5 رقمی</option>
                    <option value="6" <?php selected($otp_length, 6); ?>>6 رقمی (پیشنهادی)</option>
                    <option value="8" <?php selected($otp_length, 8); ?>>8 رقمی</option>
                </select>
                <small class="form-text text-muted">تعداد ارقام کد یکبار مصرف</small>
            </div>
        </div>

        <div class="pzl-form-row">
            <div class="form-group">
                <label for="otp_max_attempts">
                    <strong>حداکثر تعداد تلاش برای وارد کردن کد OTP</strong>
                </label>
                <input 
                    type="number" 
                    id="otp_max_attempts" 
                    name="otp_max_attempts" 
                    value="<?php echo esc_attr($otp_max_attempts); ?>" 
                    min="1" 
                    max="10"
                    placeholder="3">
                <small class="form-text text-muted">پس از این تعداد تلاش ناموفق، کاربر باید کد جدید درخواست کند</small>
            </div>
        </div>
    </div>

    <!-- بخش 2: قالب پیامک ورود -->
    <div class="pzl-settings-section">
        <h4><i class="ri-message-3-line"></i> قالب پیامک ورود</h4>
        <div class="pzl-info-box">
            <i class="ri-information-line"></i>
            <p>تنظیمات مربوط به متن پیامک‌های ارسالی برای کد یکبار مصرف ورود</p>
        </div>
        
        <div class="pzl-form-row">
            <div class="form-group">
                <label for="login_sms_template">
                    <strong>متن پیامک OTP</strong>
                </label>
                <textarea 
                    id="login_sms_template" 
                    name="login_sms_template" 
                    rows="4" 
                    placeholder="کد ورود شما: %CODE%"><?php echo esc_textarea($login_sms_template); ?></textarea>
                <small class="form-text text-muted">
                    از <code>%CODE%</code> برای نمایش کد OTP استفاده کنید.<br>
                    مثال: <code>کد ورود شما: %CODE%</code>
                </small>
            </div>
        </div>
    </div>

    <!-- بخش 2.5: تنظیمات پترن پیامک -->
    <div class="pzl-settings-section">
        <h4><i class="ri-settings-3-line"></i> تنظیمات پترن پیامک</h4>
        <div class="pzl-info-box">
            <i class="ri-information-line"></i>
            <p>تنظیمات مربوط به کدهای الگوی سرویس‌های پیامک برای ارسال پیامک‌های ورود</p>
        </div>
        
        <div class="pzl-form-row">
            <div class="form-group">
                <label for="melipayamak_login_pattern">
                    <strong>کد پترن ملی‌پیامک</strong>
                    <span class="pzl-help-text">
                        <i class="ri-information-line"></i>
                        <span class="pzl-tooltip">
                            کد الگوی ساخته شده در پنل ملی‌پیامک برای ارسال پیامک ورود.<br>
                            متغیر مورد استفاده: <code>%amount%</code> (کد OTP)
                        </span>
                    </span>
                </label>
                <input 
                    type="text" 
                    id="melipayamak_login_pattern" 
                    name="melipayamak_login_pattern" 
                    value="<?php echo esc_attr($melipayamak_login_pattern); ?>" 
                    class="ltr-input"
                    placeholder="مثال: 12345">
                <small class="form-text text-muted">
                    در صورت خالی بودن، پیامک ساده ارسال می‌شود
                </small>
            </div>
        </div>

        <div class="pzl-form-row">
            <div class="form-group">
                <label for="parsgreen_login_template">
                    <strong>قالب پیامک پارس گرین</strong>
                </label>
                <textarea 
                    id="parsgreen_login_template" 
                    name="parsgreen_login_template" 
                    rows="3" 
                    placeholder="کد ورود شما: %CODE%"><?php echo esc_textarea($parsgreen_login_template); ?></textarea>
                <small class="form-text text-muted">
                    از <code>%CODE%</code> برای نمایش کد OTP استفاده کنید
                </small>
            </div>
        </div>
    </div>

    <!-- بخش 3: روش‌های ورود -->
    <div class="pzl-settings-section">
        <h4><i class="ri-login-box-line"></i> روش‌های ورود مجاز</h4>
        
        <div class="pzl-form-row">
            <div class="form-group">
                <label class="pzl-checkbox-label">
                    <input 
                        type="checkbox" 
                        name="enable_sms_login" 
                        value="1" 
                        <?php checked($enable_sms_login, 1); ?>>
                    <span>فعال‌سازی ورود با پیامک (OTP)</span>
                </label>
            </div>
        </div>

        <div class="pzl-form-row">
            <div class="form-group">
                <label class="pzl-checkbox-label">
                    <input 
                        type="checkbox" 
                        name="enable_password_login" 
                        value="1" 
                        <?php checked($enable_password_login, 1); ?>>
                    <span>فعال‌سازی ورود با نام کاربری و رمز عبور</span>
                </label>
            </div>
        </div>
    </div>

    <!-- بخش 4: تنظیمات Redirect -->
    <div class="pzl-settings-section">
        <h4><i class="ri-arrow-go-forward-line"></i> تنظیمات هدایت (Redirect)</h4>
        
        <div class="pzl-form-row">
            <div class="form-group">
                <label for="login_redirect_url">
                    <strong>URL هدایت پس از ورود</strong>
                </label>
                <input 
                    type="url" 
                    id="login_redirect_url" 
                    name="login_redirect_url" 
                    value="<?php echo esc_url($login_redirect_url); ?>" 
                    class="ltr-input"
                    placeholder="<?php echo home_url('/dashboard'); ?>">
                <small class="form-text text-muted">
                    خالی بگذارید تا به طور خودکار بر اساس نقش کاربر هدایت شود
                </small>
            </div>
        </div>

        <div class="pzl-form-row">
            <div class="form-group">
                <label for="logout_redirect_url">
                    <strong>URL هدایت پس از خروج</strong>
                </label>
                <input 
                    type="url" 
                    id="logout_redirect_url" 
                    name="logout_redirect_url" 
                    value="<?php echo esc_url($logout_redirect_url); ?>" 
                    class="ltr-input"
                    placeholder="<?php echo home_url('/login'); ?>">
                <small class="form-text text-muted">
                    خالی بگذارید تا به صفحه ورود هدایت شود
                </small>
            </div>
        </div>
    </div>

    <!-- بخش 5: امنیت و نشست -->
    <div class="pzl-settings-section">
        <h4><i class="ri-lock-password-line"></i> امنیت و مدیریت نشست</h4>
        
        <div class="pzl-form-row">
            <div class="form-group">
                <label class="pzl-checkbox-label">
                    <input 
                        type="checkbox" 
                        name="force_logout_inactive" 
                        value="1" 
                        <?php checked($force_logout_inactive, 1); ?>>
                    <span>خروج خودکار کاربران غیرفعال</span>
                </label>
                <small class="form-text text-muted">
                    در صورت فعال بودن، کاربران بعد از مدت زمان مشخص بدون فعالیت، خودکار خارج می‌شوند
                </small>
            </div>
        </div>

        <div class="pzl-form-row">
            <div class="form-group half-width">
                <label for="inactive_timeout_minutes">
                    <strong>مدت زمان timeout (دقیقه)</strong>
                </label>
                <input 
                    type="number" 
                    id="inactive_timeout_minutes" 
                    name="inactive_timeout_minutes" 
                    value="<?php echo esc_attr($inactive_timeout_minutes); ?>" 
                    min="5" 
                    max="1440"
                    placeholder="30">
                <small class="form-text text-muted">
                    کاربر پس از این مدت بدون فعالیت، خارج می‌شود
                </small>
            </div>
        </div>
    </div>

    <!-- دکمه ذخیره -->
    <div class="pzl-form-actions">
        <button type="submit" class="btn btn-primary" data-puzzling-skip-global-handler="true">
            <i class="ri-save-line"></i> ذخیره تنظیمات
        </button>
    </div>
</form>

<style>
.pzl-settings-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.pzl-settings-section h4 {
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #4CAF50;
    padding-bottom: 10px;
}

.pzl-help-text {
    position: relative;
    display: inline-block;
    margin-right: 5px;
    color: #666;
    cursor: help;
}

.pzl-tooltip {
    visibility: hidden;
    width: 300px;
    background-color: #333;
    color: #fff;
    text-align: center;
    border-radius: 6px;
    padding: 10px;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    margin-left: -150px;
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 12px;
    line-height: 1.6;
}

.pzl-help-text:hover .pzl-tooltip {
    visibility: visible;
    opacity: 1;
}

.pzl-tooltip code {
    background: rgba(255,255,255,0.2);
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

.pzl-checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.pzl-checkbox-label input[type="checkbox"] {
    margin-left: 10px;
    width: 20px;
    height: 20px;
}

.pzl-checkbox-label span {
    font-size: 15px;
}

.ltr-input {
    direction: ltr;
    text-align: left;
}

.pzl-info-box {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.pzl-info-box i {
    color: #2196f3;
    font-size: 18px;
    flex-shrink: 0;
}

.pzl-info-box p {
    margin: 0;
    color: #1976d2;
    font-size: 14px;
    line-height: 1.4;
}
</style>

<script>
jQuery(document).ready(function($) {
    // AJAX Form Handler
    $('#pzl-authentication-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var formData = new FormData(this);
        formData.append('action', 'save_auth_settings');
        
        $btn.prop('disabled', true).html('<i class="ri-loader-4-line"></i> در حال ذخیره...');
        
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'موفق',
                        text: response.data.message || 'تنظیمات با موفقیت ذخیره شد',
                        confirmButtonText: puzzlingcrm_ajax_obj.lang.ok_button
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: puzzlingcrm_ajax_obj.lang.error_title,
                        text: response.data.message || 'خطا در ذخیره تنظیمات',
                        confirmButtonText: puzzlingcrm_ajax_obj.lang.ok_button
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: puzzlingcrm_ajax_obj.lang.error_title,
                    text: puzzlingcrm_ajax_obj.lang.server_error,
                    confirmButtonText: puzzlingcrm_ajax_obj.lang.ok_button
                });
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="ri-save-line"></i> ذخیره تنظیمات');
            }
        });
    });
});
</script>

