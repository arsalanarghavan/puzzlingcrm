<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// بررسی دسترسی
if ( ! current_user_can( 'manage_options' ) ) {
    echo '<div class="alert alert-danger">شما دسترسی لازم برای مشاهده این صفحه را ندارید.</div>';
    return;
}

// دریافت تنظیمات فعلی
$style_settings = PuzzlingCRM_Settings_Handler::get_setting('style', []);

// مقادیر پیش‌فرض
$defaults = [
    'logo_desktop' => PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/desktop-logo.png',
    'logo_mobile' => PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/toggle-logo.png',
    'logo_dark' => PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/desktop-dark.png',
    'logo_favicon' => PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/favicon.ico',
    'primary_color' => '#6366f1',
    'secondary_color' => '#6c757d',
    'success_color' => '#10b981',
    'danger_color' => '#ef4444',
    'warning_color' => '#f59e0b',
    'info_color' => '#3b82f6',
    'menu_bg_color' => '#1e293b',
    'header_bg_color' => '#ffffff',
    'body_font' => 'Vazirmatn',
    'heading_font' => 'Vazirmatn',
    'font_size_base' => '14',
    'theme_mode' => 'light',
    'menu_style' => 'dark',
    'header_style' => 'light',
    'sidebar_layout' => 'default',
];

$style_settings = wp_parse_args($style_settings, $defaults);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-palette-line me-2"></i>تنظیمات ظاهری و استایل سیستم</h5>
            </div>
            <div class="card-body">
                
                <form id="puzzling-style-settings-form" method="post">
                    <?php wp_nonce_field('puzzling_save_style_settings', 'puzzling_style_nonce'); ?>

                    <!-- بخش لوگوها -->
                    <div class="mb-5">
                        <h6 class="mb-3 border-bottom pb-2"><i class="ri-image-line me-2"></i>لوگوها و تصاویر</h6>
                        <div class="row g-3">
                            
                            <!-- لوگوی دسکتاپ -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">لوگوی دسکتاپ (حالت روشن)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="logo_desktop" id="logo_desktop" value="<?php echo esc_attr($style_settings['logo_desktop']); ?>">
                                    <button class="btn btn-primary upload-logo-btn" type="button" data-target="logo_desktop">
                                        <i class="ri-upload-2-line"></i> آپلود
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <img src="<?php echo esc_url($style_settings['logo_desktop']); ?>" class="img-thumbnail logo-preview" style="max-height: 60px;" id="logo_desktop_preview">
                                </div>
                                <small class="form-text text-muted">توصیه می‌شود: 200x50 پیکسل، فرمت PNG با پس‌زمینه شفاف</small>
                            </div>

                            <!-- لوگوی موبایل -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">لوگوی موبایل (آیکون کوچک)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="logo_mobile" id="logo_mobile" value="<?php echo esc_attr($style_settings['logo_mobile']); ?>">
                                    <button class="btn btn-primary upload-logo-btn" type="button" data-target="logo_mobile">
                                        <i class="ri-upload-2-line"></i> آپلود
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <img src="<?php echo esc_url($style_settings['logo_mobile']); ?>" class="img-thumbnail logo-preview" style="max-height: 60px;" id="logo_mobile_preview">
                                </div>
                                <small class="form-text text-muted">توصیه می‌شود: 50x50 پیکسل، مربع</small>
                            </div>

                            <!-- لوگوی حالت تاریک -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">لوگوی حالت تاریک</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="logo_dark" id="logo_dark" value="<?php echo esc_attr($style_settings['logo_dark']); ?>">
                                    <button class="btn btn-primary upload-logo-btn" type="button" data-target="logo_dark">
                                        <i class="ri-upload-2-line"></i> آپلود
                                    </button>
                                </div>
                                <div class="mt-2" style="background-color: #1e293b; padding: 10px; border-radius: 5px;">
                                    <img src="<?php echo esc_url($style_settings['logo_dark']); ?>" class="logo-preview" style="max-height: 60px;" id="logo_dark_preview">
                                </div>
                                <small class="form-text text-muted">لوگو با رنگ روشن برای حالت تاریک</small>
                            </div>

                            <!-- Favicon -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Favicon (آیکون مرورگر)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="logo_favicon" id="logo_favicon" value="<?php echo esc_attr($style_settings['logo_favicon']); ?>">
                                    <button class="btn btn-primary upload-logo-btn" type="button" data-target="logo_favicon">
                                        <i class="ri-upload-2-line"></i> آپلود
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <img src="<?php echo esc_url($style_settings['logo_favicon']); ?>" class="img-thumbnail logo-preview" style="max-height: 40px;" id="logo_favicon_preview">
                                </div>
                                <small class="form-text text-muted">توصیه می‌شود: 32x32 یا 64x64 پیکسل، فرمت ICO یا PNG</small>
                            </div>

                        </div>
                    </div>

                    <!-- بخش رنگ‌ها -->
                    <div class="mb-5">
                        <h6 class="mb-3 border-bottom pb-2"><i class="ri-palette-fill me-2"></i>رنگ‌های سیستم</h6>
                        <div class="row g-3">
                            
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">رنگ اصلی (Primary)</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="primary_color" value="<?php echo esc_attr($style_settings['primary_color']); ?>">
                                    <input type="text" class="form-control" value="<?php echo esc_attr($style_settings['primary_color']); ?>" readonly>
                                </div>
                                <small class="form-text text-muted">رنگ اصلی دکمه‌ها و المان‌های اصلی</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">رنگ ثانویه (Secondary)</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="secondary_color" value="<?php echo esc_attr($style_settings['secondary_color']); ?>">
                                    <input type="text" class="form-control" value="<?php echo esc_attr($style_settings['secondary_color']); ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">رنگ موفقیت (Success)</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="success_color" value="<?php echo esc_attr($style_settings['success_color']); ?>">
                                    <input type="text" class="form-control" value="<?php echo esc_attr($style_settings['success_color']); ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">رنگ خطر (Danger)</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="danger_color" value="<?php echo esc_attr($style_settings['danger_color']); ?>">
                                    <input type="text" class="form-control" value="<?php echo esc_attr($style_settings['danger_color']); ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">رنگ هشدار (Warning)</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="warning_color" value="<?php echo esc_attr($style_settings['warning_color']); ?>">
                                    <input type="text" class="form-control" value="<?php echo esc_attr($style_settings['warning_color']); ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">رنگ اطلاعات (Info)</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="info_color" value="<?php echo esc_attr($style_settings['info_color']); ?>">
                                    <input type="text" class="form-control" value="<?php echo esc_attr($style_settings['info_color']); ?>" readonly>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- بخش رنگ‌های قالب -->
                    <div class="mb-5">
                        <h6 class="mb-3 border-bottom pb-2"><i class="ri-layout-fill me-2"></i>رنگ‌های قالب</h6>
                        <div class="row g-3">
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">رنگ پس‌زمینه منوی سایدبار</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="menu_bg_color" value="<?php echo esc_attr($style_settings['menu_bg_color']); ?>">
                                    <input type="text" class="form-control" value="<?php echo esc_attr($style_settings['menu_bg_color']); ?>" readonly>
                                </div>
                                <small class="form-text text-muted">رنگ پس‌زمینه منوی کناری</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">رنگ پس‌زمینه هدر</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="header_bg_color" value="<?php echo esc_attr($style_settings['header_bg_color']); ?>">
                                    <input type="text" class="form-control" value="<?php echo esc_attr($style_settings['header_bg_color']); ?>" readonly>
                                </div>
                                <small class="form-text text-muted">رنگ پس‌زمینه نوار بالا</small>
                            </div>

                        </div>
                    </div>

                    <!-- بخش فونت‌ها -->
                    <div class="mb-5">
                        <h6 class="mb-3 border-bottom pb-2"><i class="ri-text me-2"></i>تنظیمات فونت</h6>
                        <div class="row g-3">
                            
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">فونت اصلی (Body)</label>
                                <select class="form-select" name="body_font">
                                    <option value="Vazirmatn" <?php selected($style_settings['body_font'], 'Vazirmatn'); ?>>وزیرمتن</option>
                                    <option value="IRANSans" <?php selected($style_settings['body_font'], 'IRANSans'); ?>>ایران سنس</option>
                                    <option value="Yekan" <?php selected($style_settings['body_font'], 'Yekan'); ?>>یکان</option>
                                    <option value="Samim" <?php selected($style_settings['body_font'], 'Samim'); ?>>صمیم</option>
                                    <option value="Sahel" <?php selected($style_settings['body_font'], 'Sahel'); ?>>ساحل</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">فونت عناوین (Heading)</label>
                                <select class="form-select" name="heading_font">
                                    <option value="Vazirmatn" <?php selected($style_settings['heading_font'], 'Vazirmatn'); ?>>وزیرمتن</option>
                                    <option value="IRANSans" <?php selected($style_settings['heading_font'], 'IRANSans'); ?>>ایران سنس</option>
                                    <option value="Yekan" <?php selected($style_settings['heading_font'], 'Yekan'); ?>>یکان</option>
                                    <option value="Samim" <?php selected($style_settings['heading_font'], 'Samim'); ?>>صمیم</option>
                                    <option value="Sahel" <?php selected($style_settings['heading_font'], 'Sahel'); ?>>ساحل</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">اندازه فونت پایه (پیکسل)</label>
                                <input type="number" class="form-control" name="font_size_base" value="<?php echo esc_attr($style_settings['font_size_base']); ?>" min="12" max="18">
                                <small class="form-text text-muted">پیش‌فرض: 14 پیکسل</small>
                            </div>

                        </div>
                    </div>

                    <!-- بخش تنظیمات قالب -->
                    <div class="mb-5">
                        <h6 class="mb-3 border-bottom pb-2"><i class="ri-settings-3-line me-2"></i>تنظیمات کلی قالب</h6>
                        <div class="row g-3">
                            
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">حالت پیش‌فرض قالب</label>
                                <select class="form-select" name="theme_mode">
                                    <option value="light" <?php selected($style_settings['theme_mode'], 'light'); ?>>روشن</option>
                                    <option value="dark" <?php selected($style_settings['theme_mode'], 'dark'); ?>>تاریک</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">استایل منوی سایدبار</label>
                                <select class="form-select" name="menu_style">
                                    <option value="light" <?php selected($style_settings['menu_style'], 'light'); ?>>روشن</option>
                                    <option value="dark" <?php selected($style_settings['menu_style'], 'dark'); ?>>تاریک</option>
                                    <option value="color" <?php selected($style_settings['menu_style'], 'color'); ?>>رنگی</option>
                                    <option value="gradient" <?php selected($style_settings['menu_style'], 'gradient'); ?>>گرادیانت</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">استایل هدر</label>
                                <select class="form-select" name="header_style">
                                    <option value="light" <?php selected($style_settings['header_style'], 'light'); ?>>روشن</option>
                                    <option value="dark" <?php selected($style_settings['header_style'], 'dark'); ?>>تاریک</option>
                                    <option value="color" <?php selected($style_settings['header_style'], 'color'); ?>>رنگی</option>
                                    <option value="gradient" <?php selected($style_settings['header_style'], 'gradient'); ?>>گرادیانت</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">چیدمان سایدبار</label>
                                <select class="form-select" name="sidebar_layout">
                                    <option value="default" <?php selected($style_settings['sidebar_layout'], 'default'); ?>>پیش‌فرض</option>
                                    <option value="closed" <?php selected($style_settings['sidebar_layout'], 'closed'); ?>>بسته</option>
                                    <option value="icontext" <?php selected($style_settings['sidebar_layout'], 'icontext'); ?>>آیکون + متن</option>
                                    <option value="icon-overlay" <?php selected($style_settings['sidebar_layout'], 'icon-overlay'); ?>>فقط آیکون</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <!-- دکمه‌های عملیات -->
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="ri-save-line me-2"></i>ذخیره تنظیمات استایل
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="reset-style-settings">
                            <i class="ri-refresh-line me-2"></i>بازگشت به پیش‌فرض
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // آپلود تصویر
    $('.upload-logo-btn').on('click', function(e) {
        e.preventDefault();
        
        var targetInput = $(this).data('target');
        var frame = wp.media({
            title: 'انتخاب تصویر',
            button: {
                text: 'استفاده از این تصویر'
            },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#' + targetInput).val(attachment.url);
            $('#' + targetInput + '_preview').attr('src', attachment.url);
        });
        
        frame.open();
    });
    
    // همگام‌سازی color input با text input
    $('input[type="color"]').on('change', function() {
        $(this).next('input[type="text"]').val($(this).val());
    });
    
    // ارسال فرم
    $('#puzzling-style-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=puzzling_save_style_settings';
        
        $.ajax({
            url: puzzling_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#puzzling-style-settings-form button[type="submit"]').prop('disabled', true).html('<i class="ri-loader-4-line me-2 spinner-border spinner-border-sm"></i>در حال ذخیره...');
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ تنظیمات با موفقیت ذخیره شد. صفحه بازخوانی می‌شود...');
                    location.reload();
                } else {
                    alert('✗ خطا در ذخیره تنظیمات: ' + response.data.message);
                    $('#puzzling-style-settings-form button[type="submit"]').prop('disabled', false).html('<i class="ri-save-line me-2"></i>ذخیره تنظیمات استایل');
                }
            },
            error: function() {
                alert('✗ خطا در ارتباط با سرور');
                $('#puzzling-style-settings-form button[type="submit"]').prop('disabled', false).html('<i class="ri-save-line me-2"></i>ذخیره تنظیمات استایل');
            }
        });
    });
    
    // بازگشت به پیش‌فرض
    $('#reset-style-settings').on('click', function() {
        if (confirm('آیا مطمئن هستید که می‌خواهید تمام تنظیمات استایل را به حالت پیش‌فرض برگردانید؟')) {
            $.ajax({
                url: puzzling_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'puzzling_reset_style_settings',
                    nonce: '<?php echo wp_create_nonce('puzzling_reset_style_settings'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('✓ تنظیمات به حالت پیش‌فرض بازگشت. صفحه بازخوانی می‌شود...');
                        location.reload();
                    } else {
                        alert('✗ خطا: ' + response.data.message);
                    }
                }
            });
        }
    });
    
});
</script>
