<?php
/**
 * Template for the [puzzling_leads] shortcode.
 *
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

global $wpdb;

//======================================================================
// 1. FORM PROCESSING (Add, Update, Delete)
//======================================================================

$message = '';
$message_class = '';

// --- Handle Delete Lead ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['lead_id'])) {
    $lead_id = intval($_GET['lead_id']);
    // Verify nonce for security
    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'puzzling_delete_lead_' . $lead_id)) {
        wp_delete_post($lead_id, true); // true = force delete
        $message = __('سرنخ با موفقیت حذف شد.', 'puzzlingcrm');
        $message_class = 'success';
    } else {
        $message = __('عملیات نامعتبر است.', 'puzzlingcrm');
        $message_class = 'error';
    }
}

// --- Handle Add/Update Lead ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Add New Lead ---
    if (isset($_POST['puzzling_add_lead'])) {
        if (check_admin_referer('puzzling_add_lead_nonce', 'security')) {
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            $mobile = sanitize_text_field($_POST['mobile']);
            $business_name = sanitize_text_field($_POST['business_name']);
            $notes = sanitize_textarea_field($_POST['notes']);
            $status = sanitize_text_field($_POST['status']);

            $lead_id = wp_insert_post([
                'post_title' => $first_name . ' ' . $last_name,
                'post_type' => 'pzl_lead',
                'post_status' => 'publish',
                'post_content' => $notes,
            ]);

            if ($lead_id) {
                update_post_meta($lead_id, '_mobile', $mobile);
                update_post_meta($lead_id, '_business_name', $business_name);
                wp_set_object_terms($lead_id, $status, 'lead_status');
                $message = __('سرنخ جدید با موفقیت اضافه شد.', 'puzzlingcrm');
                $message_class = 'success';
            }
        }
    }

    // --- Update Existing Lead ---
    if (isset($_POST['puzzling_edit_lead'])) {
        $lead_id = intval($_POST['lead_id']);
        if (check_admin_referer('puzzling_edit_lead_nonce_' . $lead_id, 'security')) {
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            $mobile = sanitize_text_field($_POST['mobile']);
            $business_name = sanitize_text_field($_POST['business_name']);
            $notes = sanitize_textarea_field($_POST['notes']);
            $status = sanitize_text_field($_POST['status']);

            wp_update_post([
                'ID' => $lead_id,
                'post_title' => $first_name . ' ' . $last_name,
                'post_content' => $notes,
            ]);

            update_post_meta($lead_id, '_mobile', $mobile);
            update_post_meta($lead_id, '_business_name', $business_name);
            wp_set_object_terms($lead_id, $status, 'lead_status');
            
            $message = __('سرنخ با موفقیت به‌روزرسانی شد.', 'puzzlingcrm');
            $message_class = 'success';
        }
    }
}


//======================================================================
// 2. DATA & VARIABLES
//======================================================================

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$lead_statuses = get_terms(['taxonomy' => 'lead_status', 'hide_empty' => false]);
$base_url = remove_query_arg(['action', 'lead_id', '_wpnonce', 'message', 'status_filter', 's', 'paged']);

?>
<div class="pzl-form-container">
    <?php if (!empty($message)): ?>
        <div class="pzl-message <?php echo esc_attr($message_class); ?>"><?php echo esc_html($message); ?></div>
    <?php endif; ?>

    <?php
    //======================================================================
    // 3. DISPLAY VIEWS (New, Edit, List)
    //======================================================================
    
    // --- View: Add New Lead ---
    if ($action === 'new'):
        ?>
        <div class="pzl-card-header" style="border: none; padding-bottom: 0;">
            <h3><i class="fas fa-user-plus"></i> <?php _e('افزودن سرنخ جدید', 'puzzlingcrm'); ?></h3>
            <a href="<?php echo esc_url($base_url); ?>" class="pzl-button">&larr; <?php _e('بازگشت به لیست', 'puzzlingcrm'); ?></a>
        </div>
        <form id="add-lead-form" method="post" class="pzl-form" style="margin-top: 20px;">
            <?php wp_nonce_field('puzzling_add_lead_nonce', 'security'); ?>
            <input type="hidden" name="puzzling_add_lead" value="1">
            <div class="form-group">
                <label for="first_name"><?php _e('نام', 'puzzlingcrm'); ?></label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name"><?php _e('نام خانوادگی', 'puzzlingcrm'); ?></label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="mobile"><?php _e('شماره موبایل', 'puzzlingcrm'); ?></label>
                <input type="text" id="mobile" name="mobile" required>
            </div>
            <div class="form-group">
                <label for="business_name"><?php _e('نام کسب و کار', 'puzzlingcrm'); ?></label>
                <input type="text" id="business_name" name="business_name">
            </div>
             <div class="form-group">
                <label for="status"><?php _e('وضعیت', 'puzzlingcrm'); ?></label>
                <select id="status" name="status">
                    <?php foreach ($lead_statuses as $status): ?>
                        <option value="<?php echo esc_attr($status->slug); ?>"><?php echo esc_html($status->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="notes"><?php _e('یادداشت', 'puzzlingcrm'); ?></label>
                <textarea id="notes" name="notes" rows="4"></textarea>
            </div>
            <div class="form-submit">
                <button type="submit" class="pzl-button"><?php _e('افزودن سرنخ', 'puzzlingcrm'); ?></button>
            </div>
        </form>

    <?php 
    // --- View: Edit Lead ---
    elseif ($action === 'edit' && isset($_GET['lead_id'])):
        $lead_id = intval($_GET['lead_id']);
        $lead = get_post($lead_id);
        if ($lead && $lead->post_type === 'pzl_lead'):
            $mobile = get_post_meta($lead_id, '_mobile', true);
            $business_name = get_post_meta($lead_id, '_business_name', true);
            $current_status_terms = get_the_terms($lead_id, 'lead_status');
            $current_status = !empty($current_status_terms) ? $current_status_terms[0]->slug : '';
            $name_parts = explode(' ', $lead->post_title, 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        ?>
            <div class="pzl-card-header" style="border: none; padding-bottom: 0;">
                <h3><i class="fas fa-user-edit"></i> <?php _e('ویرایش سرنخ', 'puzzlingcrm'); ?>: <?php echo esc_html($lead->post_title); ?></h3>
                <a href="<?php echo esc_url($base_url); ?>" class="pzl-button">&larr; <?php _e('بازگشت به لیست', 'puzzlingcrm'); ?></a>
            </div>
            <form id="edit-lead-form" method="post" class="pzl-form" style="margin-top: 20px;">
                <?php wp_nonce_field('puzzling_edit_lead_nonce_' . $lead_id, 'security'); ?>
                <input type="hidden" name="puzzling_edit_lead" value="1">
                <input type="hidden" name="lead_id" value="<?php echo esc_attr($lead_id); ?>">
                
                <div class="form-group">
                    <label for="first_name"><?php _e('نام', 'puzzlingcrm'); ?></label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name"><?php _e('نام خانوادگی', 'puzzlingcrm'); ?></label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="mobile"><?php _e('شماره موبایل', 'puzzlingcrm'); ?></label>
                    <input type="text" id="mobile" name="mobile" value="<?php echo esc_attr($mobile); ?>" required>
                </div>
                <div class="form-group">
                    <label for="business_name"><?php _e('نام کسب و کار', 'puzzlingcrm'); ?></label>
                    <input type="text" id="business_name" name="business_name" value="<?php echo esc_attr($business_name); ?>">
                </div>
                <div class="form-group">
                    <label for="status"><?php _e('وضعیت', 'puzzlingcrm'); ?></label>
                    <select id="status" name="status">
                        <?php foreach ($lead_statuses as $status): ?>
                            <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($current_status, $status->slug); ?>>
                                <?php echo esc_html($status->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notes"><?php _e('یادداشت', 'puzzlingcrm'); ?></label>
                    <textarea id="notes" name="notes" rows="4"><?php echo esc_textarea($lead->post_content); ?></textarea>
                </div>
                <div class="form-submit">
                    <button type="submit" class="pzl-button"><?php _e('ذخیره تغییرات', 'puzzlingcrm'); ?></button>
                </div>
            </form>
        <?php else: ?>
            <p><?php _e('سرنخ مورد نظر یافت نشد.', 'puzzlingcrm'); ?></p>
        <?php endif; ?>

    <?php 
    // --- View: List All Leads ---
    else:
        // --- Prepare Query Args for Filtering and Search ---
        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status_filter = isset($_GET['status_filter']) ? sanitize_key($_GET['status_filter']) : '';

        $args = [
            'post_type' => 'pzl_lead',
            'posts_per_page' => 20,
            'paged' => $paged,
        ];
        
        if (!empty($search_query)) {
            $args['s'] = $search_query;
        }

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
        ?>
        <div class="pzl-card-header" style="border: none; padding-bottom: 0;">
             <h4><i class="fas fa-users"></i> <?php _e('مدیریت سرنخ‌ها', 'puzzlingcrm'); ?></h4>
             <a href="<?php echo add_query_arg('action', 'new', $base_url); ?>" class="pzl-button"><?php _e('افزودن سرنخ جدید', 'puzzlingcrm'); ?></a>
        </div>
        
        <form method="get" class="pzl-filters" style="margin-top: 20px;">
            <input type="hidden" name="page_id" value="<?php echo get_the_ID(); // Important for shortcodes ?>">
            <input type="text" name="s" placeholder="<?php _e('جستجوی نام یا موبایل...', 'puzzlingcrm'); ?>" value="<?php echo esc_attr($search_query); ?>">
            <select name="status_filter">
                <option value=""><?php _e('همه وضعیت‌ها', 'puzzlingcrm'); ?></option>
                <?php foreach ($lead_statuses as $status): ?>
                    <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($status_filter, $status->slug); ?>>
                        <?php echo esc_html($status->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="pzl-button"><?php _e('فیلتر', 'puzzlingcrm'); ?></button>
        </form>

        <table class="pzl-table" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th><?php _e('نام', 'puzzlingcrm'); ?></th>
                    <th><?php _e('موبایل', 'puzzlingcrm'); ?></th>
                    <th><?php _e('کسب و کار', 'puzzlingcrm'); ?></th>
                    <th><?php _e('وضعیت', 'puzzlingcrm'); ?></th>
                    <th><?php _e('عملیات', 'puzzlingcrm'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($leads_query->have_posts()): while ($leads_query->have_posts()): $leads_query->the_post();
                    $lead_id = get_the_ID();
                    $mobile = get_post_meta($lead_id, '_mobile', true);
                    $business_name = get_post_meta($lead_id, '_business_name', true);
                    $status_terms = get_the_terms($lead_id, 'lead_status');
                    $status_name = !empty($status_terms) ? $status_terms[0]->name : '---';

                    $edit_url = add_query_arg(['action' => 'edit', 'lead_id' => $lead_id], $base_url);
                    $delete_nonce = wp_create_nonce('puzzling_delete_lead_' . $lead_id);
                    $delete_url = add_query_arg(['action' => 'delete', 'lead_id' => $lead_id, '_wpnonce' => $delete_nonce], $base_url);
                ?>
                    <tr>
                        <td><strong><?php the_title(); ?></strong></td>
                        <td><?php echo esc_html($mobile); ?></td>
                        <td><?php echo esc_html($business_name); ?></td>
                        <td><?php echo esc_html($status_name); ?></td>
                        <td>
                            <a href="<?php echo esc_url($edit_url); ?>" class="pzl-button pzl-button-sm"><?php _e('ویرایش', 'puzzlingcrm'); ?></a>
                            <a href="<?php echo esc_url($delete_url); ?>" class="pzl-button pzl-button-sm pzl-button-danger" onclick="return confirm('<?php _e('آیا از حذف این سرنخ مطمئن هستید؟', 'puzzlingcrm'); ?>');"><?php _e('حذف', 'puzzlingcrm'); ?></a>
                            <button class="pzl-button pzl-button-sm open-sms-modal" data-mobile="<?php echo esc_attr($mobile); ?>"><?php _e('ارسال پیامک', 'puzzlingcrm'); ?></button>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5"><?php _e('هیچ سرنخی یافت نشد.', 'puzzlingcrm'); ?></td></tr>
                <?php endif; wp_reset_postdata(); ?>
            </tbody>
        </table>

        <div class="pzl-pagination">
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%', $base_url),
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $leads_query->max_num_pages,
                'prev_text' => __('&laquo; قبلی'),
                'next_text' => __('بعدی &raquo;'),
            ]);
            ?>
        </div>

    <?php endif; ?>
</div>

<div id="pzl-sms-modal" class="pzl-modal">
    <div class="pzl-modal-content">
        <span class="pzl-close">&times;</span>
        <h4><?php _e('ارسال پیامک', 'puzzlingcrm'); ?></h4>
        <form id="pzl-send-sms-form">
            <input type="hidden" id="sms-recipient-mobile" name="mobile">
            <div class="form-group">
                <label for="sms-message"><?php _e('متن پیام', 'puzzlingcrm'); ?></label>
                <textarea id="sms-message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="pzl-button"><?php _e('ارسال', 'puzzlingcrm'); ?></button>
        </form>
    </div>
</div>