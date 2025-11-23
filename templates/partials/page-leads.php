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

// Get current page URL for edit links
$current_url = home_url('/dashboard/?dashboard_page=puzzle-leads');
if (isset($_GET['pzl_page'])) {
    $current_url = add_query_arg('pzl_page', sanitize_key($_GET['pzl_page']), home_url('/dashboard/'));
}
$base_url = remove_query_arg(['action', 'lead_id', 'view', 's', 'status_filter', 'paged'], $current_url);

// ========== EDIT FORM ==========
if ($action === 'edit' && $lead_id > 0) {
    $lead = get_post($lead_id);
    if (!$lead || $lead->post_type !== 'pzl_lead') {
        echo '<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>' . __('سرنخ مورد نظر یافت نشد.', 'puzzlingcrm') . ' <a href="' . esc_url(remove_query_arg(['action', 'lead_id'])) . '">' . __('بازگشت به لیست', 'puzzlingcrm') . '</a></div>';
        return;
    }
    
    $first_name = get_post_meta($lead_id, '_first_name', true);
    $last_name = get_post_meta($lead_id, '_last_name', true);
    $mobile = get_post_meta($lead_id, '_mobile', true);
    $business_name = get_post_meta($lead_id, '_business_name', true);
    $email = get_post_meta($lead_id, '_email', true);
    $gender = get_post_meta($lead_id, '_gender', true);
    $notes = $lead->post_content;
    $return_url = remove_query_arg(['action', 'lead_id']);
    ?>
    
    <!-- Edit Form -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="ri-user-edit-line me-2"></i><?php printf(__('ویرایش سرنخ: %s', 'puzzlingcrm'), esc_html($first_name . ' ' . $last_name)); ?>
        </h4>
        <a href="<?php echo esc_url($return_url); ?>" class="btn btn-secondary btn-sm">
            <i class="ri-arrow-right-line me-1"></i><?php _e('بازگشت', 'puzzlingcrm'); ?>
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
                            <i class="ri-user-line me-1 text-primary"></i><?php _e('نام', 'puzzlingcrm'); ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="pzl-first-name-edit" name="first_name" class="form-control" value="<?php echo esc_attr($first_name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="pzl-last-name-edit" class="form-label fw-semibold">
                            <i class="ri-user-line me-1 text-primary"></i><?php _e('نام خانوادگی', 'puzzlingcrm'); ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="pzl-last-name-edit" name="last_name" class="form-control" value="<?php echo esc_attr($last_name); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="pzl-mobile-edit" class="form-label fw-semibold">
                            <i class="ri-phone-line me-1 text-success"></i><?php _e('شماره موبایل', 'puzzlingcrm'); ?> <span class="text-danger">*</span>
                        </label>
                        <input type="tel" id="pzl-mobile-edit" name="mobile" class="form-control ltr-input" value="<?php echo esc_attr($mobile); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="pzl-email-edit" class="form-label fw-semibold">
                            <i class="ri-mail-line me-1 text-info"></i><?php _e('ایمیل', 'puzzlingcrm'); ?>
                        </label>
                        <input type="email" id="pzl-email-edit" name="email" class="form-control ltr-input" value="<?php echo esc_attr($email); ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="pzl-business-name-edit" class="form-label fw-semibold">
                            <i class="ri-building-line me-1 text-info"></i><?php _e('نام کسب‌وکار', 'puzzlingcrm'); ?>
                        </label>
                        <input type="text" id="pzl-business-name-edit" name="business_name" class="form-control" value="<?php echo esc_attr($business_name); ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="pzl-gender-edit" class="form-label fw-semibold">
                            <i class="ri-user-3-line me-1 text-secondary"></i><?php _e('جنسیت', 'puzzlingcrm'); ?>
                        </label>
                        <select id="pzl-gender-edit" name="gender" class="form-select">
                            <option value=""><?php _e('انتخاب کنید', 'puzzlingcrm'); ?></option>
                            <option value="male" <?php selected($gender, 'male'); ?>><?php _e('آقا', 'puzzlingcrm'); ?></option>
                            <option value="female" <?php selected($gender, 'female'); ?>><?php _e('خانم', 'puzzlingcrm'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="pzl-lead-status-edit" class="form-label fw-semibold">
                            <i class="ri-flag-line me-1 text-warning"></i><?php _e('وضعیت سرنخ', 'puzzlingcrm'); ?>
                        </label>
                        <select id="pzl-lead-status-edit" name="lead_status" class="form-select">
                            <?php
                            $current_status_terms = wp_get_object_terms($lead_id, 'lead_status', ['fields' => 'slugs']);
                            $current_status = !empty($current_status_terms) ? $current_status_terms[0] : '';
                            $all_statuses = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false]);

                            if (empty($all_statuses)) {
                                echo '<option value="">' . __('هیچ وضعیتی تعریف نشده است', 'puzzlingcrm') . '</option>';
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
                        <i class="ri-file-text-line me-1 text-secondary"></i><?php _e('یادداشت', 'puzzlingcrm'); ?>
                    </label>
                    <textarea id="pzl-notes-edit" name="notes" rows="4" class="form-control"><?php echo esc_textarea($notes); ?></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="ri-save-line me-2"></i><?php _e('ذخیره تغییرات', 'puzzlingcrm'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
    return;
}

// ========== LEADS LIST/CARD VIEW ==========
$lead_statuses = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false]);
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
$view_mode = isset($_GET['view']) && in_array($_GET['view'], ['card', 'list']) ? $_GET['view'] : 'list';

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

// Prepare leads data for both views
$all_leads = [];
if ($leads_query->have_posts()) {
    while ($leads_query->have_posts()) {
        $leads_query->the_post();
        $lead_id = get_the_ID();
        $first_name = get_post_meta($lead_id, '_first_name', true);
        $last_name = get_post_meta($lead_id, '_last_name', true);
        $mobile = get_post_meta($lead_id, '_mobile', true);
        $business_name = get_post_meta($lead_id, '_business_name', true);
        $email = get_post_meta($lead_id, '_email', true);
        $gender = get_post_meta($lead_id, '_gender', true);
        $status_terms = wp_get_object_terms($lead_id, 'lead_status');
        $status_name = !empty($status_terms) && !is_wp_error($status_terms) ? $status_terms[0]->name : __('نامشخص', 'puzzlingcrm');
        $status_slug = !empty($status_terms) && !is_wp_error($status_terms) ? $status_terms[0]->slug : '';
        $owner_id = get_post_field('post_author', $lead_id);
        $owner = get_userdata($owner_id);
        
        $all_leads[] = [
            'id' => $lead_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'full_name' => trim($first_name . ' ' . $last_name),
            'mobile' => $mobile,
            'business_name' => $business_name,
            'email' => $email,
            'gender' => $gender,
            'status_name' => $status_name,
            'status_slug' => $status_slug,
            'owner_id' => $owner_id,
            'owner_name' => $owner ? $owner->display_name : '',
            'date' => get_the_date('Y-m-d'),
            'date_formatted' => jdate('Y/m/d', strtotime(get_the_date('Y-m-d')))
        ];
    }
    wp_reset_postdata();
}

// Get total count
$total_leads = wp_count_posts('pzl_lead')->publish;

// Map status slugs to display names, colors, and h6 classes (exact match from reference)
$status_display_map = [
    'discovered' => ['name' => __('سرنخ‌های کشف شده', 'puzzlingcrm'), 'color' => 'primary', 'h6_class' => 'lead-discovered'],
    'qualified' => ['name' => __('سرنخ‌های واجد شرایط', 'puzzlingcrm'), 'color' => 'primary1', 'h6_class' => 'lead-qualified'],
    'contacted' => ['name' => __('تماس برقرار شد', 'puzzlingcrm'), 'color' => 'primary2', 'h6_class' => 'contact-initiated'],
    'needs-identified' => ['name' => __('نیازها شناسایی شدند', 'puzzlingcrm'), 'color' => 'primary3', 'h6_class' => 'need-identified'],
    'negotiation' => ['name' => __('مذاکره', 'puzzlingcrm'), 'color' => 'secondary', 'h6_class' => 'negotiation'],
    'converted' => ['name' => __('معامله نهایی شد', 'puzzlingcrm'), 'color' => 'success', 'h6_class' => 'deal-finalized'],
];

// Display order exactly as in reference
$status_display_order = ['discovered', 'qualified', 'contacted', 'needs-identified', 'negotiation', 'converted'];

// Create a map of status slugs to counts from taxonomy
$status_counts_map = [];
foreach ($lead_statuses as $status_term) {
    $status_counts_map[$status_term->slug] = $status_term->count;
}
?>

<!-- Header with Actions and View Toggle -->
<div class="card custom-card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="fw-medium fs-16"><?php _e('سرنخ‌ها', 'puzzlingcrm'); ?></span>
                <span class="badge bg-primary align-middle"><?php echo count($all_leads); ?></span>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- View Toggle -->
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="viewMode" id="viewList" value="list" <?php checked($view_mode, 'list'); ?>>
                    <label class="btn btn-outline-primary btn-sm" for="viewList" title="<?php esc_attr_e('حالت لیست', 'puzzlingcrm'); ?>">
                        <i class="ri-list-check"></i>
                    </label>
                    <input type="radio" class="btn-check" name="viewMode" id="viewCard" value="card" <?php checked($view_mode, 'card'); ?>>
                    <label class="btn btn-outline-primary btn-sm" for="viewCard" title="<?php esc_attr_e('حالت کارت', 'puzzlingcrm'); ?>">
                        <i class="ri-grid-line"></i>
                    </label>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLeadModal">
                    <i class="ri-add-line me-1 fw-medium align-middle"></i><span class="d-none d-sm-inline"><?php _e('ایجاد سرنخ', 'puzzlingcrm'); ?></span><span class="d-sm-none"><?php _e('ایجاد', 'puzzlingcrm'); ?></span>
                </button>
                <button type="button" class="btn btn-success-light btn-sm d-none d-md-inline-flex"><?php _e('خروجی CSV', 'puzzlingcrm'); ?></button>
                <div class="dropdown">
                    <a href="javascript:void(0);" class="btn btn-light btn-sm btn-wave waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-none d-lg-inline"><?php _e('مرتب‌سازی بر اساس', 'puzzlingcrm'); ?></span><span class="d-lg-none"><?php _e('مرتب', 'puzzlingcrm'); ?></span><i class="ri-arrow-down-s-line align-middle ms-1 d-inline-block"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" role="menu">
                        <li><a class="dropdown-item" href="javascript:void(0);"><?php _e('جدیدترین', 'puzzlingcrm'); ?></a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);"><?php _e('تاریخ اضافه شده', 'puzzlingcrm'); ?></a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);"><?php _e('الف - ی', 'puzzlingcrm'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card custom-card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3" id="leads-filter-form">
            <input type="hidden" name="page" value="puzzling-leads">
            <input type="hidden" name="view" id="view-mode-input" value="<?php echo esc_attr($view_mode); ?>">
            <div class="col-12 col-md-6 col-lg-5">
                <label class="form-label"><?php _e('جستجو', 'puzzlingcrm'); ?></label>
                <input type="text" name="s" class="form-control" placeholder="<?php esc_attr_e('نام، موبایل یا نام کسب‌وکار...', 'puzzlingcrm'); ?>" value="<?php echo esc_attr($search_query); ?>">
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label"><?php _e('وضعیت', 'puzzlingcrm'); ?></label>
                <select name="status_filter" class="form-select">
                    <option value=""><?php _e('همه وضعیت‌ها', 'puzzlingcrm'); ?></option>
                    <?php foreach ($lead_statuses as $status): ?>
                    <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($status_filter, $status->slug); ?>>
                        <?php echo esc_html($status->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-12 col-lg-3">
                <label class="form-label d-block d-md-none">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ri-filter-3-line"></i> <?php _e('فیلتر', 'puzzlingcrm'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Status Cards Header (Exactly as reference) - Draggable -->
<div class="row mb-4 pzl-status-cards-row" id="pzl-status-cards-container">
    <?php foreach ($status_display_order as $status_slug):
        // Get display data
        $display_data = $status_display_map[$status_slug] ?? ['name' => ucfirst($status_slug), 'color' => 'secondary', 'h6_class' => ''];
        $display_name = $display_data['name'];
        $color = $display_data['color'];
        $h6_class = $display_data['h6_class'] ?? '';
        
        // Get count from taxonomy (most accurate)
        $status_count = isset($status_counts_map[$status_slug]) ? $status_counts_map[$status_slug] : 0;
        
        // Special handling for converted status (show "معاملات" suffix)
        $badge_text = $status_count;
        if ($status_slug === 'converted' && $status_count > 0) {
            $badge_text = $status_count . ' ' . __('معاملات', 'puzzlingcrm');
        }
        
        // Badge classes based on color (exact match from reference)
        // Note: Primary doesn't have text-fixed-white, others do
        if ($color === 'primary') {
            $badge_class = 'badge bg-primary';
        } else {
            $badge_class = 'badge bg-' . $color . ' text-fixed-white';
        }
    ?>
    <div class="col-xxl-2 col-md-4 pzl-status-card-item" data-status-slug="<?php echo esc_attr($status_slug); ?>">
        <div class="card custom-card border border-<?php echo esc_attr($color); ?> border-opacity-50" style="cursor: move;">
            <div class="card-body p-3">
                <div class="d-flex align-items-top flex-wrap justify-content-between">
                    <div>
                        <h6 class="fw-medium <?php echo esc_attr($h6_class); ?>"><i class="ri-circle-fill p-1 lh-1 fs-7 rounded-2 bg-<?php echo esc_attr($color); ?>-transparent text-<?php echo esc_attr($color); ?> me-2 align-middle"></i><?php echo esc_html($display_name); ?></h6>
                    </div>
                    <?php if ($status_slug === 'discovered'): ?>
                    <div class="ms-auto text-center">
                        <span class=" <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($badge_text); ?></span>
                    </div>
                    <?php else: ?>
                    <div>
                        <span class=" <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($badge_text); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- LIST VIEW -->
<div class="pzl-leads-list-view" style="<?php echo $view_mode === 'list' ? '' : 'display:none;'; ?>">
    <div class="card custom-card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="card-title">
                <?php _e('سرنخ‌ها', 'puzzlingcrm'); ?><span class="badge bg-primary rounded ms-2 fs-12 align-middle"><?php echo count($all_leads); ?></span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table text-nowrap">
                    <thead>
                        <tr>
                            <th scope="col">
                                <input class="form-check-input check-all" type="checkbox" id="checkboxNoLabel" value="" aria-label="...">
                            </th>
                            <th scope="col"><?php _e('نام تماس', 'puzzlingcrm'); ?></th>
                            <th scope="col"><?php _e('شرکت', 'puzzlingcrm'); ?></th>
                            <th scope="col"><?php _e('ایمیل', 'puzzlingcrm'); ?></th>
                            <th scope="col"><?php _e('وضعیت سرنخ', 'puzzlingcrm'); ?></th>
                            <th scope="col"><?php _e('تلفن', 'puzzlingcrm'); ?></th>
                            <th scope="col"><?php _e('مالک', 'puzzlingcrm'); ?></th>
                            <th scope="col"><?php _e('آخرین قرارداد', 'puzzlingcrm'); ?></th>
                            <th scope="col"><?php _e('عملیات', 'puzzlingcrm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_leads)): 
                            foreach ($all_leads as $lead):
                                $lead_id = $lead['id'];
                                
                                // Badge class based on status
                                $badge_class = 'bg-secondary-transparent';
                                if ($lead['status_slug'] === 'new') $badge_class = 'bg-primary1-transparent';
                                elseif ($lead['status_slug'] === 'contacted') $badge_class = 'bg-primary-transparent';
                                elseif ($lead['status_slug'] === 'qualified') $badge_class = 'bg-success-transparent';
                                elseif ($lead['status_slug'] === 'converted') $badge_class = 'bg-success-transparent';
                                elseif ($lead['status_slug'] === 'lost') $badge_class = 'bg-danger-transparent';
                                
                                // Get avatar for lead - use owner or default
                                $avatar_user_id = $lead['owner_id'] > 0 ? $lead['owner_id'] : 0;
                                $avatar_url = '';
                                $avatar_html = '';
                                if ($avatar_user_id > 0) {
                                    $avatar_url = get_avatar_url($avatar_user_id, ['size' => 40]);
                                    $avatar_html = '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($lead['full_name']) . '">';
                                } else {
                                    // Use initials as fallback
                                    $initials = mb_substr($lead['first_name'] ?: '', 0, 1, 'UTF-8') . mb_substr($lead['last_name'] ?: '', 0, 1, 'UTF-8');
                                    if (empty($initials)) {
                                        $initials = '?';
                                    }
                                    $avatar_html = '<span class="d-flex align-items-center justify-content-center bg-primary text-white w-100 h-100">' . esc_html($initials) . '</span>';
                                }
                        ?>
                        <tr class="crm-contact leads-list" data-lead-id="<?php echo esc_attr($lead_id); ?>">
                            <td class="leads-checkbox">
                                <input class="form-check-input" type="checkbox" id="checkboxNoLabel<?php echo esc_attr($lead_id); ?>" value="" aria-label="...">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="lh-1">
                                        <span class="avatar avatar-rounded avatar-sm">
                                            <?php echo $avatar_html; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="d-block fw-medium"><?php echo esc_html($lead['full_name']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($lead['business_name']): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="lh-1">
                                        <span class="avatar avatar-sm p-1 bg-light avatar-rounded">
                                            <i class="ri-building-line"></i>
                                        </span>
                                    </div>
                                    <div><?php echo esc_html($lead['business_name']); ?></div>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($lead['email']): ?>
                                <div>
                                    <span class="d-block mb-1"><i class="ri-mail-line me-2 align-middle fs-14 text-muted d-inline-block"></i><?php echo esc_html($lead['email']); ?></span>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($lead['status_name']); ?></span>
                            </td>
                            <td>
                                <?php if ($lead['mobile']): ?>
                                <div>
                                    <span class="d-block"><i class="ri-phone-line me-2 align-middle fs-14 text-muted"></i><?php echo esc_html($lead['mobile']); ?></span>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($lead['owner_name'] && $lead['owner_id'] > 0): 
                                    $owner_avatar = get_avatar_url($lead['owner_id'], ['size' => 40]);
                                ?>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="lh-1">
                                        <span class="avatar avatar-rounded avatar-sm">
                                            <img src="<?php echo esc_url($owner_avatar); ?>" alt="<?php echo esc_attr($lead['owner_name']); ?>">
                                        </span>
                                    </div>
                                    <div>
                                        <span class="d-block fw-medium"><?php echo esc_html($lead['owner_name']); ?></span>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-medium">
                                <?php echo esc_html($lead['date_formatted']); ?>
                            </td>
                            <td>
                                <div class="btn-list">
                                    <a href="<?php echo esc_url(add_query_arg(['action' => 'edit', 'lead_id' => $lead_id], $base_url)); ?>" class="btn btn-sm btn-primary-light btn-icon" title="<?php esc_attr_e('مشاهده', 'puzzlingcrm'); ?>">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="<?php echo esc_url(add_query_arg(['action' => 'edit', 'lead_id' => $lead_id], $base_url)); ?>" class="btn btn-sm btn-info-light btn-icon" title="<?php esc_attr_e('ویرایش', 'puzzlingcrm'); ?>">
                                        <i class="ri-pencil-line"></i>
                                    </a>
                                    <button class="btn btn-sm btn-primary2-light btn-icon contact-delete pzl-delete-lead" 
                                            data-lead-id="<?php echo esc_attr($lead_id); ?>" 
                                            data-nonce="<?php echo wp_create_nonce('puzzlingcrm-ajax-nonce'); ?>" 
                                            title="<?php esc_attr_e('حذف', 'puzzlingcrm'); ?>">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        else: 
                        ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="ri-user-add-line fs-3 mb-3 d-block opacity-3"></i>
                                <p class="text-muted"><?php _e('هیچ سرنخی یافت نشد', 'puzzlingcrm'); ?></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($leads_query->max_num_pages > 1): ?>
        <div class="card-footer border-top-0">
            <div class="d-flex align-items-center">
                <div>
                    <?php printf(__('نمایش %d مورد', 'puzzlingcrm'), count($all_leads)); ?> <i class="bi bi-arrow-left ms-2 fw-medium"></i>
                </div>
                <div class="ms-auto">
                    <?php
                    echo paginate_links([
                        'total' => $leads_query->max_num_pages,
                        'current' => $paged,
                        'format' => '?paged=%#%',
                        'prev_text' => __('قبلی', 'puzzlingcrm'),
                        'next_text' => __('بعدی', 'puzzlingcrm'),
                    ]);
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- CARD VIEW -->
<div class="pzl-leads-card-view" style="<?php echo $view_mode === 'card' ? '' : 'display:none;'; ?>">
    <?php if (!empty($lead_statuses)): 
        // Group leads by status
        $leads_by_status = [];
        foreach ($all_leads as $lead) {
            $status_key = $lead['status_slug'] ?: 'no-status';
            if (!isset($leads_by_status[$status_key])) {
                $leads_by_status[$status_key] = [];
            }
            $leads_by_status[$status_key][] = $lead;
        }
        
        // Get status colors
        $status_colors = [];
        $color_index = 0;
        $colors = ['primary', 'primary1', 'primary2', 'primary3', 'secondary', 'success'];
        foreach ($lead_statuses as $status) {
            $status_colors[$status->slug] = $colors[$color_index % count($colors)];
            $color_index++;
        }
    ?>
    <!-- Cards Grid -->
    <div class="row g-3">
        <?php foreach ($lead_statuses as $status): 
            $leads_in_status = isset($leads_by_status[$status->slug]) ? $leads_by_status[$status->slug] : [];
            $status_id = 'status-' . esc_attr($status->slug);
        ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2" id="<?php echo $status_id; ?>">
            <?php if (!empty($leads_in_status)): 
                foreach ($leads_in_status as $lead):
                    $lead_id = $lead['id'];
                    // Get avatar - use owner if available, otherwise use initials
                    $avatar_user_id = $lead['owner_id'] > 0 ? $lead['owner_id'] : 0;
                    $avatar_html = '';
                    if ($avatar_user_id > 0) {
                        $avatar_url = get_avatar_url($avatar_user_id, ['size' => 40]);
                        $avatar_html = '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($lead['full_name']) . '">';
                    } else {
                        // Use initials as fallback
                        $initials = mb_substr($lead['first_name'] ?: '', 0, 1, 'UTF-8') . mb_substr($lead['last_name'] ?: '', 0, 1, 'UTF-8');
                        if (empty($initials)) {
                            $initials = '?';
                        }
                        $badge_class_for_avatar = $status_colors[$status->slug] ?? 'primary';
                        $avatar_html = '<span class="d-flex align-items-center justify-content-center bg-' . esc_attr($badge_class_for_avatar) . ' text-white w-100 h-100 fs-12">' . esc_html($initials) . '</span>';
                    }
                    $badge_class = $status_colors[$status->slug] ?? 'secondary';
            ?>
            <div class="card custom-card mb-3 h-100" data-lead-id="<?php echo esc_attr($lead_id); ?>">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-2 mb-3 flex-shrink-0"> 
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <div class="lh-1">
                                <span class="avatar avatar-sm avatar-rounded">
                                    <?php echo $avatar_html; ?>
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold"><?php echo esc_html($lead['full_name']); ?></div>
                                <div class="text-muted fs-10"><?php echo esc_html($lead['date_formatted']); ?></div>
                            </div>
                        </div>
                        <div class="dropdown ms-auto">
                            <a aria-label="anchor" href="javascript:void(0);" class="btn btn-light btn-icons btn-sm text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fe fe-more-vertical"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg(['action' => 'edit', 'lead_id' => $lead_id], $base_url)); ?>"><?php _e('ویرایش', 'puzzlingcrm'); ?></a></li>
                                <li><a class="dropdown-item contact-delete pzl-delete-lead" href="javascript:void(0);" data-lead-id="<?php echo esc_attr($lead_id); ?>" data-nonce="<?php echo wp_create_nonce('puzzlingcrm-ajax-nonce'); ?>"><?php _e('حذف', 'puzzlingcrm'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg(['action' => 'edit', 'lead_id' => $lead_id], $base_url)); ?>"><?php _e('مشاهده جزئیات', 'puzzlingcrm'); ?></a></li>
                            </ul>
                        </div>
                    </div>
                    <?php if ($lead['business_name']): ?>
                    <p class="fw-medium mb-1 fs-14"><?php echo esc_html($lead['business_name']); ?></p>
                    <?php endif; ?>
                    <?php if ($lead['mobile']): ?>
                    <p class="fw-medium mb-1"><span class="text-muted fw-normal"><?php _e('موبایل:', 'puzzlingcrm'); ?></span><?php echo esc_html($lead['mobile']); ?></p>
                    <?php endif; ?>
                    <div class="deal-description">
                        <?php if ($lead['business_name']): ?>
                        <div class="">
                            <a href="javascript:void(0);" class="company-name"><?php echo esc_html($lead['business_name']); ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php 
                endforeach;
            endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card custom-card">
        <div class="card-body text-center py-5">
            <i class="ri-user-add-line fs-3 mb-3 d-block opacity-3"></i>
            <p class="text-muted"><?php _e('هیچ سرنخی یافت نشد', 'puzzlingcrm'); ?></p>
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
                    <i class="ri-user-add-line me-2"></i><?php _e('ایجاد سرنخ', 'puzzlingcrm'); ?>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="pzl-add-lead-form" class="pzl-ajax-form">
                    <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                    <input type="hidden" name="action" value="puzzling_add_lead">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php _e('نام', 'puzzlingcrm'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php _e('نام خانوادگی', 'puzzlingcrm'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php _e('شماره موبایل', 'puzzlingcrm'); ?> <span class="text-danger">*</span></label>
                            <input type="tel" name="mobile" class="form-control ltr-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php _e('نام کسب‌وکار', 'puzzlingcrm'); ?></label>
                            <input type="text" name="business_name" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php _e('ایمیل', 'puzzlingcrm'); ?></label>
                            <input type="email" name="email" class="form-control ltr-input">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php _e('جنسیت', 'puzzlingcrm'); ?></label>
                            <select name="gender" class="form-select">
                                <option value=""><?php _e('انتخاب کنید', 'puzzlingcrm'); ?></option>
                                <option value="male"><?php _e('آقا', 'puzzlingcrm'); ?></option>
                                <option value="female"><?php _e('خانم', 'puzzlingcrm'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold"><?php _e('وضعیت سرنخ', 'puzzlingcrm'); ?></label>
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
                        <label class="form-label fw-semibold"><?php _e('یادداشت', 'puzzlingcrm'); ?></label>
                        <textarea name="notes" rows="3" class="form-control"></textarea>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-add-line me-1"></i><?php _e('افزودن سرنخ', 'puzzlingcrm'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// Store view mode for the script
window.pzlLeadsViewMode = '<?php echo esc_js($view_mode); ?>';
console.log('PuzzlingCRM Leads: View mode set to', window.pzlLeadsViewMode);
</script>
<script src="<?php echo esc_url(PUZZLINGCRM_PLUGIN_URL . 'assets/js/leads-page-init.js?v=' . PUZZLINGCRM_VERSION); ?>" defer></script>

