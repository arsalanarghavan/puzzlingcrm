<?php
/**
 * PuzzlingCRM Leads Management Page (Professionally Restyled)
 * This version uses the main plugin's stylesheet for a consistent and professional UI.
 *
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options') && !current_user_can('system_manager')) {
    return;
}

// Get all lead statuses for filtering and stats
$lead_statuses = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false]);
$total_leads = wp_count_posts('pzl_lead')->publish;

// ======== Query and Filtering Logic ========
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

$args = [
    'post_type' => 'pzl_lead',
    'post_status' => 'publish',
    'posts_per_page' => 20,
    'paged' => $paged,
];

if (!empty($search_query)) {
    $args['meta_query'] = [
        'relation' => 'OR',
        ['key' => '_first_name', 'value' => $search_query, 'compare' => 'LIKE'],
        ['key' => '_last_name', 'value' => $search_query, 'compare' => 'LIKE'],
        ['key' => '_mobile', 'value' => $search_query, 'compare' => 'LIKE'],
        ['key' => '_business_name', 'value' => $search_query, 'compare' => 'LIKE'],
    ];
}

if (!empty($status_filter)) {
    $args['tax_query'] = [['taxonomy' => 'lead_status', 'field' => 'slug', 'terms' => $status_filter]];
}

$leads_query = new WP_Query($args);
?>

<div class="puzzling-dashboard-wrapper">
    <div class="pzl-card-header">
        <h3><i class="fas fa-users"></i> مدیریت سرنخ‌ها</h3>
        <a href="#" id="pzl-add-new-lead-btn" class="pzl-button">
            <i class="fas fa-plus"></i> افزودن سرنخ جدید
        </a>
    </div>

    <div class="pzl-dashboard-stats-grid">
        <div class="stat-widget-card gradient-1">
            <div class="stat-widget-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-widget-content">
                <div class="stat-number"><?php echo $total_leads; ?></div>
                <div class="stat-title">کل سرنخ‌ها</div>
            </div>
        </div>
        <?php foreach ($lead_statuses as $status) : ?>
        <div class="stat-widget-card gradient-2">
            <div class="stat-widget-icon"><i class="fas fa-tag"></i></div>
            <div class="stat-widget-content">
                <div class="stat-number"><?php echo esc_html($status->count); ?></div>
                <div class="stat-title"><?php echo esc_html($status->name); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="pzl-card">
        <form method="get" class="pzl-form">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
            <div class="pzl-form-row">
                <div class="form-group half-width">
                    <label for="status_filter">فیلتر بر اساس وضعیت</label>
                    <select name="status_filter" id="status_filter">
                        <option value="">همه وضعیت‌ها</option>
                        <?php foreach ($lead_statuses as $status) : ?>
                            <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($status_filter, $status->slug); ?>>
                                <?php echo esc_html($status->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group half-width">
                     <label for="search_query">جستجو</label>
                    <input type="search" id="search_query" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="نام، موبایل یا کسب‌وکار..." />
                </div>
            </div>
            <div class="form-submit" style="margin-top:0; padding-top:0;">
                <button type="submit" class="pzl-button">اعمال فیلتر</button>
            </div>
        </form>
        
        <table class="pzl-table">
            <thead>
                <tr>
                    <th>نام کامل</th>
                    <th>موبایل</th>
                    <th>کسب‌وکار</th>
                    <th>وضعیت</th>
                    <th>یادداشت‌ها</th>
                    <th>تاریخ ثبت</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($leads_query->have_posts()) : ?>
                    <?php while ($leads_query->have_posts()) : $leads_query->the_post(); ?>
                        <?php
                            $lead_id = get_the_ID();
                            $status_terms = get_the_terms($lead_id, 'lead_status');
                            $status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : '---';
                            // You can add a class based on slug for specific status colors if defined in your CSS
                            $status_slug = !empty($status_terms) ? esc_html($status_terms[0]->slug) : '';
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html(get_the_title()); ?></strong></td>
                            <td><a href="tel:<?php echo esc_attr(get_post_meta($lead_id, '_mobile', true)); ?>"><?php echo esc_html(get_post_meta($lead_id, '_mobile', true)); ?></a></td>
                            <td><?php echo esc_html(get_post_meta($lead_id, '_business_name', true)); ?></td>
                            <td><span class="pzl-status-badge status-<?php echo $status_slug; ?>"><?php echo $status_name; ?></span></td>
                            <td><?php echo wp_trim_words(get_the_content(), 10, '...'); ?></td>
                            <td><?php echo get_the_date('Y/m/d'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6">
                            <div class="pzl-empty-state">
                                <i class="fas fa-search"></i>
                                <h4>نتیجه‌ای یافت نشد</h4>
                                <p>هیچ سرنخی با مشخصات وارد شده یافت نشد. لطفاً فیلترهای خود را تغییر دهید.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($leads_query->max_num_pages > 1) : ?>
        <div class="pzl-pagination">
            <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'), 'format' => '?paged=%#%',
                    'current' => $paged, 'total' => $leads_query->max_num_pages,
                    'prev_text' => ' قبلی', 'next_text' => 'بعدی ',
                ]);
            ?>
        </div>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</div>

<div id="pzl-add-lead-modal" class="pzl-modal-backdrop" style="display:none;">
    <div class="pzl-modal-content" style="max-width: 600px;">
        <button type="button" class="pzl-modal-close" id="pzl-close-modal-btn">&times;</button>
        <div class="pzl-modal-header">
            <h3 class="pzl-modal-title">افزودن سرنخ جدید</h3>
        </div>
        <form id="pzl-add-lead-form" class="pzl-form pzl-modal-body">
            <?php wp_nonce_field('puzzling_add_lead_nonce', 'security'); ?>
            <input type="hidden" name="action" value="puzzling_add_lead">
            
            <div class="pzl-form-row">
                <div class="form-group half-width">
                    <label for="pzl-first-name">نام<span style="color:red">*</span></label>
                    <input type="text" id="pzl-first-name" name="first_name" required>
                </div>
                <div class="form-group half-width">
                    <label for="pzl-last-name">نام خانوادگی<span style="color:red">*</span></label>
                    <input type="text" id="pzl-last-name" name="last_name" required>
                </div>
            </div>
            <div class="pzl-form-row">
                <div class="form-group half-width">
                    <label for="pzl-mobile">شماره موبایل<span style="color:red">*</span></label>
                    <input type="tel" id="pzl-mobile" name="mobile" class="ltr-input" required placeholder="09123456789">
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
            <div class="pzl-modal-footer">
                <button type="button" class="pzl-button pzl-button-secondary" id="pzl-cancel-modal-btn">انصراف</button>
                <button type="submit" class="pzl-button">ثبت سرنخ</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const modal = $('#pzl-add-lead-modal');
    const form = $('#pzl-add-lead-form');

    $('#pzl-add-new-lead-btn').on('click', function(e) {
        e.preventDefault();
        modal.fadeIn(200).addClass('pzl-is-visible');
    });

    function closeModal() {
        modal.fadeOut(200).removeClass('pzl-is-visible');
        form[0].reset();
    }

    $('#pzl-close-modal-btn, #pzl-cancel-modal-btn, .pzl-modal-backdrop').on('click', function(e) {
        if ($(e.target).is('.pzl-modal-backdrop, #pzl-close-modal-btn, #pzl-cancel-modal-btn')) {
            e.preventDefault();
            closeModal();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === "Escape" && modal.is(':visible')) {
            closeModal();
        }
    });
});
</script>

<style>
/* Scoped styles for leads page to avoid conflicts */
.pzl-modal-backdrop {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(29, 30, 41, 0.7);
    z-index: 10000; display: none; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.2s;
}
.pzl-modal-backdrop.pzl-is-visible { opacity: 1; }
.pzl-modal-content {
    background: #f0f0f1; border-radius: 8px;
    width: 95%; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    transform: translateY(-20px); transition: transform 0.2s;
    position: relative;
}
.pzl-is-visible .pzl-modal-content { transform: translateY(0); }
.pzl-modal-header { padding: 20px 25px; border-bottom: 1px solid #ddd; }
.pzl-modal-title { margin: 0; font-size: 20px; font-weight: 700; }
.pzl-modal-body { padding: 25px; }
.pzl-modal-footer {
    padding: 15px 25px; border-top: 1px solid #ddd; text-align: left;
    background: #f7f7f7; border-radius: 0 0 8px 8px;
    display: flex; justify-content: flex-end; gap: 10px;
}
.pzl-modal-close {
    position: absolute; top: 10px; left: 15px; background: none; border: none;
    font-size: 28px; cursor: pointer; color: #666; line-height: 1;
    padding: 5px; transition: color 0.2s;
}
.pzl-modal-close:hover { color: #F0192A; }
</style>