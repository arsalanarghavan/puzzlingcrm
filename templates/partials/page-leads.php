<?php
/**
 * Leads Management Page (Xintra Style)
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options') && !current_user_can('system_manager')) {
    wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'puzzlingcrm'));
}

// Determine view
$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$lead_id = isset($_GET['lead_id']) ? intval($_GET['lead_id']) : 0;

// ========== EDIT FORM ==========
if ($action === 'edit' && $lead_id > 0) {
    $lead = get_post($lead_id);
    if (!$lead || $lead->post_type !== 'pzl_lead') {
        echo '<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>سرنخ مورد نظر یافت نشد. <a href="' . esc_url(remove_query_arg(['action', 'lead_id'])) . '">بازگشت به لیست</a></div>';
        return;
    }
    
    $first_name = get_post_meta($lead_id, '_first_name', true);
    $last_name = get_post_meta($lead_id, '_last_name', true);
    $mobile = get_post_meta($lead_id, '_mobile', true);
    $business_name = get_post_meta($lead_id, '_business_name', true);
    $gender = get_post_meta($lead_id, '_gender', true);
    $notes = $lead->post_content;
    $return_url = remove_query_arg(['action', 'lead_id']);
    ?>
    
    <!-- Edit Form -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="ri-user-edit-line me-2"></i>ویرایش سرنخ: <?php echo esc_html($first_name . ' ' . $last_name); ?>
        </h4>
        <a href="<?php echo esc_url($return_url); ?>" class="btn btn-secondary btn-sm">
            <i class="ri-arrow-right-line me-1"></i>بازگشت
        </a>
    </div>

    <div class="card custom-card">
        <div class="card-body">
            <form id="pzl-edit-lead-form" class="pzl-ajax-form">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="action" value="puzzling_edit_lead">
                <input type="hidden" name="lead_id" value="<?php echo esc_attr($lead_id); ?>">
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr(wp_unslash($return_url)); ?>">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="pzl-first-name-edit" class="form-label fw-semibold">
                            <i class="ri-user-line me-1 text-primary"></i>نام <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="pzl-first-name-edit" name="first_name" class="form-control" value="<?php echo esc_attr($first_name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="pzl-last-name-edit" class="form-label fw-semibold">
                            <i class="ri-user-line me-1 text-primary"></i>نام خانوادگی <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="pzl-last-name-edit" name="last_name" class="form-control" value="<?php echo esc_attr($last_name); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="pzl-mobile-edit" class="form-label fw-semibold">
                            <i class="ri-phone-line me-1 text-success"></i>شماره موبایل <span class="text-danger">*</span>
                        </label>
                        <input type="tel" id="pzl-mobile-edit" name="mobile" class="form-control ltr-input" value="<?php echo esc_attr($mobile); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="pzl-business-name-edit" class="form-label fw-semibold">
                            <i class="ri-building-line me-1 text-info"></i>نام کسب‌وکار
                        </label>
                        <input type="text" id="pzl-business-name-edit" name="business_name" class="form-control" value="<?php echo esc_attr($business_name); ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="pzl-gender-edit" class="form-label fw-semibold">
                            <i class="ri-user-3-line me-1 text-secondary"></i>جنسیت
                        </label>
                        <select id="pzl-gender-edit" name="gender" class="form-select">
                            <option value="">انتخاب کنید</option>
                            <option value="male" <?php selected($gender, 'male'); ?>>آقا</option>
                            <option value="female" <?php selected($gender, 'female'); ?>>خانم</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="pzl-lead-status-edit" class="form-label fw-semibold">
                            <i class="ri-flag-line me-1 text-warning"></i>وضعیت سرنخ
                        </label>
                        <select id="pzl-lead-status-edit" name="lead_status" class="form-select">
                            <?php
                            $current_status_terms = wp_get_object_terms($lead_id, 'lead_status', ['fields' => 'slugs']);
                            $current_status = !empty($current_status_terms) ? $current_status_terms[0] : '';
                            $all_statuses = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false]);

                            if (empty($all_statuses)) {
                                echo '<option value="">هیچ وضعیتی تعریف نشده است</option>';
                            } else {
                                foreach ($all_statuses as $status) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($status->slug),
                                        selected($current_status, $status->slug, false),
                                        esc_html($status->name)
                                    );
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="pzl-notes-edit" class="form-label fw-semibold">
                        <i class="ri-file-text-line me-1 text-secondary"></i>یادداشت
                    </label>
                    <textarea id="pzl-notes-edit" name="notes" rows="4" class="form-control"><?php echo esc_textarea($notes); ?></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="ri-save-line me-2"></i>ذخیره تغییرات
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
    return;
}

// ========== LIST VIEW ==========
$lead_statuses = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false]);
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

$args = [
    'post_type' => 'pzl_lead',
    'post_status' => 'publish',
    'posts_per_page' => 20,
    'paged' => $paged,
    's' => $search_query
];

if (!empty($status_filter)) {
    $args['tax_query'] = [['taxonomy' => 'lead_status', 'field' => 'slug', 'terms' => $status_filter]];
}

$leads_query = new WP_Query($args);
?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-primary">
                            <i class="ri-user-add-line fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <p class="text-muted mb-0">کل سرنخ‌ها</p>
                        <h4 class="fw-semibold mt-1"><?php echo wp_count_posts('pzl_lead')->publish; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php foreach (array_slice($lead_statuses, 0, 3) as $index => $status):
        $colors = ['success', 'warning', 'info'];
        $icons = ['ri-user-follow-line', 'ri-time-line', 'ri-user-star-line'];
        $color = $colors[$index] ?? 'secondary';
        $icon = $icons[$index] ?? 'ri-user-line';
    ?>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card custom-card overflow-hidden">
            <div class="card-body">
                <div class="d-flex align-items-top justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-<?php echo $color; ?>">
                            <i class="<?php echo $icon; ?> fs-18"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <p class="text-muted mb-0"><?php echo esc_html($status->name); ?></p>
                        <h4 class="fw-semibold mt-1"><?php echo $status->count; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Header with Button -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLeadModal">
        <i class="ri-user-add-line me-1"></i>افزودن سرنخ جدید
    </button>
</div>

<!-- Filters Card -->
<div class="card custom-card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3">
            <input type="hidden" name="page" value="puzzling-leads">
            <div class="col-md-6">
                <label class="form-label">جستجو</label>
                <input type="text" name="s" class="form-control" placeholder="نام، موبایل یا نام کسب‌وکار..." value="<?php echo esc_attr($search_query); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">وضعیت</label>
                <select name="status_filter" class="form-select">
                    <option value="">همه وضعیت‌ها</option>
                    <?php foreach ($lead_statuses as $status): ?>
                    <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($status_filter, $status->slug); ?>>
                        <?php echo esc_html($status->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ri-filter-3-line"></i> فیلتر
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Leads Table -->
<div class="card custom-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table text-nowrap">
                <thead>
                    <tr>
                        <th>نام</th>
                        <th>موبایل</th>
                        <th>کسب‌وکار</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($leads_query->have_posts()): 
                        while ($leads_query->have_posts()): $leads_query->the_post();
                            $lead_id = get_the_ID();
                            $first_name = get_post_meta($lead_id, '_first_name', true);
                            $last_name = get_post_meta($lead_id, '_last_name', true);
                            $mobile = get_post_meta($lead_id, '_mobile', true);
                            $business_name = get_post_meta($lead_id, '_business_name', true);
                            $status_terms = wp_get_object_terms($lead_id, 'lead_status');
                            $status_name = !empty($status_terms) && !is_wp_error($status_terms) ? $status_terms[0]->name : 'نامشخص';
                            $status_slug = !empty($status_terms) && !is_wp_error($status_terms) ? $status_terms[0]->slug : '';
                            
                            $badge_class = 'bg-secondary';
                            if ($status_slug === 'new') $badge_class = 'bg-primary';
                            elseif ($status_slug === 'contacted') $badge_class = 'bg-info';
                            elseif ($status_slug === 'qualified') $badge_class = 'bg-success';
                            elseif ($status_slug === 'converted') $badge_class = 'bg-success';
                            elseif ($status_slug === 'lost') $badge_class = 'bg-danger';
                    ?>
                    <tr>
                        <td class="fw-semibold"><?php echo esc_html($first_name . ' ' . $last_name); ?></td>
                        <td class="ltr-input"><?php echo esc_html($mobile); ?></td>
                        <td><?php echo $business_name ? esc_html($business_name) : '<span class="text-muted">-</span>'; ?></td>
                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo esc_html($status_name); ?></span></td>
                        <td><?php echo jdate('Y/m/d', strtotime(get_the_date('Y-m-d'))); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'edit', 'lead_id' => $lead_id])); ?>" class="btn btn-sm btn-icon btn-primary-light" title="ویرایش">
                                    <i class="ri-edit-line"></i>
                                </a>
                                <button class="btn btn-sm btn-icon btn-danger-light pzl-delete-lead" 
                                        data-lead-id="<?php echo esc_attr($lead_id); ?>" 
                                        data-nonce="<?php echo wp_create_nonce('puzzlingcrm-ajax-nonce'); ?>" 
                                        title="حذف">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                        wp_reset_postdata();
                    else: 
                    ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="ri-user-add-line fs-3 mb-3 d-block opacity-3"></i>
                            <p class="text-muted">هیچ سرنخی یافت نشد</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($leads_query->max_num_pages > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-center">
            <?php
            echo paginate_links([
                'total' => $leads_query->max_num_pages,
                'current' => $paged,
                'format' => '?paged=%#%',
                'prev_text' => '<i class="ri-arrow-right-s-line"></i>',
                'next_text' => '<i class="ri-arrow-left-s-line"></i>',
            ]);
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Lead Modal -->
<div class="modal fade" id="addLeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="ri-user-add-line me-2"></i>افزودن سرنخ جدید
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="pzl-add-lead-form" class="pzl-ajax-form">
                    <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                    <input type="hidden" name="action" value="puzzling_add_lead">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">نام <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">نام خانوادگی <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">شماره موبایل <span class="text-danger">*</span></label>
                            <input type="tel" name="mobile" class="form-control ltr-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">نام کسب‌وکار</label>
                            <input type="text" name="business_name" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">جنسیت</label>
                            <select name="gender" class="form-select">
                                <option value="">انتخاب کنید</option>
                                <option value="male">آقا</option>
                                <option value="female">خانم</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">وضعیت سرنخ</label>
                            <select name="lead_status" class="form-select">
                                <?php foreach ($lead_statuses as $status): ?>
                                <option value="<?php echo esc_attr($status->slug); ?>">
                                    <?php echo esc_html($status->name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">یادداشت</label>
                        <textarea name="notes" rows="3" class="form-control"></textarea>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-add-line me-1"></i>افزودن سرنخ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
