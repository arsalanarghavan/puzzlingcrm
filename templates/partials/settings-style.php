<?php
/**
 * Style Settings - تنظیمات کامل White Label
 * شامل: برندینگ، رنگ‌بندی، متن‌ها، فونت، تم
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

// Get white label settings
$company_name = get_option('puzzlingcrm_wl_company_name', 'PuzzlingCRM');
$company_logo = get_option('puzzlingcrm_wl_company_logo', '');
$login_logo = get_option('puzzlingcrm_wl_login_logo', '');
$email_logo = get_option('puzzlingcrm_wl_email_logo', '');
$favicon = get_option('puzzlingcrm_wl_company_icon', '');
$company_url = get_option('puzzlingcrm_wl_company_url', 'https://puzzlingco.com');

$primary_color = get_option('puzzlingcrm_wl_primary_color', '#e03f2b');
$secondary_color = get_option('puzzlingcrm_wl_secondary_color', '#6c757d');
$accent_color = get_option('puzzlingcrm_wl_accent_color', '#FF5722');
$success_color = get_option('puzzlingcrm_wl_success_color', '#28a745');
$warning_color = get_option('puzzlingcrm_wl_warning_color', '#ffc107');
$danger_color = get_option('puzzlingcrm_wl_danger_color', '#dc3545');
$info_color = get_option('puzzlingcrm_wl_info_color', '#17a2b8');

$dashboard_title = get_option('puzzlingcrm_wl_dashboard_title', 'داشبورد');
$welcome_message = get_option('puzzlingcrm_wl_welcome_message', '');
$footer_text = get_option('puzzlingcrm_wl_footer_text', '');
$copyright_text = get_option('puzzlingcrm_wl_copyright_text', '');

$support_email = get_option('puzzlingcrm_wl_support_email', '');
$support_phone = get_option('puzzlingcrm_wl_support_phone', '');
$support_url = get_option('puzzlingcrm_wl_support_url', '');

$settings = PuzzlingCRM_Settings_Handler::get_all_settings();
$font_family = $settings['font_family'] ?? 'IRANSans';
$base_font_size = $settings['base_font_size'] ?? '15';
$default_theme = $settings['default_theme'] ?? 'light';

$custom_css = get_option('puzzlingcrm_wl_custom_css', '');
$custom_js = get_option('puzzlingcrm_wl_custom_js', '');
$hide_branding = get_option('puzzlingcrm_wl_hide_branding', 0);
?>

<div class="pzl-form-container">
    <h4><i class="ri-palette-line"></i> تنظیمات ظاهر و استایل</h4>
    
    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="styleSettingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="branding-tab" data-bs-toggle="tab" data-bs-target="#branding" type="button" role="tab">
                <i class="ri-store-line me-1"></i> برندینگ
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="colors-tab" data-bs-toggle="tab" data-bs-target="#colors" type="button" role="tab">
                <i class="ri-paint-brush-line me-1"></i> رنگ‌بندی
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="texts-tab" data-bs-toggle="tab" data-bs-target="#texts" type="button" role="tab">
                <i class="ri-text me-1"></i> متن‌ها و محتوا
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="fonts-tab" data-bs-toggle="tab" data-bs-target="#fonts" type="button" role="tab">
                <i class="ri-font-size-2 me-1"></i> فونت و تم
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab">
                <i class="ri-settings-3-line me-1"></i> پیشرفته
            </button>
        </li>
    </ul>

    <form id="puzzling-style-settings-form" method="post" action="#" class="pzl-form" data-action="puzzling_save_white_label_settings" onsubmit="return false;">
        <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
        <input type="hidden" name="settings_tab" value="style">

        <div class="tab-content" id="styleSettingsTabContent">
            
            <!-- Tab 1: Branding -->
            <div class="tab-pane fade show active" id="branding" role="tabpanel">
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-store-line me-2"></i>
                            اطلاعات شرکت و برندینگ
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="wl_company_name" class="form-label">نام شرکت <span class="text-danger">*</span></label>
                                    <input type="text" id="wl_company_name" name="wl_company_name" 
                                           value="<?php echo esc_attr($company_name); ?>" 
                                           class="form-control" required>
                                    <small class="form-text text-muted">نام شرکت شما که در تمام بخش‌های سیستم نمایش داده می‌شود</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="wl_company_url" class="form-label">URL شرکت</label>
                                    <input type="url" id="wl_company_url" name="wl_company_url" 
                                           value="<?php echo esc_url($company_url); ?>" 
                                           class="form-control ltr-input">
                                    <small class="form-text text-muted">آدرس وب‌سایت شرکت (برای کپی‌رایت فوتر)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-image-line me-2"></i>
                            مدیریت لوگوها
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Dashboard Logo -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label">لوگوی داشبورد (Header/Sidebar)</label>
                                <div class="logo-upload-wrapper">
                                    <input type="hidden" id="wl_company_logo" name="wl_company_logo" value="<?php echo esc_url($company_logo); ?>">
                                    <div class="logo-preview mb-2" style="min-height: 100px; border: 2px dashed #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                        <?php if ($company_logo): ?>
                                            <img src="<?php echo esc_url($company_logo); ?>" alt="Logo" style="max-width: 100%; max-height: 100px; padding: 10px;">
                                        <?php else: ?>
                                            <span class="text-muted"><i class="ri-image-add-line fs-3"></i><br>لوگو انتخاب نشده</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm logo-upload-btn" data-target="wl_company_logo">
                                            <i class="ri-upload-cloud-line me-1"></i> آپلود لوگو
                                        </button>
                                        <?php if ($company_logo): ?>
                                            <button type="button" class="btn btn-danger btn-sm logo-remove-btn" data-target="wl_company_logo">
                                                <i class="ri-delete-bin-line me-1"></i> حذف
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <small class="form-text text-muted d-block mt-2">لوگوی اصلی سیستم - در Header و Sidebar نمایش داده می‌شود</small>
                                </div>
                            </div>

                            <!-- Login Logo -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label">لوگوی صفحه ورود</label>
                                <div class="logo-upload-wrapper">
                                    <input type="hidden" id="wl_login_logo" name="wl_login_logo" value="<?php echo esc_url($login_logo); ?>">
                                    <div class="logo-preview mb-2" style="min-height: 100px; border: 2px dashed #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                        <?php if ($login_logo): ?>
                                            <img src="<?php echo esc_url($login_logo); ?>" alt="Login Logo" style="max-width: 100%; max-height: 100px; padding: 10px;">
                                        <?php else: ?>
                                            <span class="text-muted"><i class="ri-image-add-line fs-3"></i><br>لوگو انتخاب نشده</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm logo-upload-btn" data-target="wl_login_logo">
                                            <i class="ri-upload-cloud-line me-1"></i> آپلود لوگو
                                        </button>
                                        <?php if ($login_logo): ?>
                                            <button type="button" class="btn btn-danger btn-sm logo-remove-btn" data-target="wl_login_logo">
                                                <i class="ri-delete-bin-line me-1"></i> حذف
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <small class="form-text text-muted d-block mt-2">لوگوی صفحه ورود و لاگین</small>
                                </div>
                            </div>

                            <!-- Email Logo -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label">لوگوی ایمیل‌ها</label>
                                <div class="logo-upload-wrapper">
                                    <input type="hidden" id="wl_email_logo" name="wl_email_logo" value="<?php echo esc_url($email_logo); ?>">
                                    <div class="logo-preview mb-2" style="min-height: 100px; border: 2px dashed #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                        <?php if ($email_logo): ?>
                                            <img src="<?php echo esc_url($email_logo); ?>" alt="Email Logo" style="max-width: 100%; max-height: 100px; padding: 10px;">
                                        <?php else: ?>
                                            <span class="text-muted"><i class="ri-image-add-line fs-3"></i><br>لوگو انتخاب نشده</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm logo-upload-btn" data-target="wl_email_logo">
                                            <i class="ri-upload-cloud-line me-1"></i> آپلود لوگو
                                        </button>
                                        <?php if ($email_logo): ?>
                                            <button type="button" class="btn btn-danger btn-sm logo-remove-btn" data-target="wl_email_logo">
                                                <i class="ri-delete-bin-line me-1"></i> حذف
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <small class="form-text text-muted d-block mt-2">لوگوی استفاده شده در ایمیل‌های ارسالی</small>
                                </div>
                            </div>

                            <!-- Favicon -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label">فاوآیکن (Favicon)</label>
                                <div class="logo-upload-wrapper">
                                    <input type="hidden" id="wl_company_icon" name="wl_company_icon" value="<?php echo esc_url($favicon); ?>">
                                    <div class="logo-preview mb-2" style="min-height: 100px; border: 2px dashed #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                        <?php if ($favicon): ?>
                                            <img src="<?php echo esc_url($favicon); ?>" alt="Favicon" style="max-width: 64px; max-height: 64px; padding: 10px;">
                                        <?php else: ?>
                                            <span class="text-muted"><i class="ri-image-add-line fs-3"></i><br>فاوآیکن انتخاب نشده</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm logo-upload-btn" data-target="wl_company_icon">
                                            <i class="ri-upload-cloud-line me-1"></i> آپلود فاوآیکن
                                        </button>
                                        <?php if ($favicon): ?>
                                            <button type="button" class="btn btn-danger btn-sm logo-remove-btn" data-target="wl_company_icon">
                                                <i class="ri-delete-bin-line me-1"></i> حذف
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <small class="form-text text-muted d-block mt-2">آیکون نمایش داده شده در تب مرورگر (پیشنهاد: 32x32 یا 64x64 پیکسل)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Colors -->
            <div class="tab-pane fade" id="colors" role="tabpanel">
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-paint-brush-line me-2"></i>
                            رنگ‌بندی سیستم
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="wl_primary_color" class="form-label">رنگ اصلی (Primary)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" id="wl_primary_color" name="wl_primary_color" 
                                           value="<?php echo esc_attr($primary_color); ?>" 
                                           class="form-control form-control-color color-input">
                                    <input type="text" value="<?php echo esc_attr($primary_color); ?>" 
                                           class="form-control ltr-input color-text" readonly>
                                </div>
                                <small class="form-text text-muted">رنگ اصلی برای دکمه‌ها، لینک‌ها و المان‌های مهم</small>
                                <div class="color-preview mt-2" style="background: <?php echo esc_attr($primary_color); ?>; height: 40px; border-radius: 6px; border: 1px solid #dee2e6;"></div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="wl_secondary_color" class="form-label">رنگ ثانویه (Secondary)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" id="wl_secondary_color" name="wl_secondary_color" 
                                           value="<?php echo esc_attr($secondary_color); ?>" 
                                           class="form-control form-control-color color-input">
                                    <input type="text" value="<?php echo esc_attr($secondary_color); ?>" 
                                           class="form-control ltr-input color-text" readonly>
                                </div>
                                <small class="form-text text-muted">رنگ ثانویه برای دکمه‌های غیرفعال و المان‌های کمتر مهم</small>
                                <div class="color-preview mt-2" style="background: <?php echo esc_attr($secondary_color); ?>; height: 40px; border-radius: 6px; border: 1px solid #dee2e6;"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="wl_accent_color" class="form-label">رنگ تاکیدی (Accent)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" id="wl_accent_color" name="wl_accent_color" 
                                           value="<?php echo esc_attr($accent_color); ?>" 
                                           class="form-control form-control-color color-input">
                                    <input type="text" value="<?php echo esc_attr($accent_color); ?>" 
                                           class="form-control ltr-input color-text" readonly>
                                </div>
                                <small class="form-text text-muted">رنگ تاکیدی برای برجسته کردن المان‌های خاص</small>
                                <div class="color-preview mt-2" style="background: <?php echo esc_attr($accent_color); ?>; height: 40px; border-radius: 6px; border: 1px solid #dee2e6;"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="wl_success_color" class="form-label">رنگ موفقیت (Success)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" id="wl_success_color" name="wl_success_color" 
                                           value="<?php echo esc_attr($success_color); ?>" 
                                           class="form-control form-control-color color-input">
                                    <input type="text" value="<?php echo esc_attr($success_color); ?>" 
                                           class="form-control ltr-input color-text" readonly>
                                </div>
                                <small class="form-text text-muted">رنگ نمایش پیام‌های موفقیت</small>
                                <div class="color-preview mt-2" style="background: <?php echo esc_attr($success_color); ?>; height: 40px; border-radius: 6px; border: 1px solid #dee2e6;"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="wl_warning_color" class="form-label">رنگ هشدار (Warning)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" id="wl_warning_color" name="wl_warning_color" 
                                           value="<?php echo esc_attr($warning_color); ?>" 
                                           class="form-control form-control-color color-input">
                                    <input type="text" value="<?php echo esc_attr($warning_color); ?>" 
                                           class="form-control ltr-input color-text" readonly>
                                </div>
                                <small class="form-text text-muted">رنگ نمایش پیام‌های هشدار</small>
                                <div class="color-preview mt-2" style="background: <?php echo esc_attr($warning_color); ?>; height: 40px; border-radius: 6px; border: 1px solid #dee2e6;"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="wl_danger_color" class="form-label">رنگ خطر (Danger)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" id="wl_danger_color" name="wl_danger_color" 
                                           value="<?php echo esc_attr($danger_color); ?>" 
                                           class="form-control form-control-color color-input">
                                    <input type="text" value="<?php echo esc_attr($danger_color); ?>" 
                                           class="form-control ltr-input color-text" readonly>
                                </div>
                                <small class="form-text text-muted">رنگ نمایش پیام‌های خطا</small>
                                <div class="color-preview mt-2" style="background: <?php echo esc_attr($danger_color); ?>; height: 40px; border-radius: 6px; border: 1px solid #dee2e6;"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="wl_info_color" class="form-label">رنگ اطلاعات (Info)</label>
                                <div class="d-flex gap-2">
                                    <input type="color" id="wl_info_color" name="wl_info_color" 
                                           value="<?php echo esc_attr($info_color); ?>" 
                                           class="form-control form-control-color color-input">
                                    <input type="text" value="<?php echo esc_attr($info_color); ?>" 
                                           class="form-control ltr-input color-text" readonly>
                                </div>
                                <small class="form-text text-muted">رنگ نمایش پیام‌های اطلاعاتی</small>
                                <div class="color-preview mt-2" style="background: <?php echo esc_attr($info_color); ?>; height: 40px; border-radius: 6px; border: 1px solid #dee2e6;"></div>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-center mt-4">
                            <i class="ri-information-line fs-5 me-3"></i>
                            <div>
                                <strong>نکته:</strong> پس از تغییر رنگ‌ها، ممکن است نیاز باشد صفحه را رفرش کنید (F5) تا تغییرات کاملاً اعمال شوند.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Texts -->
            <div class="tab-pane fade" id="texts" role="tabpanel">
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-text me-2"></i>
                            متن‌ها و محتوا
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="wl_dashboard_title" class="form-label">عنوان داشبورد</label>
                            <input type="text" id="wl_dashboard_title" name="wl_dashboard_title" 
                                   value="<?php echo esc_attr($dashboard_title); ?>" 
                                   class="form-control">
                            <small class="form-text text-muted">عنوان نمایش داده شده در صفحه اصلی داشبورد</small>
                        </div>

                        <div class="mb-3">
                            <label for="wl_welcome_message" class="form-label">پیام خوش‌آمدگویی</label>
                            <textarea id="wl_welcome_message" name="wl_welcome_message" 
                                      class="form-control" rows="3"><?php echo esc_textarea($welcome_message); ?></textarea>
                            <small class="form-text text-muted">پیام خوش‌آمدگویی که به کاربران نمایش داده می‌شود</small>
                        </div>

                        <div class="mb-3">
                            <label for="wl_footer_text" class="form-label">متن فوتر</label>
                            <input type="text" id="wl_footer_text" name="wl_footer_text" 
                                   value="<?php echo esc_attr($footer_text); ?>" 
                                   class="form-control">
                            <small class="form-text text-muted">متن نمایش داده شده در پایین صفحه (فوتر)</small>
                        </div>

                        <div class="mb-3">
                            <label for="wl_copyright_text" class="form-label">متن کپی‌رایت سفارشی</label>
                            <textarea id="wl_copyright_text" name="wl_copyright_text" 
                                      class="form-control" rows="3" 
                                      placeholder="<?php esc_attr_e('اگر خالی باشد، از متن پیش‌فرض استفاده می‌شود', 'puzzlingcrm'); ?>"><?php echo esc_textarea($copyright_text); ?></textarea>
                            <small class="form-text text-muted">می‌توانید از HTML استفاده کنید. متغیرهای موجود: <code>{year}</code>, <code>{company_name}</code>, <code>{company_url}</code></small>
                        </div>

                        <div class="alert alert-success d-flex align-items-start">
                            <i class="ri-lightbulb-line fs-5 me-3 mt-1"></i>
                            <div>
                                <strong>راهنما:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>از متغیر <code>{year}</code> برای نمایش سال جاری استفاده کنید</li>
                                    <li>از متغیر <code>{company_name}</code> برای نمایش نام شرکت استفاده کنید</li>
                                    <li>از متغیر <code>{company_url}</code> برای نمایش لینک شرکت استفاده کنید</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-customer-service-line me-2"></i>
                            اطلاعات تماس و پشتیبانی
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="wl_support_email" class="form-label">ایمیل پشتیبانی</label>
                                <input type="email" id="wl_support_email" name="wl_support_email" 
                                       value="<?php echo esc_attr($support_email); ?>" 
                                       class="form-control ltr-input">
                                <small class="form-text text-muted">آدرس ایمیل برای پشتیبانی</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="wl_support_phone" class="form-label">تلفن پشتیبانی</label>
                                <input type="text" id="wl_support_phone" name="wl_support_phone" 
                                       value="<?php echo esc_attr($support_phone); ?>" 
                                       class="form-control ltr-input">
                                <small class="form-text text-muted">شماره تماس برای پشتیبانی</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="wl_support_url" class="form-label">URL پشتیبانی</label>
                                <input type="url" id="wl_support_url" name="wl_support_url" 
                                       value="<?php echo esc_url($support_url); ?>" 
                                       class="form-control ltr-input">
                                <small class="form-text text-muted">آدرس وب‌سایت یا صفحه پشتیبانی</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Fonts & Theme -->
            <div class="tab-pane fade" id="fonts" role="tabpanel">
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
                            <select id="font_family" name="font_family" class="form-select">
                                <option value="IRANSans" <?php selected($font_family, 'IRANSans'); ?>>IRANSans (پیش‌فرض)</option>
                                <option value="Vazirmatn" <?php selected($font_family, 'Vazirmatn'); ?>>Vazirmatn</option>
                                <option value="Yekan" <?php selected($font_family, 'Yekan'); ?>>یکان</option>
                                <option value="Shabnam" <?php selected($font_family, 'Shabnam'); ?>>شبنم</option>
                            </select>
                            <small class="form-text text-muted">فونت اصلی که در تمام سیستم استفاده می‌شود</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="base_font_size" class="form-label">اندازه پایه فونت</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="range" id="base_font_size" name="base_font_size" 
                                       min="12" max="18" value="<?php echo esc_attr($base_font_size); ?>" 
                                       class="form-range" style="flex: 1;">
                                <span id="font_size_value" class="badge bg-primary"><?php echo esc_html($base_font_size); ?>px</span>
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
                                    <div class="form-check card custom-card p-3 theme-option">
                                        <input class="form-check-input" type="radio" name="default_theme" id="theme_light" value="light" <?php checked($default_theme, 'light'); ?>>
                                        <label class="form-check-label" for="theme_light">
                                            <i class="ri-sun-line text-warning fs-4 d-block mb-2"></i>
                                            <strong>روشن (Light)</strong>
                                            <p class="text-muted fs-12 mb-0">تم روشن و مناسب روز</p>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check card custom-card p-3 theme-option">
                                        <input class="form-check-input" type="radio" name="default_theme" id="theme_dark" value="dark" <?php checked($default_theme, 'dark'); ?>>
                                        <label class="form-check-label" for="theme_dark">
                                            <i class="ri-moon-line text-primary fs-4 d-block mb-2"></i>
                                            <strong>تیره (Dark)</strong>
                                            <p class="text-muted fs-12 mb-0">تم تیره و راحت برای چشم</p>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check card custom-card p-3 theme-option">
                                        <input class="form-check-input" type="radio" name="default_theme" id="theme_auto" value="auto" <?php checked($default_theme, 'auto'); ?>>
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
            </div>

            <!-- Tab 5: Advanced -->
            <div class="tab-pane fade" id="advanced" role="tabpanel">
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-code-s-slash-line me-2"></i>
                            تنظیمات پیشرفته
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="wl_custom_css" class="form-label">CSS سفارشی</label>
                            <textarea id="wl_custom_css" name="wl_custom_css" 
                                      class="form-control code" rows="10" 
                                      placeholder="/* CSS سفارشی خود را اینجا وارد کنید */"><?php echo esc_textarea($custom_css); ?></textarea>
                            <small class="form-text text-muted">استایل‌های CSS سفارشی که در تمام صفحات اعمال می‌شوند</small>
                        </div>

                        <div class="mb-3">
                            <label for="wl_custom_js" class="form-label">JavaScript سفارشی</label>
                            <textarea id="wl_custom_js" name="wl_custom_js" 
                                      class="form-control code" rows="10" 
                                      placeholder="// JavaScript سفارشی خود را اینجا وارد کنید"><?php echo esc_textarea($custom_js); ?></textarea>
                            <small class="form-text text-muted">اسکریپت‌های JavaScript سفارشی</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="wl_hide_branding" name="wl_hide_branding" value="1" <?php checked($hide_branding, 1); ?>>
                                <label class="form-check-label" for="wl_hide_branding">
                                    <strong>مخفی کردن برندینگ</strong>
                                </label>
                            </div>
                            <small class="form-text text-muted">مخفی کردن تمام نشانه‌های "PuzzlingCRM" در سیستم</small>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start">
                            <i class="ri-error-warning-line fs-5 me-3 mt-1"></i>
                            <div>
                                <strong>هشدار:</strong> استفاده از CSS و JavaScript سفارشی ممکن است باعث تغییر در رفتار سیستم شود. قبل از استفاده، حتماً نسخه پشتیبان تهیه کنید.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- دکمه ذخیره -->
        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg" data-puzzling-skip-global-handler="true">
                <i class="ri-save-line me-2"></i>
                ذخیره تمام تنظیمات
            </button>
            <button type="button" class="btn btn-secondary btn-lg" onclick="location.reload();">
                <i class="ri-refresh-line me-2"></i>
                انصراف
            </button>
        </div>
    </form>
</div>

<script>
// Register handler IMMEDIATELY in inline script (runs before document.ready)
(function() {
    function registerStyleFormHandler() {
        var $styleForm = jQuery('#puzzling-style-settings-form');
        if ($styleForm.length === 0) {
            // Form not loaded yet, try again
            setTimeout(registerStyleFormHandler, 100);
            return;
        }
        
        console.log('Registering style settings form handler...');
        
        // Remove ALL existing handlers including delegated ones
        jQuery(document).off('submit', '#puzzling-style-settings-form');
        $styleForm.off('submit');
        
        // Register using native addEventListener for highest priority (capture phase)
        var formElement = $styleForm[0];
        
        // IMPORTANT: Prevent the global forms-enhancement.js handler from disabling our button
        var $submitBtn = $styleForm.find('button[type="submit"]');
        if ($submitBtn.length) {
            console.log('Submit button found, preventing global disable handler...');
            
            // Remove any data attributes that might trigger the global handler
            $submitBtn.removeData('loading');
            
            // Stop the global handler from disabling the button by preventing its event from reaching it
            $submitBtn.on('click.puzzlingStyleForm', function(e) {
                // Stop event from bubbling to global handler
                e.stopImmediatePropagation();
                console.log('=== SUBMIT BUTTON CLICKED (stopping global handler) ===');
            });
        } else {
            console.error('Submit button NOT FOUND!');
        }
        
        // Also listen for click events on form to see if anything is preventing submit
        formElement.addEventListener('click', function(e) {
            if (e.target && e.target.type === 'submit') {
                console.log('=== FORM CLICK EVENT - Submit button clicked ===');
                console.log('Target:', e.target);
                console.log('Current target:', e.currentTarget);
            }
        }, true);
        
        formElement.addEventListener('submit', function(e) {
            console.log('=== FORM SUBMIT EVENT TRIGGERED (native) ===');
            console.log('Event:', e);
            console.log('Form:', this);
            
            // IMPORTANT: Re-enable button in case global handler disabled it
            var $btn = jQuery(this).find('button[type="submit"]');
            if ($btn.length) {
                $btn.prop('disabled', false);
                console.log('Button re-enabled');
            }
            
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            console.log('=== Style Settings Form Submit Handler Called (native) ===');
            
            var $form = jQuery(formElement);
            var $btn = $form.find('button[type="submit"]');
            var originalButtonHtml = $btn.html();
            
            var formData = new FormData(formElement);
            
            // Get nonce from form or global object
            var nonce = $form.find('input[name="security"]').val();
            if (!nonce) {
                if (typeof window.puzzlingcrm_ajax_obj !== 'undefined' && window.puzzlingcrm_ajax_obj.nonce) {
                    nonce = window.puzzlingcrm_ajax_obj.nonce;
                } else if (typeof puzzlingcrm_ajax_obj !== 'undefined' && puzzlingcrm_ajax_obj.nonce) {
                    nonce = puzzlingcrm_ajax_obj.nonce;
                }
            }
            if (!nonce) {
                nonce = '<?php echo wp_create_nonce('puzzlingcrm-ajax-nonce'); ?>';
            }
            
            // Debug: Check specific fields BEFORE adding to FormData
            console.log('=== BEFORE FormData Setup ===');
            console.log('Nonce found:', nonce ? 'YES' : 'NO', nonce ? nonce.substring(0, 10) + '...' : 'NO');
            console.log('Company Name:', $form.find('#wl_company_name').val());
            console.log('Company Logo:', $form.find('#wl_company_logo').val());
            console.log('Login Logo:', $form.find('#wl_login_logo').val());
            console.log('Email Logo:', $form.find('#wl_email_logo').val());
            console.log('Company Icon:', $form.find('#wl_company_icon').val());
            
            // Ensure all fields are explicitly added to FormData
            formData.set('security', nonce);
            formData.set('action', 'puzzling_save_white_label_settings');
            formData.set('settings_tab', 'style');
            
            // Explicitly add ALL form fields to FormData
            formData.set('wl_company_name', $form.find('#wl_company_name').val() || '');
            formData.set('wl_company_url', $form.find('#wl_company_url').val() || '');
            formData.set('wl_company_logo', $form.find('#wl_company_logo').val() || '');
            formData.set('wl_login_logo', $form.find('#wl_login_logo').val() || '');
            formData.set('wl_email_logo', $form.find('#wl_email_logo').val() || '');
            formData.set('wl_company_icon', $form.find('#wl_company_icon').val() || '');
            formData.set('wl_primary_color', $form.find('#wl_primary_color').val() || '');
            formData.set('wl_secondary_color', $form.find('#wl_secondary_color').val() || '');
            formData.set('wl_accent_color', $form.find('#wl_accent_color').val() || '');
            formData.set('wl_success_color', $form.find('#wl_success_color').val() || '');
            formData.set('wl_warning_color', $form.find('#wl_warning_color').val() || '');
            formData.set('wl_danger_color', $form.find('#wl_danger_color').val() || '');
            formData.set('wl_info_color', $form.find('#wl_info_color').val() || '');
            formData.set('wl_dashboard_title', $form.find('#wl_dashboard_title').val() || '');
            formData.set('wl_welcome_message', $form.find('#wl_welcome_message').val() || '');
            formData.set('wl_footer_text', $form.find('#wl_footer_text').val() || '');
            formData.set('wl_copyright_text', $form.find('#wl_copyright_text').val() || '');
            formData.set('wl_support_email', $form.find('#wl_support_email').val() || '');
            formData.set('wl_support_phone', $form.find('#wl_support_phone').val() || '');
            formData.set('wl_support_url', $form.find('#wl_support_url').val() || '');
            formData.set('font_family', $form.find('#font_family').val() || '');
            formData.set('base_font_size', $form.find('#base_font_size').val() || '');
            formData.set('default_theme', $form.find('input[name="default_theme"]:checked').val() || '');
            formData.set('wl_custom_css', $form.find('#wl_custom_css').val() || '');
            formData.set('wl_custom_js', $form.find('#wl_custom_js').val() || '');
            formData.set('wl_hide_branding', $form.find('#wl_hide_branding').is(':checked') ? '1' : '0');
            
            // Verify logo fields in FormData AFTER explicit set
            console.log('=== AFTER FormData Setup ===');
            console.log('security in FormData:', formData.get('security') ? 'YES' : 'NO');
            console.log('action in FormData:', formData.get('action'));
            console.log('wl_company_logo in FormData:', formData.get('wl_company_logo'));
            console.log('wl_login_logo in FormData:', formData.get('wl_login_logo'));
            
            // Get AJAX URL
            var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
            if (typeof window.puzzlingcrm_ajax_obj !== 'undefined' && window.puzzlingcrm_ajax_obj.ajax_url) {
                ajaxUrl = window.puzzlingcrm_ajax_obj.ajax_url;
            } else if (typeof puzzlingcrm_ajax_obj !== 'undefined' && puzzlingcrm_ajax_obj.ajax_url) {
                ajaxUrl = puzzlingcrm_ajax_obj.ajax_url;
            }
            
            console.log('AJAX URL:', ajaxUrl);
            
            $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> در حال ذخیره...');
            
            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json', // Explicitly expect JSON response
                success: function(response) {
                    console.log('=== AJAX SUCCESS CALLBACK ===');
                    console.log('Full Response:', response);
                    console.log('Response Type:', typeof response);
                    console.log('Response.success:', response ? response.success : 'undefined');
                    console.log('Response.data:', response ? response.data : 'undefined');
                    
                    $btn.prop('disabled', false).html(originalButtonHtml);
                    
                    // Check if response is a string (JSON string) that needs parsing
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                            console.log('Parsed response:', response);
                        } catch(e) {
                            console.error('Failed to parse response string:', e);
                        }
                    }
                    
                    if (response && response.success) {
                        console.log('Response indicates success!');
                        Swal.fire({
                            icon: 'success',
                            title: 'موفق',
                            text: (response.data && response.data.message) ? response.data.message : 'تنظیمات با موفقیت ذخیره شد.',
                            confirmButtonText: 'باشه'
                        }).then(function() {
                            if (response.data && response.data.reload) {
                                console.log('Reloading page...');
                                location.reload();
                            }
                        });
                    } else {
                        console.error('Response indicates failure:', response);
                        var errorMsg = 'خطا در ذخیره تنظیمات.';
                        if (response && response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'خطا',
                            text: errorMsg,
                            confirmButtonText: 'باشه'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false).html(originalButtonHtml);
                    
                    console.error('=== AJAX ERROR CALLBACK ===');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Status Code:', xhr.status);
                    console.error('Status Text:', xhr.statusText);
                    console.error('Response Text:', xhr.responseText);
                    console.error('Response JSON:', xhr.responseJSON);
                    console.error('Full XHR object:', xhr);
                    
                    var errorMessage = 'خطا در ارتباط با سرور.';
                    try {
                        if (xhr.responseJSON && xhr.responseJSON.data) {
                            errorMessage = xhr.responseJSON.data.message || errorMessage;
                        } else if (xhr.responseText) {
                            var response = JSON.parse(xhr.responseText);
                            if (response.data && response.data.message) {
                                errorMessage = response.data.message;
                            }
                        }
                    } catch(e) {
                        console.error('Failed to parse error response:', e);
                    }
                    
                    if (xhr.status === 400) {
                        errorMessage = 'خطا در درخواست (400). ' + (errorMessage || 'لطفاً دوباره تلاش کنید.');
                    } else if (xhr.status === 403) {
                        errorMessage = 'دسترسی غیرمجاز (403). لطفاً صفحه را رفرش کنید.';
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        html: '<div>' + errorMessage + '</div><small>کد خطا: ' + xhr.status + '</small>',
                        confirmButtonText: 'باشه'
                    });
                }
            });
            
            return false;
        }, true); // Use capture phase for priority
        
        console.log('Style settings form handler registered successfully (native)');
    }
    
    // Try to register immediately
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery is available, registering handler...');
        registerStyleFormHandler();
    } else {
        console.log('jQuery not available yet, waiting...');
        // Wait for jQuery
        window.addEventListener('load', function() {
            if (typeof jQuery !== 'undefined') {
                console.log('jQuery now available, registering handler...');
                registerStyleFormHandler();
            } else {
                console.error('jQuery still not available after load event!');
            }
        });
    }
})();

jQuery(document).ready(function($) {
    // Double-check: Register form handler in document.ready as backup
    var $styleForm = $('#puzzling-style-settings-form');
    if ($styleForm.length) {
        console.log('Document ready - Registering form handler as backup...');
        
        // Check if handler is already registered by checking if form has data attribute
        if (!$styleForm.data('handler-registered')) {
            // Remove any existing handlers
            $styleForm.off('submit');
            jQuery(document).off('submit', '#puzzling-style-settings-form');
            
            // IMPORTANT: Prevent global handler from disabling button
            var $submitBtn = $styleForm.find('button[type="submit"]');
            if ($submitBtn.length) {
                console.log('Submit button found in jQuery handler, preventing global disable...');
                
                // Remove loading data to prevent global handler
                $submitBtn.removeData('loading');
                
                // Stop global handler from processing this button
                $submitBtn.on('click.puzzlingStyleForm', function(e) {
                    e.stopImmediatePropagation();
                    console.log('=== SUBMIT BUTTON CLICKED (jQuery - stopping global) ===');
                });
            } else {
                console.error('Submit button NOT FOUND in jQuery handler!');
            }
            
            // Register handler
            $styleForm.on('submit', function(e) {
                console.log('=== FORM SUBMIT EVENT TRIGGERED (jQuery) ===');
                console.log('Event:', e);
                console.log('Form element:', this);
                console.log('Form ID:', $(this).attr('id'));
                
                // IMPORTANT: Re-enable button in case global handler disabled it
                var $btn = $(this).find('button[type="submit"]');
                if ($btn.length) {
                    $btn.prop('disabled', false);
                    console.log('Button re-enabled in jQuery handler');
                }
                
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                console.log('=== Style Settings Form Submit Handler Called (jQuery) ===');
                
                var $form = $(this);
                var $btn = $form.find('button[type="submit"]');
                var originalButtonHtml = $btn.html();
                
                var formData = new FormData(this);
                
                // Get nonce
                var nonce = $form.find('input[name="security"]').val();
                if (!nonce && typeof window.puzzlingcrm_ajax_obj !== 'undefined' && window.puzzlingcrm_ajax_obj.nonce) {
                    nonce = window.puzzlingcrm_ajax_obj.nonce;
                }
                if (!nonce) {
                    nonce = '<?php echo wp_create_nonce('puzzlingcrm-ajax-nonce'); ?>';
                }
                
                console.log('=== BEFORE FormData Setup ===');
                console.log('Nonce:', nonce ? 'YES' : 'NO');
                console.log('Company Logo:', $form.find('#wl_company_logo').val());
                
                // Add all fields explicitly
                formData.set('security', nonce);
                formData.set('action', 'puzzling_save_white_label_settings');
                formData.set('settings_tab', 'style');
                formData.set('wl_company_name', $form.find('#wl_company_name').val() || '');
                formData.set('wl_company_url', $form.find('#wl_company_url').val() || '');
                formData.set('wl_company_logo', $form.find('#wl_company_logo').val() || '');
                formData.set('wl_login_logo', $form.find('#wl_login_logo').val() || '');
                formData.set('wl_email_logo', $form.find('#wl_email_logo').val() || '');
                formData.set('wl_company_icon', $form.find('#wl_company_icon').val() || '');
                formData.set('wl_primary_color', $form.find('#wl_primary_color').val() || '');
                formData.set('wl_secondary_color', $form.find('#wl_secondary_color').val() || '');
                formData.set('wl_accent_color', $form.find('#wl_accent_color').val() || '');
                formData.set('wl_success_color', $form.find('#wl_success_color').val() || '');
                formData.set('wl_warning_color', $form.find('#wl_warning_color').val() || '');
                formData.set('wl_danger_color', $form.find('#wl_danger_color').val() || '');
                formData.set('wl_info_color', $form.find('#wl_info_color').val() || '');
                formData.set('wl_dashboard_title', $form.find('#wl_dashboard_title').val() || '');
                formData.set('wl_welcome_message', $form.find('#wl_welcome_message').val() || '');
                formData.set('wl_footer_text', $form.find('#wl_footer_text').val() || '');
                formData.set('wl_copyright_text', $form.find('#wl_copyright_text').val() || '');
                formData.set('wl_support_email', $form.find('#wl_support_email').val() || '');
                formData.set('wl_support_phone', $form.find('#wl_support_phone').val() || '');
                formData.set('wl_support_url', $form.find('#wl_support_url').val() || '');
                formData.set('font_family', $form.find('#font_family').val() || '');
                formData.set('base_font_size', $form.find('#base_font_size').val() || '');
                formData.set('default_theme', $form.find('input[name="default_theme"]:checked').val() || '');
                formData.set('wl_custom_css', $form.find('#wl_custom_css').val() || '');
                formData.set('wl_custom_js', $form.find('#wl_custom_js').val() || '');
                formData.set('wl_hide_branding', $form.find('#wl_hide_branding').is(':checked') ? '1' : '0');
                
                console.log('=== AFTER FormData Setup ===');
                console.log('wl_company_logo:', formData.get('wl_company_logo'));
                
                var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                if (typeof window.puzzlingcrm_ajax_obj !== 'undefined' && window.puzzlingcrm_ajax_obj.ajax_url) {
                    ajaxUrl = window.puzzlingcrm_ajax_obj.ajax_url;
                }
                
                console.log('AJAX URL:', ajaxUrl);
                
                $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> در حال ذخیره...');
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json', // Explicitly expect JSON response
                    success: function(response) {
                        console.log('=== AJAX SUCCESS CALLBACK (jQuery) ===');
                        console.log('Full Response:', response);
                        console.log('Response Type:', typeof response);
                        console.log('Response.success:', response ? response.success : 'undefined');
                        console.log('Response.data:', response ? response.data : 'undefined');
                        
                        $btn.prop('disabled', false).html(originalButtonHtml);
                        
                        if (response && response.success) {
                            console.log('Response indicates success!');
                            Swal.fire({
                                icon: 'success',
                                title: 'موفق',
                                text: (response.data && response.data.message) ? response.data.message : 'تنظیمات با موفقیت ذخیره شد.',
                                confirmButtonText: 'باشه'
                            }).then(function() {
                                if (response.data && response.data.reload) {
                                    console.log('Reloading page...');
                                    location.reload();
                                }
                            });
                        } else {
                            console.error('Response indicates failure:', response);
                            var errorMsg = 'خطا در ذخیره تنظیمات.';
                            if (response && response.data && response.data.message) {
                                errorMsg = response.data.message;
                            }
                            Swal.fire({icon: 'error', title: 'خطا', text: errorMsg, confirmButtonText: 'باشه'});
                        }
                    },
                    error: function(xhr, status, error) {
                        $btn.prop('disabled', false).html(originalButtonHtml);
                        
                        console.error('=== AJAX ERROR CALLBACK (jQuery) ===');
                        console.error('Status:', status);
                        console.error('Error:', error);
                        console.error('Status Code:', xhr.status);
                        console.error('Status Text:', xhr.statusText);
                        console.error('Response Text:', xhr.responseText);
                        console.error('Response JSON:', xhr.responseJSON);
                        
                        var errorMsg = 'خطا در ارتباط با سرور.';
                        try {
                            if (xhr.responseJSON && xhr.responseJSON.data) {
                                errorMsg = xhr.responseJSON.data.message || errorMsg;
                            } else if (xhr.responseText) {
                                var response = JSON.parse(xhr.responseText);
                                if (response && response.data && response.data.message) {
                                    errorMsg = response.data.message;
                                }
                            }
                        } catch(e) {
                            console.error('Failed to parse error response:', e);
                        }
                        
                        Swal.fire({icon: 'error', title: 'خطا', html: '<div>' + errorMsg + '</div><small>کد: ' + xhr.status + '</small>', confirmButtonText: 'باشه'});
                    }
                });
                
                return false;
            });
            
            $styleForm.data('handler-registered', true);
            console.log('Form handler registered successfully (jQuery backup)');
        } else {
            console.log('Form handler already registered');
        }
    }
    
    // Update font size display
    $('#base_font_size').on('input', function() {
        $('#font_size_value').text($(this).val() + 'px');
    });
    
    // Color picker change - update text and preview
    $('.color-input').on('change', function() {
        var $textInput = $(this).closest('.d-flex').find('.color-text');
        var $preview = $(this).closest('.col-md-6').find('.color-preview');
        var colorValue = $(this).val();
        
        $textInput.val(colorValue);
        $preview.css('background', colorValue);
    });
    
    // Logo upload button
    $('.logo-upload-btn').on('click', function(e) {
        e.preventDefault();
        
        // Check if wp.media is available
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            console.error('wp.media is not available. Please ensure WordPress media uploader is loaded.');
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: 'سیستم آپلود تصاویر در دسترس نیست. لطفاً صفحه را رفرش کنید.',
                confirmButtonText: 'باشه'
            });
            return false;
        }
        
        var targetId = $(this).data('target');
        var button = $(this);
        
        console.log('Logo upload button clicked. Target:', targetId);
        console.log('wp.media available:', typeof wp !== 'undefined' && typeof wp.media !== 'undefined');
        
        var mediaUploader = wp.media({
            title: 'انتخاب تصویر',
            button: {
                text: 'انتخاب'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var logoUrl = attachment.url;
            
            console.log('Logo selected:', logoUrl, 'Target field:', targetId);
            
            // Update hidden input field
            var $inputField = $('#' + targetId);
            $inputField.val(logoUrl);
            
            console.log('Input field updated. New value:', $inputField.val());
            
            // Update preview
            var $preview = button.closest('.logo-upload-wrapper').find('.logo-preview');
            $preview.html('<img src="' + logoUrl + '" alt="Logo" style="max-width: 100%; max-height: 100px; padding: 10px;">');
            
            // Show remove button if hidden
            var $removeBtn = button.closest('.logo-upload-wrapper').find('.logo-remove-btn');
            if ($removeBtn.length === 0) {
                button.after('<button type="button" class="btn btn-danger btn-sm logo-remove-btn" data-target="' + targetId + '"><i class="ri-delete-bin-line me-1"></i> حذف</button>');
            }
        });
        
        mediaUploader.open();
    });
    
    // Logo remove button
    $(document).on('click', '.logo-remove-btn', function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var $wrapper = $(this).closest('.logo-upload-wrapper');
        
        $('#' + targetId).val('');
        $wrapper.find('.logo-preview').html('<span class="text-muted"><i class="ri-image-add-line fs-3"></i><br>لوگو انتخاب نشده</span>');
        $(this).remove();
    });
    
    // Note: Form submission handler is already registered at the beginning of this script
});
</script>

<style>
/* استایل خاص برای این صفحه */
.theme-option {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid #e9ecef;
    margin-bottom: 15px;
}

.theme-option:hover {
    border-color: var(--puzzling-primary-color, #845adf);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(132, 90, 223, 0.15);
}

.theme-option input:checked ~ label {
    color: var(--puzzling-primary-color, #845adf);
}

.theme-option input:checked {
    border-color: var(--puzzling-primary-color, #845adf);
}

.form-check-input {
    margin-top: 0;
}

.form-check-label {
    width: 100%;
    cursor: pointer;
    text-align: center;
}

.form-control-color {
    width: 60px;
    height: 45px;
    padding: 4px;
    cursor: pointer;
}

.color-preview {
    transition: background-color 0.3s ease;
}

.logo-preview {
    transition: all 0.3s ease;
}

.logo-preview img {
    transition: all 0.3s ease;
}

.code {
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

.nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #6c757d;
    padding: 12px 20px;
}

.nav-tabs .nav-link:hover {
    border-bottom-color: var(--puzzling-primary-color, #845adf);
    color: var(--puzzling-primary-color, #845adf);
}

.nav-tabs .nav-link.active {
    border-bottom-color: var(--puzzling-primary-color, #845adf);
    color: var(--puzzling-primary-color, #845adf);
    background: transparent;
}

/* استایل‌های دکمه‌ها - رفع مشکل رنگ سبز */
#puzzling-style-settings-form .btn-primary {
    background-color: <?php echo esc_attr($primary_color); ?> !important;
    border-color: <?php echo esc_attr($primary_color); ?> !important;
    color: #fff !important;
}

#puzzling-style-settings-form .btn-primary:hover,
#puzzling-style-settings-form .btn-primary:focus,
#puzzling-style-settings-form .btn-primary:active {
    background-color: <?php echo esc_attr($primary_color); ?> !important;
    border-color: <?php echo esc_attr($primary_color); ?> !important;
    opacity: 0.9;
    color: #fff !important;
}

#puzzling-style-settings-form .btn-secondary {
    background-color: <?php echo esc_attr($secondary_color); ?> !important;
    border-color: <?php echo esc_attr($secondary_color); ?> !important;
    color: #fff !important;
}

#puzzling-style-settings-form .btn-secondary:hover,
#puzzling-style-settings-form .btn-secondary:focus,
#puzzling-style-settings-form .btn-secondary:active {
    background-color: <?php echo esc_attr($secondary_color); ?> !important;
    border-color: <?php echo esc_attr($secondary_color); ?> !important;
    opacity: 0.9;
    color: #fff !important;
}

#puzzling-style-settings-form .btn-danger {
    background-color: <?php echo esc_attr($danger_color); ?> !important;
    border-color: <?php echo esc_attr($danger_color); ?> !important;
    color: #fff !important;
}

#puzzling-style-settings-form .btn-danger:hover,
#puzzling-style-settings-form .btn-danger:focus,
#puzzling-style-settings-form .btn-danger:active {
    background-color: <?php echo esc_attr($danger_color); ?> !important;
    border-color: <?php echo esc_attr($danger_color); ?> !important;
    opacity: 0.9;
    color: #fff !important;
}

#puzzling-style-settings-form .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
}

#puzzling-style-settings-form .btn-lg {
    padding: 0.5rem 1rem;
    font-size: 1.125rem;
    border-radius: 0.375rem;
}
</style>
