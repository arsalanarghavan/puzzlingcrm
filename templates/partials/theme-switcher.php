<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<!-- Theme Switcher -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="switcher-canvas" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header border-bottom d-block p-0">
        <div class="d-flex align-items-center justify-content-between p-3">
            <h5 class="offcanvas-title text-default" id="offcanvasRightLabel">تنظیمات سریع نمایش</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
        </div>
        <nav class="border-top border-block-start-dashed">
            <div class="nav nav-tabs nav-justified" id="switcher-main-tab" role="tablist">
                <button class="nav-link active" id="switcher-home-tab" data-bs-toggle="tab" data-bs-target="#switcher-home" type="button" role="tab" aria-controls="switcher-home" aria-selected="true">تنظیمات قالب</button>
                <button class="nav-link" id="switcher-profile-tab" data-bs-toggle="tab" data-bs-target="#switcher-profile" type="button" role="tab" aria-controls="switcher-profile" aria-selected="false">رنگ قالب</button>
            </div>
        </nav>
    </div>
    <div class="offcanvas-body">
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active border-0" id="switcher-home" role="tabpanel" aria-labelledby="switcher-home-tab" tabindex="0">
                
                <!-- حالت رنگ قالب -->
                <div class="mb-4">
                    <p class="switcher-style-head">حالت رنگ قالب:</p>
                    <div class="row switcher-style gx-0">
                        <div class="col-4">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-light-theme">
                                    روشن
                                </label>
                                <input class="form-check-input" type="radio" name="theme-style" id="switcher-light-theme" checked>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-dark-theme">
                                    تیره
                                </label>
                                <input class="form-check-input" type="radio" name="theme-style" id="switcher-dark-theme">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- قالب ناوبری -->
                <div class="mb-4">
                    <p class="switcher-style-head">قالب ناوبری:</p>
                    <div class="row switcher-style gx-0">
                        <div class="col-4">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-vertical">
                                    عمودی
                                </label>
                                <input class="form-check-input" type="radio" name="navigation-style" id="switcher-vertical" checked>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-horizontal">
                                    افقی
                                </label>
                                <input class="form-check-input" type="radio" name="navigation-style" id="switcher-horizontal">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- سبک های چیدمان منوی جانبی -->
                <div class="sidemenu-layout-styles mb-4">
                    <p class="switcher-style-head">سبک های چیدمان منوی جانبی:</p>
                    <div class="row switcher-style gx-0 pb-2 gy-2">
                        <div class="col-sm-6">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-default-menu">
                                    پیش فرض
                                </label>
                                <input class="form-check-input" type="radio" name="sidemenu-layout-styles" id="switcher-default-menu" checked>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-closed-menu">
                                    منو بسته
                                </label>
                                <input class="form-check-input" type="radio" name="sidemenu-layout-styles" id="switcher-closed-menu">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-icontext-menu">
                                    آیکن متنی
                                </label>
                                <input class="form-check-input" type="radio" name="sidemenu-layout-styles" id="switcher-icontext-menu">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-icon-overlay">
                                    آیکن
                                </label>
                                <input class="form-check-input" type="radio" name="sidemenu-layout-styles" id="switcher-icon-overlay">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- موقعیت های منو -->
                <div class="mb-4">
                    <p class="switcher-style-head">موقعیت های منو:</p>
                    <div class="row switcher-style gx-0">
                        <div class="col-4">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-menu-fixed">
                                    ثابت
                                </label>
                                <input class="form-check-input" type="radio" name="menu-positions" id="switcher-menu-fixed" checked>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-menu-scroll">
                                    قابل پیمایش
                                </label>
                                <input class="form-check-input" type="radio" name="menu-positions" id="switcher-menu-scroll">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- موقعیت های سرصفحه -->
                <div class="mb-4">
                    <p class="switcher-style-head">موقعیت های سرصفحه:</p>
                    <div class="row switcher-style gx-0">
                        <div class="col-4">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-header-fixed">
                                    ثابت
                                </label>
                                <input class="form-check-input" type="radio" name="header-positions" id="switcher-header-fixed" checked>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check switch-select">
                                <label class="form-check-label" for="switcher-header-scroll">
                                    قابل پیمایش
                                </label>
                                <input class="form-check-input" type="radio" name="header-positions" id="switcher-header-scroll">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <!-- تب رنگ قالب -->
            <div class="tab-pane fade border-0" id="switcher-profile" role="tabpanel" aria-labelledby="switcher-profile-tab" tabindex="0">
                
                <!-- رنگ های منو -->
                <div class="theme-colors mb-4">
                    <p class="switcher-style-head">رنگ های منو:</p>
                    <div class="d-flex switcher-style pb-2">
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-white" data-bs-toggle="tooltip" data-bs-placement="top" title="منوی روشن" type="radio" name="menu-colors" id="switcher-menu-light">
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-dark" data-bs-toggle="tooltip" data-bs-placement="top" title="منوی تیره" type="radio" name="menu-colors" id="switcher-menu-dark" checked>
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="منوی رنگی" type="radio" name="menu-colors" id="switcher-menu-primary">
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-gradient" data-bs-toggle="tooltip" data-bs-placement="top" title="منوی گرادیانت" type="radio" name="menu-colors" id="switcher-menu-gradient">
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-transparent" data-bs-toggle="tooltip" data-bs-placement="top" title="منوی شفاف" type="radio" name="menu-colors" id="switcher-menu-transparent">
                        </div>
                    </div>
                </div>

                <!-- رنگ‌های سرصفحه -->
                <div class="theme-colors mb-4">
                    <p class="switcher-style-head">رنگ‌های سرصفحه:</p>
                    <div class="d-flex switcher-style pb-2">
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-white" data-bs-toggle="tooltip" data-bs-placement="top" title="هدر روشن" type="radio" name="header-colors" id="switcher-header-light" checked>
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-dark" data-bs-toggle="tooltip" data-bs-placement="top" title="هدر تیره" type="radio" name="header-colors" id="switcher-header-dark">
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="هدر رنگی" type="radio" name="header-colors" id="switcher-header-primary">
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-gradient" data-bs-toggle="tooltip" data-bs-placement="top" title="هدر گرادیانت" type="radio" name="header-colors" id="switcher-header-gradient">
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-transparent" data-bs-toggle="tooltip" data-bs-placement="top" title="هدر شفاف" type="radio" name="header-colors" id="switcher-header-transparent">
                        </div>
                    </div>
                </div>

                <!-- رنگ اصلی -->
                <div class="theme-colors mb-4">
                    <p class="switcher-style-head">رنگ اصلی قالب:</p>
                    <div class="d-flex flex-wrap align-items-center switcher-style">
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-primary-1" type="radio" name="theme-primary" id="switcher-primary" checked>
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-primary-2" type="radio" name="theme-primary" id="switcher-primary1">
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-primary-3" type="radio" name="theme-primary" id="switcher-primary2">
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-primary-4" type="radio" name="theme-primary" id="switcher-primary3">
                        </div>
                        <div class="form-check switch-select me-3">
                            <input class="form-check-input color-input color-primary-5" type="radio" name="theme-primary" id="switcher-primary4">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- دکمه بازنشانی -->
        <div class="d-flex justify-content-center mt-4">
            <button type="button" id="reset-all" class="btn btn-danger">
                <i class="ri-refresh-line"></i> بازنشانی تنظیمات
            </button>
        </div>
    </div>
</div>
