<?php
/**
 * Template for the [puzzling_leads] shortcode.
 *
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$lead_statuses = get_option('puzzling_lead_statuses', [['name' => 'جدید', 'color' => '#0073aa']]);
?>
<div class="pzl-form-container">
    <?php if ($action === 'new'): ?>
        <div class="pzl-card-header" style="border: none; padding-bottom: 0;">
            <h3><i class="fas fa-user-plus"></i> <?php _e('افزودن سرنخ جدید', 'puzzlingcrm'); ?></h3>
            <a href="<?php echo remove_query_arg('action'); ?>" class="pzl-button">&larr; <?php _e('بازگشت به لیست', 'puzzlingcrm'); ?></a>
        </div>
        <form id="add-lead-form" method="post" class="pzl-form" style="margin-top: 20px;">
            <?php wp_nonce_field('puzzling_add_lead_nonce', 'security'); ?>
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
                <label for="notes"><?php _e('یادداشت', 'puzzlingcrm'); ?></label>
                <textarea id="notes" name="notes" rows="4"></textarea>
            </div>
            <div class="form-submit">
                <button type="submit" class="pzl-button"><?php _e('افزودن سرنخ', 'puzzlingcrm'); ?></button>
            </div>
        </form>
    <?php else: ?>
        <div class="pzl-card-header" style="border: none; padding-bottom: 0;">
             <h4><i class="fas fa-users"></i> <?php _e('مدیریت سرنخ‌ها', 'puzzlingcrm'); ?></h4>
             <a href="<?php echo add_query_arg('action', 'new'); ?>" class="pzl-button"><?php _e('افزودن سرنخ جدید', 'puzzlingcrm'); ?></a>
        </div>
        
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
                $leads = get_posts(['post_type' => 'pzl_lead', 'posts_per_page' => -1]);
                if (!empty($leads)): foreach ($leads as $lead): 
                    $mobile = get_post_meta($lead->ID, '_mobile', true);
                    $business_name = get_post_meta($lead->ID, '_business_name', true);
                    $status_terms = get_the_terms($lead->ID, 'lead_status');
                    $status_name = !empty($status_terms) ? $status_terms[0]->name : '---';
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($lead->post_title); ?></strong></td>
                        <td><?php echo esc_html($mobile); ?></td>
                        <td><?php echo esc_html($business_name); ?></td>
                        <td><?php echo esc_html($status_name); ?></td>
                        <td>
                            <button class="pzl-button pzl-button-sm open-sms-modal" data-mobile="<?php echo esc_attr($mobile); ?>"><?php _e('ارسال پیامک', 'puzzlingcrm'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5"><?php _e('هیچ سرنخی یافت نشد.', 'puzzlingcrm'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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