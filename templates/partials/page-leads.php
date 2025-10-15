<?php
/**
 * PuzzlingCRM Leads Management Page (Final, Fully Functional Version with Edit Screen & Inline Status Change)
 *
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options') && !current_user_can('system_manager')) {
    wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'puzzlingcrm'));
}

// --- LOGIC TO DISPLAY EITHER THE LIST OR THE EDIT FORM ---
$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$lead_id = isset($_GET['lead_id']) ? intval($_GET['lead_id']) : 0;

if ($action === 'edit' && $lead_id > 0) {
    // ======== DISPLAY EDIT FORM ========
    $lead = get_post($lead_id);
    if (!$lead || $lead->post_type !== 'pzl_lead') {
        echo '<div class="puzzling-dashboard-wrapper"><div class="notice notice-error"><p>سرنخ مورد نظر یافت نشد. <a href="' . esc_url(remove_query_arg(['action', 'lead_id'])) . '">بازگشت به لیست</a></p></div></div>';
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
    <div class="puzzling-dashboard-wrapper">
        <div class="pzl-card-header">
            <h3><i class="fas fa-edit"></i> ویرایش سرنخ: <?php echo esc_html($first_name . ' ' . $last_name); ?></h3>
            <a href="<?php echo esc_url($return_url); ?>" class="pzl-button pzl-button-secondary">
                <i class="fas fa-arrow-left"></i> بازگشت به لیست سرنخ‌ها
            </a>
        </div>
        <div class="pzl-card">
            <form id="pzl-edit-lead-form" class="pzl-form pzl-ajax-form">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
                <input type="hidden" name="action" value="puzzling_edit_lead">
                <input type="hidden" name="lead_id" value="<?php echo esc_attr($lead_id); ?>">
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr(wp_unslash($return_url)); ?>">

                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="pzl-first-name-edit">نام<span class="pzl-required">*</span></label>
                        <input type="text" id="pzl-first-name-edit" name="first_name" value="<?php echo esc_attr($first_name); ?>" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="pzl-last-name-edit">نام خانوادگی<span class="pzl-required">*</span></label>
                        <input type="text" id="pzl-last-name-edit" name="last_name" value="<?php echo esc_attr($last_name); ?>" required>
                    </div>
                </div>
                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="pzl-mobile-edit">شماره موبایل<span class="pzl-required">*</span></label>
                        <input type="tel" id="pzl-mobile-edit" name="mobile" class="ltr-input" value="<?php echo esc_attr($mobile); ?>" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="pzl-business-name-edit">نام کسب‌وکار</label>
                        <input type="text" id="pzl-business-name-edit" name="business_name" value="<?php echo esc_attr($business_name); ?>">
                    </div>
                </div>
                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="pzl-gender-edit">جنسیت</label>
                        <select id="pzl-gender-edit" name="gender">
                            <option value="">انتخاب کنید</option>
                            <option value="male" <?php selected($gender, 'male'); ?>>آقا</option>
                            <option value="female" <?php selected($gender, 'female'); ?>>خانم</option>
                        </select>
                    </div>
                    <div class="form-group half-width">
                        <!-- Empty space for layout balance -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="pzl-lead-status-edit">وضعیت سرنخ</label>
                    <select id="pzl-lead-status-edit" name="lead_status">
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

                <div class="form-group">
                    <label for="pzl-notes-edit">یادداشت</label>
                    <textarea id="pzl-notes-edit" name="notes" rows="4"><?php echo esc_textarea($notes); ?></textarea>
                </div>
                <div class="pzl-modal-footer" style="background: transparent; border-top: none; padding: 15px 0 0 0;">
                    <button type="submit" class="pzl-button">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
    <?php
    return;
}

// ======== DISPLAY LEADS LIST (DEFAULT VIEW) ========
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
$delete_nonce = wp_create_nonce('puzzlingcrm-ajax-nonce');
$change_status_nonce = wp_create_nonce('puzzlingcrm-ajax-nonce');
$current_page_url = remove_query_arg(['action', 'lead_id', '_wpnonce', 'deleted', 'updated']);
?>

<div class="puzzling-dashboard-wrapper">
    <div class="pzl-card-header">
        <h3><i class="fas fa-users"></i> مدیریت سرنخ‌ها</h3>
        <a href="#" id="pzl-add-new-lead-btn" class="pzl-button"><i class="fas fa-plus"></i> افزودن سرنخ جدید</a>
    </div>
    
    <div class="pzl-card">
        <form method="get" class="pzl-form">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page'] ?? 'puzzling-leads'); ?>">
             <div class="pzl-form-row">
                <div class="form-group half-width">
                    <label for="status_filter">فیلتر بر اساس وضعیت</label>
                    <select name="status_filter" id="status_filter">
                        <option value="">همه وضعیت‌ها</option>
                        <?php if (!empty($lead_statuses)) : ?>
                            <?php foreach ($lead_statuses as $status) : ?>
                                <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($status_filter, $status->slug); ?>><?php echo esc_html($status->name); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                    <th>جنسیت</th>
                    <th>کسب‌وکار</th>
                    <th>وضعیت</th>
                    <th>تاریخ ثبت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($leads_query->have_posts()) : ?>
                    <?php while ($leads_query->have_posts()) : $leads_query->the_post(); ?>
                        <?php
                        $lead_id = get_the_ID();
                        $status_terms = get_the_terms($lead_id, 'lead_status');
                        $status_slug = !empty($status_terms) && isset($status_terms[0]) ? $status_terms[0]->slug : '';
                        $edit_link = add_query_arg(['action' => 'edit', 'lead_id' => $lead_id], $current_page_url);
                        $gender = get_post_meta($lead_id, '_gender', true);
                        $gender_display = ($gender === 'male') ? 'آقا' : (($gender === 'female') ? 'خانم' : '---');
                        
                        ?>
                        <tr data-lead-id="<?php echo esc_attr($lead_id); ?>">
                            <td><strong><?php echo esc_html(get_the_title()); ?></strong></td>
                            <td><a href="tel:<?php echo esc_attr(get_post_meta($lead_id, '_mobile', true)); ?>"><?php echo esc_html(get_post_meta($lead_id, '_mobile', true)); ?></a></td>
                            <td><?php echo esc_html($gender_display); ?></td>
                            <td><?php echo esc_html(get_post_meta($lead_id, '_business_name', true)); ?></td>
                            <td>
                                <?php if (!empty($lead_statuses)) : ?>
                                <select class="pzl-lead-status-changer" data-lead-id="<?php echo esc_attr($lead_id); ?>" data-nonce="<?php echo esc_attr($change_status_nonce); ?>">
                                    <?php
                                    foreach ($lead_statuses as $status) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($status->slug),
                                            selected($status_slug, $status->slug, false),
                                            esc_html($status->name)
                                        );
                                    }
                                    ?>
                                </select>
                                <?php else: ?>
                                    ---
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_the_date('Y/m/d'); ?></td>
                            <td class="pzl-table-actions">
                                <a href="<?php echo esc_url($edit_link); ?>" class="pzl-button-icon" title="ویرایش"><i class="fas fa-edit"></i></a>
                                <a href="#" class="pzl-button-icon pzl-delete-lead-btn" data-nonce="<?php echo esc_attr($delete_nonce); ?>" title="حذف" style="color: #F0192A;"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7">
                            <div class="pzl-empty-state">
                                <i class="fas fa-search"></i>
                                <h4>نتیجه‌ای یافت نشد</h4>
                                <p>هیچ سرنخی با مشخصات وارد شده یافت نشد. <br><?php if (empty($lead_statuses)) echo '<b>توجه:</b> هیچ وضعیتی برای سرنخ‌ها تعریف نشده است. لطفاً از بخش تنظیمات وضعیت‌ها را مدیریت کنید.'; ?></p>
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
                'base' => add_query_arg('paged', '%#%', $current_page_url),
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $leads_query->max_num_pages,
                'prev_text' => ' قبلی',
                'next_text' => 'بعدی ',
            ]);
            ?>
        </div>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</div>

<div id="pzl-add-lead-modal" class="pzl-modal-backdrop">
    <div class="pzl-modal-content">
        <button type="button" class="pzl-modal-close" id="pzl-close-modal-btn">&times;</button>
        <div class="pzl-modal-header">
            <h3 class="pzl-modal-title">افزودن سرنخ جدید</h3>
        </div>
        <form id="pzl-add-lead-form" class="pzl-form pzl-modal-body pzl-ajax-form">
            <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>
            <input type="hidden" name="action" value="puzzling_add_lead">
            <div class="pzl-form-row">
                <div class="form-group half-width">
                    <label for="pzl-first-name">نام<span class="pzl-required">*</span></label>
                    <input type="text" id="pzl-first-name" name="first_name" required>
                </div>
                <div class="form-group half-width">
                    <label for="pzl-last-name">نام خانوادگی<span class="pzl-required">*</span></label>
                    <input type="text" id="pzl-last-name" name="last_name" required>
                </div>
            </div>
            <div class="pzl-form-row">
                <div class="form-group half-width">
                    <label for="pzl-mobile">شماره موبایل<span class="pzl-required">*</span></label>
                    <input type="tel" id="pzl-mobile" name="mobile" class="ltr-input" required placeholder="09123456789">
                </div>
                <div class="form-group half-width">
                    <label for="pzl-business-name">نام کسب‌وکار</label>
                    <input type="text" id="pzl-business-name" name="business_name">
                </div>
            </div>
            <div class="pzl-form-row">
                <div class="form-group half-width">
                    <label for="pzl-gender">جنسیت</label>
                    <select id="pzl-gender" name="gender">
                        <option value="">انتخاب کنید</option>
                        <option value="male">آقا</option>
                        <option value="female">خانم</option>
                    </select>
                </div>
                <div class="form-group half-width">
                    <!-- Empty space for layout balance -->
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
// Keep the modal script separate for clarity, as it's page-specific
jQuery(document).ready(function($) {
    const modal = $('#pzl-add-lead-modal');
    const form = $('#pzl-add-lead-form');

    window.closeLeadModal = function() {
        modal.removeClass('pzl-is-visible');
        setTimeout(() => form[0].reset(), 200);
    };

    $('#pzl-add-new-lead-btn').on('click', function(e) {
        e.preventDefault();
        modal.addClass('pzl-is-visible');
    });

    $('#pzl-close-modal-btn, #pzl-cancel-modal-btn, .pzl-modal-backdrop').on('click', function(e) {
        if ($(e.target).is('.pzl-modal-backdrop, #pzl-close-modal-btn, #pzl-cancel-modal-btn')) {
            e.preventDefault();
            window.closeLeadModal();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === "Escape" && modal.hasClass('pzl-is-visible')) {
            window.closeLeadModal();
        }
    });
});
</script>

<style>
/* Styles can remain the same */
.pzl-modal-backdrop {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(29, 30, 41, 0.7);
    z-index: 10000;
    display: none;
    align-items: center; justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease-in-out;
}
.pzl-modal-backdrop.pzl-is-visible { display: flex; opacity: 1; }
.pzl-modal-content {
    background: #fff; border-radius: 8px;
    width: 95%; max-width: 600px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    transform: translateY(-20px);
    transition: transform 0.2s ease-in-out, opacity 0.2s;
    opacity: 0;
}
.pzl-modal-backdrop.pzl-is-visible .pzl-modal-content { transform: translateY(0); opacity: 1; }
.pzl-modal-header { padding: 20px 25px; border-bottom: 1px solid #ddd; }
.pzl-modal-title { margin: 0; font-size: 20px; font-weight: 700; }
.pzl-modal-body { padding: 25px; }
.pzl-modal-footer {
    padding: 15px 25px; border-top: 1px solid #ddd;
    text-align: left; background: #f7f7f7;
    border-radius: 0 0 8px 8px; display: flex;
    justify-content: flex-end; gap: 10px;
}
.pzl-modal-close {
    position: absolute; top: 10px; left: 15px;
    background: none; border: none; font-size: 28px;
    cursor: pointer; color: #666; line-height: 1;
    padding: 5px; transition: color 0.2s;
}
.pzl-modal-close:hover { color: #F0192A; }
.pzl-table-actions { text-align: left; white-space: nowrap; }
.pzl-button-icon {
    display: inline-block; padding: 5px 8px;
    text-decoration: none; color: #0073aa;
    transition: color 0.2s;
}
.pzl-button-icon:hover { color: #005a87; }
.pzl-required { color: red; margin-right: 3px; }
select.pzl-lead-status-changer {
    min-width: 150px;
    padding: 5px;
    border-radius: 4px;
    border: 1px solid #ddd;
    transition: background-color 0.3s ease;
}
</style>