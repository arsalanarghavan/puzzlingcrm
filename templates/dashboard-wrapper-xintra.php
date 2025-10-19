<!DOCTYPE html>
<html lang="fa" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close" <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="Description" content="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>">
    
    <title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> | داشبورد</title>
    
    <?php wp_head(); ?>
</head>

<body class="<?php echo esc_attr( implode( ' ', get_body_class() ) ); ?>">

    <?php
    // بررسی ورود کاربر
    if ( ! is_user_logged_in() ) {
        echo '<div class="container mt-5">';
        echo '<div class="alert alert-warning"><i class="fas fa-lock"></i> لطفاً برای دسترسی به داشبورد وارد شوید.</div>';
        wp_login_form();
        echo '</div>';
        wp_footer();
        echo '</body></html>';
        return;
    }

    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;
    
    // تشخیص نقش کاربر
    $user_role = 'guest';
    if ( in_array( 'administrator', $user_roles ) || in_array( 'system_manager', $user_roles ) ) {
        $user_role = 'system_manager';
    } elseif ( in_array( 'finance_manager', $user_roles ) ) {
        $user_role = 'finance_manager';
    } elseif ( in_array( 'team_member', $user_roles ) ) {
        $user_role = 'team_member';
    } elseif ( in_array( 'customer', $user_roles ) ) {
        $user_role = 'customer';
    }

    // دریافت تنظیمات استایل
    $style_settings = PuzzlingCRM_Settings_Handler::get_setting('style', []);
    $logo_desktop = isset($style_settings['logo_desktop']) ? $style_settings['logo_desktop'] : PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/desktop-logo.png';
    $logo_mobile = isset($style_settings['logo_mobile']) ? $style_settings['logo_mobile'] : PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/toggle-logo.png';
    $logo_dark = isset($style_settings['logo_dark']) ? $style_settings['logo_dark'] : PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/desktop-dark.png';
    $primary_color = isset($style_settings['primary_color']) ? $style_settings['primary_color'] : '#6366f1';
    
    // لینک داشبورد اصلی
    $dashboard_url = home_url('/dashboard');
    ?>

    <!-- Loader -->
    <div id="loader">
        <img src="<?php echo esc_url(PUZZLINGCRM_PLUGIN_URL . 'assets/xintra/images/brand-logos/desktop-logo.png'); ?>" alt="بارگذاری...">
    </div>

    <div class="page">
        <!-- Start::main-header -->
        <header class="app-header sticky" id="header">
            <div class="main-header-container container-fluid">
                
                <!-- Start::header-content-left -->
                <div class="header-content-left">
                    
                    <!-- Logo -->
                    <div class="header-element">
                        <div class="horizontal-logo">
                            <a href="<?php echo esc_url($dashboard_url); ?>" class="header-logo">
                                <img src="<?php echo esc_url($logo_desktop); ?>" alt="لوگو" class="desktop-logo">
                                <img src="<?php echo esc_url($logo_mobile); ?>" alt="لوگو" class="toggle-dark">
                                <img src="<?php echo esc_url($logo_dark); ?>" alt="لوگو" class="desktop-dark">
                                <img src="<?php echo esc_url($logo_mobile); ?>" alt="لوگو" class="toggle-logo">
                                <img src="<?php echo esc_url($logo_mobile); ?>" alt="لوگو" class="toggle-white">
                                <img src="<?php echo esc_url($logo_desktop); ?>" alt="لوگو" class="desktop-white">
                            </a>
                        </div>
                    </div>

                    <!-- Toggle Sidebar -->
                    <div class="header-element mx-lg-0 mx-2">
                        <a aria-label="Toggle Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle" data-bs-toggle="sidebar" href="javascript:void(0);">
                            <span></span>
                        </a>
                    </div>

                    <!-- Search Bar -->
                    <div class="header-element header-search d-md-block d-none my-auto">
                        <input type="text" class="header-search-bar form-control" id="header-search" placeholder="جستجو در سیستم..." autocomplete="off">
                        <a href="javascript:void(0);" class="header-search-icon border-0">
                            <i class="ri-search-line"></i>
                        </a>
                    </div>

                </div>
                <!-- End::header-content-left -->

                <!-- Start::header-content-right -->
                <ul class="header-content-right">

                    <!-- Theme Mode Toggle -->
                    <li class="header-element header-theme-mode">
                        <a href="javascript:void(0);" class="header-link layout-setting">
                            <span class="light-layout">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"></path>
                                </svg>
                            </span>
                            <span class="dark-layout">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"></path>
                                </svg>
                            </span>
                        </a>
                    </li>

                    <!-- Notifications -->
                    <li class="header-element notifications-dropdown dropdown">
                        <a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" id="notificationDropdown" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"></path>
                            </svg>
                            <span class="header-icon-badge pulse pulse-secondary" id="notification-icon-badge"></span>
                        </a>
                        <div class="main-header-dropdown dropdown-menu dropdown-menu-end" data-popper-placement="none">
                            <div class="p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-0 fs-16">اعلان‌ها</p>
                                    <span class="badge bg-secondary-transparent" id="notifiation-data">0 جدید</span>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <ul class="list-unstyled mb-0" id="header-notification-scroll">
                                <li class="dropdown-item text-center py-3">
                                    <span class="text-muted">در حال بارگذاری...</span>
                                </li>
                            </ul>
                            <div class="dropdown-divider"></div>
                            <div class="p-3 empty-header-item1 border-top text-center">
                                <a href="<?php echo esc_url(add_query_arg('view', 'notifications', $dashboard_url)); ?>" class="text-primary text-decoration-underline">مشاهده همه</a>
                            </div>
                        </div>
                    </li>

                    <!-- User Profile Dropdown -->
                    <li class="header-element dropdown">
                        <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <div class="me-xl-2 me-0">
                                    <?php echo get_avatar($current_user->ID, 32, '', '', ['class' => 'avatar avatar-sm avatar-rounded']); ?>
                                </div>
                                <div class="d-xl-block d-none lh-1">
                                    <span class="fw-semibold fs-13"><?php echo esc_html($current_user->display_name); ?></span>
                                </div>
                            </div>
                        </a>
                        <ul class="main-header-dropdown dropdown-menu pt-0 header-profile-dropdown dropdown-menu-end" aria-labelledby="mainHeaderProfile">
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="<?php echo esc_url(add_query_arg('view', 'my_profile', $dashboard_url)); ?>">
                                    <i class="ri-user-line fs-18 me-2 op-7"></i>پروفایل من
                                </a>
                            </li>
                            <?php if ($user_role === 'system_manager'): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="<?php echo esc_url(add_query_arg('view', 'settings', $dashboard_url)); ?>">
                                    <i class="ri-settings-3-line fs-18 me-2 op-7"></i>تنظیمات
                                </a>
                            </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="<?php echo esc_url(wp_logout_url($dashboard_url)); ?>">
                                    <i class="ri-logout-box-line fs-18 me-2 op-7"></i>خروج
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Switcher Icon -->
                    <li class="header-element">
                        <a href="javascript:void(0);" class="header-link switcher-icon" data-bs-toggle="offcanvas" data-bs-target="#switcher-canvas">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"></path>
                            </svg>
                        </a>
                    </li>

                </ul>
                <!-- End::header-content-right -->

            </div>
        </header>
        <!-- End::main-header -->

        <!-- Start::main-sidebar -->
        <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/sidebar-navigation.php'; ?>
        <!-- End::main-sidebar -->

        <!-- Start::main-content -->
        <div class="main-content app-content">
            <div class="container-fluid">

                <?php
                // نمایش پیام‌های سیستمی
                if ( isset($_GET['puzzling_notice']) ) {
                    $notice_key = sanitize_key($_GET['puzzling_notice']);
                    $messages = [
                        'security_failed' => ['type' => 'danger', 'text' => 'خطای امنیتی! درخواست شما معتبر نبود.'],
                        'permission_denied' => ['type' => 'danger', 'text' => 'شما دسترسی لازم برای انجام این کار را ندارید.'],
                        'project_created_success' => ['type' => 'success', 'text' => 'پروژه جدید با موفقیت ایجاد شد.'],
                        'project_updated_success' => ['type' => 'success', 'text' => 'پروژه با موفقیت به‌روزرسانی شد.'],
                        'project_deleted_success' => ['type' => 'success', 'text' => 'پروژه با موفقیت حذف شد.'],
                        'settings_saved' => ['type' => 'success', 'text' => 'تنظیمات با موفقیت ذخیره شد.'],
                        'style_settings_saved' => ['type' => 'success', 'text' => 'تنظیمات ظاهری با موفقیت ذخیره شد.'],
                        'payment_success' => ['type' => 'success', 'text' => 'پرداخت شما با موفقیت انجام شد.'],
                        'profile_updated_success' => ['type' => 'success', 'text' => 'پروفایل شما با موفقیت به‌روزرسانی شد.'],
                    ];

                    if (array_key_exists($notice_key, $messages)) {
                        $notice_type = esc_attr($messages[$notice_key]['type']);
                        $message = esc_html($messages[$notice_key]['text']);
                        echo "<div class='alert alert-{$notice_type} alert-dismissible fade show' role='alert'>";
                        echo $message;
                        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                        echo "</div>";
                    }
                }

                // بارگذاری محتوای اصلی بر اساس view
                $current_view = isset($_GET['view']) ? sanitize_key($_GET['view']) : '';
                
                // نقشه view ها به فایل‌های قالب
                $view_map = [
                    'my_profile' => 'page-my-profile',
                    'projects' => 'page-projects',
                    'customers' => 'page-customers',
                    'leads' => 'page-leads',
                    'staff' => 'page-staff',
                    'tasks' => 'page-tasks',
                    'contracts' => 'page-contracts',
                    'pro_invoices' => 'page-pro-invoices',
                    'invoices' => 'common/payments-table',
                    'appointments' => 'page-appointments',
                    'consultations' => 'page-consultations',
                    'tickets' => 'list-tickets',
                    'subscriptions' => 'page-subscriptions',
                    'reports' => 'page-reports',
                    'logs' => 'page-logs',
                    'settings' => 'page-settings',
                ];

                if ($current_view && isset($view_map[$current_view])) {
                    // بررسی دسترسی برای view های خاص
                    $restricted_views = ['settings', 'customers', 'staff', 'leads', 'consultations', 'subscriptions', 'logs'];
                    
                    if (in_array($current_view, $restricted_views) && $user_role !== 'system_manager') {
                        echo '<div class="alert alert-danger">شما دسترسی لازم برای مشاهده این صفحه را ندارید.</div>';
                    } else {
                        $template_file = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $view_map[$current_view] . '.php';
                        if (file_exists($template_file)) {
                            include $template_file;
                        } else {
                            echo '<div class="alert alert-warning">صفحه مورد نظر یافت نشد.</div>';
                        }
                    }
                } else {
                    // بارگذاری داشبورد پیش‌فرض بر اساس نقش کاربر
                    switch ($user_role) {
                        case 'system_manager':
                            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/dashboard-system-manager.php';
                            break;
                        case 'finance_manager':
                            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/dashboard-finance.php';
                            break;
                        case 'team_member':
                            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/dashboard-team-member.php';
                            break;
                        case 'customer':
                            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/dashboard-client.php';
                            break;
                        default:
                            echo '<div class="alert alert-warning">نقش کاربری شما برای دسترسی به داشبورد تعریف نشده است.</div>';
                    }
                }
                ?>

            </div>
        </div>
        <!-- End::main-content -->

    </div>

    <!-- Theme Switcher -->
    <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/theme-switcher.php'; ?>

    <?php wp_footer(); ?>
</body>
</html>
