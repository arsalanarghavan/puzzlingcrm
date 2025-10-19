<?php
/**
 * Style Settings - رنگ‌بندی، لوگو، فونت
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
?>

<div class="pzl-form-container">
    <h4><i class="ri-palette-line"></i> تنظیمات ظاهر و استایل</h4>
    
    <form id="puzzling-style-settings-form" method="post" class="pzl-form pzl-ajax-form" data-action="puzzling_save_settings">
        <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>

        <!-- رنگ‌بندی -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-paint-brush-line me-2"></i>
                    رنگ‌بندی سیستم
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="primary_color" class="form-label">رنگ اصلی (Primary)</label>
                            <div class="d-flex gap-2">
                                <input type="color" id="primary_color" name="puzzling_settings[primary_color]" value="<?php echo esc_attr($settings['primary_color'] ?? '#845adf'); ?>" class="form-control form-control-color">
                                <input type="text" value="<?php echo esc_attr($settings['primary_color'] ?? '#845adf'); ?>" class="form-control ltr-input" readonly>
                            </div>
                            <small class="form-text text-muted">رنگ اصلی برای دکمه‌ها، لینک‌ها و المان‌های مهم</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="secondary_color" class="form-label">رنگ ثانویه (Secondary)</label>
                            <div class="d-flex gap-2">
                                <input type="color" id="secondary_color" name="puzzling_settings[secondary_color]" value="<?php echo esc_attr($settings['secondary_color'] ?? '#6c757d'); ?>" class="form-control form-control-color">
                                <input type="text" value="<?php echo esc_attr($settings['secondary_color'] ?? '#6c757d'); ?>" class="form-control ltr-input" readonly>
                            </div>
                            <small class="form-text text-muted">رنگ ثانویه برای دکمه‌های غیرفعال و المان‌های کمتر مهم</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="success_color" class="form-label">رنگ موفقیت</label>
                            <input type="color" id="success_color" name="puzzling_settings[success_color]" value="<?php echo esc_attr($settings['success_color'] ?? '#28a745'); ?>" class="form-control form-control-color">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="warning_color" class="form-label">رنگ هشدار</label>
                            <input type="color" id="warning_color" name="puzzling_settings[warning_color]" value="<?php echo esc_attr($settings['warning_color'] ?? '#ffc107'); ?>" class="form-control form-control-color">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="danger_color" class="form-label">رنگ خطر</label>
                            <input type="color" id="danger_color" name="puzzling_settings[danger_color]" value="<?php echo esc_attr($settings['danger_color'] ?? '#dc3545'); ?>" class="form-control form-control-color">
                        </div>
                    </div>
                </div>

                <div class="alert alert-info d-flex align-items-center">
                    <i class="ri-information-line fs-5 me-3"></i>
                    <div>
                        <strong>نکته:</strong> پس از تغییر رنگ‌ها، ممکن است نیاز باشد صفحه را رفرش کنید (F5) تا تغییرات کاملاً اعمال شوند.
                    </div>
                </div>
            </div>
        </div>

        <!-- لوگوها -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-image-line me-2"></i>
                    مدیریت لوگوها
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-primary d-flex align-items-start">
                    <i class="ri-information-line fs-5 me-3 mt-1"></i>
                    <div>
                        <strong>راهنما:</strong>
                        <p class="mb-0 mt-2">برای تغییر لوگوی سیستم، از بخش <strong>ظاهر > سفارشی‌سازی > هویت سایت > لوگو</strong> در پنل WordPress استفاده کنید.</p>
                        <p class="mb-0 mt-2">لوگوی شما به صورت خودکار در تمام بخش‌های سیستم (Header، Sidebar، صفحه ورود) نمایش داده می‌شود.</p>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="<?php echo admin_url('customize.php'); ?>" class="btn btn-primary" target="_blank">
                        <i class="ri-settings-3-line me-1"></i>
                        رفتن به تنظیمات WordPress
                    </a>
                </div>
            </div>
        </div>

        <!-- فونت -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-font-size-2 me-2"></i>
                    تنظیمات فونت
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="font_family" class="form-label">فونت اصلی</label>
                    <select id="font_family" name="puzzling_settings[font_family]" class="form-select">
                        <option value="IRANSans" <?php selected($settings['font_family'] ?? 'IRANSans', 'IRANSans'); ?>>IRANSans (پیش‌فرض)</option>
                        <option value="Vazirmatn" <?php selected($settings['font_family'] ?? '', 'Vazirmatn'); ?>>Vazirmatn</option>
                        <option value="Yekan" <?php selected($settings['font_family'] ?? '', 'Yekan'); ?>>یکان</option>
                        <option value="Shabnam" <?php selected($settings['font_family'] ?? '', 'Shabnam'); ?>>شبنم</option>
                    </select>
                    <small class="form-text text-muted">فونت اصلی که در تمام سیستم استفاده می‌شود (فعلاً: IRANSans)</small>
                </div>
                
                <div class="mb-3">
                    <label for="base_font_size" class="form-label">اندازه پایه فونت</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="range" id="base_font_size" name="puzzling_settings[base_font_size]" min="12" max="18" value="<?php echo esc_attr($settings['base_font_size'] ?? '15'); ?>" class="form-range" style="flex: 1;">
                        <span id="font_size_value" class="badge bg-primary"><?php echo esc_html($settings['base_font_size'] ?? '15'); ?>px</span>
                    </div>
                    <small class="form-text text-muted">اندازه پایه متن در سیستم (پیشنهادی: 15px)</small>
                </div>

                <div class="alert alert-success d-flex align-items-start">
                    <i class="ri-check-line fs-5 me-3 mt-1"></i>
                    <div>
                        <strong>فونت IRANSans با 5 ضخامت مختلف:</strong>
                        <ul class="mb-0 mt-2">
                            <li><span style="font-weight: 100;">UltraLight (100)</span> - برای متن‌های خیلی سبک</li>
                            <li><span style="font-weight: 300;">Light (300)</span> - برای متن‌های سبک</li>
                            <li><span style="font-weight: 400;">Regular (400)</span> - برای متن‌های معمولی</li>
                            <li><span style="font-weight: 500;">Medium (500)</span> - برای عناوین کوچک</li>
                            <li><span style="font-weight: 700;">Bold (700)</span> - برای عناوین و تاکیدها</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- تم پیش‌فرض -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-contrast-2-line me-2"></i>
                    تنظیمات تم
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">تم پیش‌فرض</label>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check card custom-card p-3">
                                <input class="form-check-input" type="radio" name="puzzling_settings[default_theme]" id="theme_light" value="light" <?php checked($settings['default_theme'] ?? 'light', 'light'); ?>>
                                <label class="form-check-label" for="theme_light">
                                    <i class="ri-sun-line text-warning fs-4 d-block mb-2"></i>
                                    <strong>روشن (Light)</strong>
                                    <p class="text-muted fs-12 mb-0">تم روشن و مناسب روز</p>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check card custom-card p-3">
                                <input class="form-check-input" type="radio" name="puzzling_settings[default_theme]" id="theme_dark" value="dark" <?php checked($settings['default_theme'] ?? '', 'dark'); ?>>
                                <label class="form-check-label" for="theme_dark">
                                    <i class="ri-moon-line text-primary fs-4 d-block mb-2"></i>
                                    <strong>تیره (Dark)</strong>
                                    <p class="text-muted fs-12 mb-0">تم تیره و راحت برای چشم</p>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check card custom-card p-3">
                                <input class="form-check-input" type="radio" name="puzzling_settings[default_theme]" id="theme_auto" value="auto" <?php checked($settings['default_theme'] ?? '', 'auto'); ?>>
                                <label class="form-check-label" for="theme_auto">
                                    <i class="ri-contrast-2-line text-info fs-4 d-block mb-2"></i>
                                    <strong>خودکار (Auto)</strong>
                                    <p class="text-muted fs-12 mb-0">بر اساس سیستم کاربر</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- دکمه ذخیره -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="ri-save-line me-2"></i>
                ذخیره تنظیمات
            </button>
            <button type="button" class="btn btn-secondary btn-lg" onclick="location.reload();">
                <i class="ri-refresh-line me-2"></i>
                انصراف
            </button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Update font size display
    $('#base_font_size').on('input', function() {
        $('#font_size_value').text($(this).val() + 'px');
    });
    
    // Color picker change
    $('input[type="color"]').on('change', function() {
        $(this).next('input[type="text"]').val($(this).val());
    });
});
</script>

<style>
/* استایل خاص برای این صفحه */
.form-check.card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid #e9ecef;
}

.form-check.card:hover {
    border-color: #845adf;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(132, 90, 223, 0.15);
}

.form-check.card input:checked + label {
    color: #845adf;
}

.form-check.card input:checked ~ * {
    border-color: #845adf;
}

.form-check-input {
    margin-top: 0;
}

.form-check-label {
    width: 100%;
    cursor: pointer;
}

.form-control-color {
    width: 60px;
    height: 45px;
    padding: 4px;
}
</style>

