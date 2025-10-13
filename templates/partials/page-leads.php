<?php
/**
 * PuzzlingCRM Leads Management Page - VISUALLY REVAMPED
 *
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options') && !current_user_can('system_manager')) {
    return;
}

// Get all lead statuses for filtering and stats
$lead_statuses = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false]);

// ======== Start: Query and Filtering Logic ========
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

$args = [
    'post_type' => 'pzl_lead',
    'post_status' => 'publish',
    'posts_per_page' => 20,
    'paged' => $paged,
];

// Add search to query
if (!empty($search_query)) {
    $args['meta_query'] = [
        'relation' => 'OR',
        ['key' => '_first_name', 'value' => $search_query, 'compare' => 'LIKE'],
        ['key' => '_last_name', 'value' => $search_query, 'compare' => 'LIKE'],
        ['key' => '_mobile', 'value' => $search_query, 'compare' => 'LIKE'],
        ['key' => '_business_name', 'value' => $search_query, 'compare' => 'LIKE'],
    ];
}

// Add status filter to query
if (!empty($status_filter)) {
    $args['tax_query'] = [
        [
            'taxonomy' => 'lead_status',
            'field' => 'slug',
            'terms' => $status_filter,
        ],
    ];
}

$leads_query = new WP_Query($args);
// ======== End: Query and Filtering Logic ========

?>

<div class="pzl-dashboard-section">

    <div class="pzl-dashboard-stats-grid">
        <div class="stat-widget-card gradient-1">
            <div class="stat-widget-icon"><i class="fas fa-users"></i></div>
            <div class="stat-widget-content">
                <span class="stat-number"><?php echo wp_count_posts('pzl_lead')->publish; ?></span>
                <span class="stat-title">کل سرنخ‌ها</span>
            </div>
        </div>
        <?php 
        $gradients = ['gradient-2', 'gradient-3', 'gradient-4', 'gradient-5'];
        $i = 0;
        foreach ($lead_statuses as $status) : ?>
            <div class="stat-widget-card <?php echo $gradients[$i % count($gradients)]; ?>">
                <div class="stat-widget-icon"><i class="fas fa-tag"></i></div>
                <div class="stat-widget-content">
                    <span class="stat-number"><?php echo esc_html($status->count); ?></span>
                    <span class="stat-title"><?php echo esc_html($status->name); ?></span>
                </div>
            </div>
        <?php $i++; endforeach; ?>
    </div>

    <div class="pzl-card">
        <div class="pzl-card-header">
            <h3><i class="fas fa-bullhorn"></i> مدیریت سرنخ‌ها</h3>
            <a href="#" id="pzl-add-new-lead-btn" class="pzl-button">افزودن سرنخ جدید</a>
        </div>

        <form method="get">
            <input type="hidden" name="page" value="puzzling-crm-leads">
            <div class="pzl-search-form-container" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <div class="form-group" style="flex: 1;">
                    <select name="status_filter" style="width: 100%;">
                        <option value="">همه وضعیت‌ها</option>
                        <?php foreach ($lead_statuses as $status) : ?>
                            <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($status_filter, $status->slug); ?>>
                                <?php echo esc_html($status->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 2;">
                     <i class="fas fa-search pzl-search-icon"></i>
                    <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="نام، موبایل یا کسب‌وکار..." />
                </div>
                <div class="form-group">
                    <input type="submit" class="pzl-button" value="فیلتر و جستجو">
                </div>
            </div>
        </form>

        <table class="pzl-table">
            <thead>
                <tr>
                    <th scope="col">نام و نام خانوادگی</th>
                    <th scope="col">موبایل</th>
                    <th scope="col">نام کسب‌وکار</th>
                    <th scope="col">وضعیت</th>
                    <th scope="col">یادداشت‌ها</th>
                    <th scope="col">تاریخ ثبت</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($leads_query->have_posts()) : ?>
                    <?php while ($leads_query->have_posts()) : $leads_query->the_post(); ?>
                        <?php
                            $lead_id = get_the_ID();
                            $first_name = get_post_meta($lead_id, '_first_name', true);
                            $last_name = get_post_meta($lead_id, '_last_name', true);
                            $mobile = get_post_meta($lead_id, '_mobile', true);
                            $business_name = get_post_meta($lead_id, '_business_name', true);
                            $status_terms = get_the_terms($lead_id, 'lead_status');
                            $status_name = !empty($status_terms) ? $status_terms[0]->name : '---';
                        ?>
                        <tr>
                            <td><?php echo esc_html($first_name . ' ' . $last_name); ?></td>
                            <td><?php echo esc_html($mobile); ?></td>
                            <td><?php echo esc_html($business_name); ?></td>
                            <td><span class="pzl-status-badge"><?php echo esc_html($status_name); ?></span></td>
                            <td><?php echo wp_trim_words(get_the_content(), 10, '...'); ?></td>
                            <td><?php echo get_the_date('Y/m/d'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6">هیچ سرنخی یافت نشد.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $leads_query->max_num_pages,
                            'current' => $paged,
                        ]);
                    ?>
                </span>
            </div>
        </div>
        <?php wp_reset_postdata(); ?>
    </div>
</div>

<div id="pzl-add-lead-modal" style="display:none;">
    <div class="pzl-modal-content">
        <h3 style="margin-top:0;">افزودن سرنخ جدید</h3>
        <form id="pzl-add-lead-form" class="pzl-form">
            <?php wp_nonce_field('puzzling_add_lead_nonce', 'security'); ?>
            <div class="pzl-form-row">
                <div class="form-group half-width">
                    <label for="pzl-first-name">نام</label>
                    <input type="text" id="pzl-first-name" name="first_name" required>
                </div>
                <div class="form-group half-width">
                    <label for="pzl-last-name">نام خانوادگی</label>
                    <input type="text" id="pzl-last-name" name="last_name" required>
                </div>
            </div>
            <div class="pzl-form-row">
                <div class="form-group half-width">
                    <label for="pzl-mobile">شماره موبایل</label>
                    <input type="tel" id="pzl-mobile" name="mobile" class="ltr-input" required>
                </div>
                <div class="form-group half-width">
                    <label for="pzl-business-name">نام کسب‌وکار</label>
                    <input type="text" id="pzl-business-name" name="business_name">
                </div>
            </div>
            <div class="form-group">
                <label for="pzl-notes">یادداشت</label>
                <textarea id="pzl-notes" name="notes" rows="4"></textarea>
            </div>
            <div class="form-submit">
                <button type="submit" class="pzl-button">ثبت سرنخ</button>
                <button type="button" class="pzl-button pzl-button-secondary" id="pzl-close-modal-btn">انصراف</button>
            </div>
        </form>
        <div id="pzl-add-lead-feedback"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show modal
    $('#pzl-add-new-lead-btn').on('click', function(e) {
        e.preventDefault();
        $('#pzl-add-lead-modal').fadeIn();
    });

    // Close modal
    $('#pzl-close-modal-btn, #pzl-add-lead-modal').on('click', function(e) {
        if (e.target === this || $(e.target).is('#pzl-close-modal-btn')) {
            $('#pzl-add-lead-modal').fadeOut();
            $('#pzl-add-lead-form')[0].reset();
            $('#pzl-add-lead-feedback').empty();
        }
    });

    // Handle form submission
    $('#pzl-add-lead-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var feedbackDiv = $('#pzl-add-lead-feedback');
        feedbackDiv.text('در حال ثبت...').removeClass('error success');

        $.post(ajaxurl, 'action=puzzling_add_lead&' + formData, function(response) {
            if (response.success) {
                feedbackDiv.text(response.data.message).addClass('success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                feedbackDiv.text(response.data.message).addClass('error');
            }
        });
    });
});
</script>

<style>
/* Basic Styles for Modal */
#pzl-add-lead-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; display: flex; align-items: center; justify-content: center;}
.pzl-modal-content { background: #fff; padding: 30px; border-radius: 5px; width: 90%; max-width: 500px; }
#pzl-add-lead-feedback.success { color: green; margin-top: 10px; }
#pzl-add-lead-feedback.error { color: red; margin-top: 10px; }
</style>