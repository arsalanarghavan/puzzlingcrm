<?php
/**
 * My Profile Page Template for all logged-in users.
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!is_user_logged_in()) return;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$phone_number = get_user_meta($user_id, 'pzl_mobile_phone', true);
?>
<div class="pzl-dashboard-section">
    <div class="pzl-card-header">
        <h3><i class="ri-user-settings-line"></i> <?php esc_html_e('ویرایش پروفایل من', 'puzzlingcrm'); ?></h3>
    </div>
    <div class="pzl-card">
        <form method="post" class="pzl-form pzl-ajax-form" id="pzl-my-profile-form" data-action="puzzling_update_my_profile" enctype="multipart/form-data">
            <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>

            <div class="pzl-profile-main-info">
                <div class="pzl-profile-avatar-column">
                    <label><?php esc_html_e('عکس پروفایل', 'puzzlingcrm'); ?></label>
                    <div class="pzl-avatar-container">
                        <?php echo get_avatar($user_id, 200); ?>
                    </div>
                    <input type="file" name="pzl_profile_picture" id="pzl_profile_picture" accept="image/*">
                </div>
                <div class="pzl-profile-details-column">
                    <h4><?php esc_html_e('اطلاعات اصلی و ورود', 'puzzlingcrm'); ?></h4>
                    <div class="pzl-form-row">
                        <div class="form-group half-width">
                            <label for="first_name"><?php esc_html_e('نام', 'puzzlingcrm'); ?></label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>">
                        </div>
                        <div class="form-group half-width">
                            <label for="last_name"><?php esc_html_e('نام خانوادگی', 'puzzlingcrm'); ?></label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" required>
                        </div>
                    </div>
                     <div class="pzl-form-row">
                        <div class="form-group half-width">
                            <label for="email"><?php esc_html_e('ایمیل (غیرقابل تغییر)', 'puzzlingcrm'); ?></label>
                            <input type="email" id="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" disabled>
                        </div>
                        <div class="form-group half-width">
                            <label for="pzl_mobile_phone"><?php esc_html_e('شماره موبایل', 'puzzlingcrm'); ?></label>
                            <input type="text" id="pzl_mobile_phone" name="pzl_mobile_phone" value="<?php echo esc_attr($phone_number); ?>" class="ltr-input">
                        </div>
                    </div>
                    <hr>
                    <h4><?php esc_html_e('تغییر رمز عبور', 'puzzlingcrm'); ?></h4>
                    <p class="description"><?php esc_html_e('برای تغییر رمز عبور، هر دو فیلد زیر را پر کنید. در غیر این صورت، آن را خالی بگذارید.', 'puzzlingcrm'); ?></p>
                     <div class="pzl-form-row">
                        <div class="form-group half-width">
                            <label for="password"><?php esc_html_e('رمز عبور جدید', 'puzzlingcrm'); ?></label>
                            <input type="password" id="password" name="password">
                        </div>
                        <div class="form-group half-width">
                            <label for="password_confirm"><?php esc_html_e('تکرار رمز عبور جدید', 'puzzlingcrm'); ?></label>
                            <input type="password" id="password_confirm" name="password_confirm">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-submit">
                <button type="submit" class="pzl-button"><?php esc_html_e('ذخیره تغییرات', 'puzzlingcrm'); ?></button>
            </div>
        </form>
    </div>
</div>