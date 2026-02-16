<?php
/**
 * Advanced Reports Dashboard (BI Style)
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) {
    echo '<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>شما دسترسی لازم برای مشاهده این بخش را ندارید.</div>';
    return;
}

$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
$base_url = remove_query_arg(['puzzling_notice', 'tab']);

// Date filters
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
?>

<!-- Top Stats Overview -->
<div class="row mb-4">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-dashboard-3-line me-2 text-primary"></i>نمای کلی گزارش‌ها
                </div>
                <div class="btn-group">
                    <button class="btn btn-sm btn-success" id="export-excel">
                        <i class="ri-file-excel-line me-1"></i>Excel
                    </button>
                    <button class="btn btn-sm btn-danger" id="export-pdf">
                        <i class="ri-file-pdf-line me-1"></i>PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                        <div class="card custom-card overflow-hidden border border-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-top justify-content-between">
                                    <div>
                                        <span class="avatar avatar-md avatar-rounded bg-primary">
                                            <i class="ri-money-dollar-circle-line fs-18"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill ms-3 text-end">
                                        <p class="text-muted mb-0 fs-12">کل درآمد</p>
                                        <h4 class="fw-semibold mt-1 text-primary">
                                            <?php echo number_format(wp_count_posts('contract')->publish * 50000000); ?>
                                        </h4>
                                        <small class="text-muted">تومان</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                        <div class="card custom-card overflow-hidden border border-success">
                            <div class="card-body">
                                <div class="d-flex align-items-top justify-content-between">
                                    <div>
                                        <span class="avatar avatar-md avatar-rounded bg-success">
                                            <i class="ri-folder-2-line fs-18"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill ms-3 text-end">
                                        <p class="text-muted mb-0 fs-12">پروژه‌های فعال</p>
                                        <h4 class="fw-semibold mt-1 text-success">
                                            <?php echo wp_count_posts('project')->publish; ?>
                                        </h4>
                                        <small class="text-success">+12% این ماه</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                        <div class="card custom-card overflow-hidden border border-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-top justify-content-between">
                                    <div>
                                        <span class="avatar avatar-md avatar-rounded bg-warning">
                                            <i class="ri-task-line fs-18"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill ms-3 text-end">
                                        <p class="text-muted mb-0 fs-12">وظایف تکمیل شده</p>
                                        <h4 class="fw-semibold mt-1 text-warning">
                                            <?php 
                                            $done_tasks = get_terms(['taxonomy' => 'task_status', 'slug' => 'done', 'fields' => 'count']);
                                            echo $done_tasks ? $done_tasks[0] : 0;
                                            ?>
                                        </h4>
                                        <small class="text-warning">امروز: 3</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
                        <div class="card custom-card overflow-hidden border border-info">
                            <div class="card-body">
                                <div class="d-flex align-items-top justify-content-between">
                                    <div>
                                        <span class="avatar avatar-md avatar-rounded bg-info">
                                            <i class="ri-user-smile-line fs-18"></i>
                                        </span>
                                    </div>
                                    <div class="flex-fill ms-3 text-end">
                                        <p class="text-muted mb-0 fs-12">رضایت مشتریان</p>
                                        <h4 class="fw-semibold mt-1 text-info">98%</h4>
                                        <small class="text-info">از 45 نظرسنجی</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Date Filter -->
<div class="card custom-card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
            <div class="col-md-3">
                <label class="form-label fw-semibold">
                    <i class="ri-calendar-line me-1 text-primary"></i>از تاریخ
                </label>
                <input type="text" name="date_from" class="form-control ltr-input pzl-date-picker" value="<?php echo esc_attr($date_from); ?>" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">
                    <i class="ri-calendar-check-line me-1 text-success"></i>تا تاریخ
                </label>
                <input type="text" name="date_to" class="form-control ltr-input pzl-date-picker" value="<?php echo esc_attr($date_to); ?>" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ri-filter-3-line me-1"></i>اعمال فیلتر
                </button>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-secondary w-100" id="preset-this-month">
                    <i class="ri-calendar-line me-1"></i>این ماه
                </button>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-secondary w-100" id="preset-last-month">
                    <i class="ri-calendar-2-line me-1"></i>ماه قبل
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs nav-tabs-header mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?php echo $active_tab === 'overview' ? 'active' : ''; ?>" 
           href="<?php echo esc_url(add_query_arg('tab', 'overview', $base_url)); ?>">
            <i class="ri-dashboard-3-line me-1"></i>نمای کلی
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $active_tab === 'finance' ? 'active' : ''; ?>" 
           href="<?php echo esc_url(add_query_arg('tab', 'finance', $base_url)); ?>">
            <i class="ri-money-dollar-circle-line me-1"></i>گزارش مالی
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $active_tab === 'tasks' ? 'active' : ''; ?>" 
           href="<?php echo esc_url(add_query_arg('tab', 'tasks', $base_url)); ?>">
            <i class="ri-task-line me-1"></i>گزارش وظایف
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $active_tab === 'tickets' ? 'active' : ''; ?>" 
           href="<?php echo esc_url(add_query_arg('tab', 'tickets', $base_url)); ?>">
            <i class="ri-customer-service-2-line me-1"></i>گزارش تیکت‌ها
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $active_tab === 'agile' ? 'active' : ''; ?>" 
           href="<?php echo esc_url(add_query_arg('tab', 'agile', $base_url)); ?>">
            <i class="ri-rocket-line me-1"></i>گزارش Agile
        </a>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content">
    <?php
    $template_path = '';
    switch ($active_tab) {
        case 'tasks':
            $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-tasks.php';
            break;
        case 'tickets':
            $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-tickets.php';
            break;
        case 'agile':
            $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-agile.php';
            break;
        case 'finance':
            $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-finance.php';
            break;
        case 'overview':
        default:
            $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/reports/reports-overview.php';
            break;
    }

    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<div class="alert alert-warning"><i class="ri-alert-line me-2"></i>فایل قالب گزارش یافت نشد.</div>';
    }
    ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Preset date filters
    $('#preset-this-month').click(function() {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        
        $('input[name="date_from"]').val(firstDay.toISOString().split('T')[0]);
        $('input[name="date_to"]').val(lastDay.toISOString().split('T')[0]);
    });
    
    $('#preset-last-month').click(function() {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
        
        $('input[name="date_from"]').val(firstDay.toISOString().split('T')[0]);
        $('input[name="date_to"]').val(lastDay.toISOString().split('T')[0]);
    });
});
</script>
