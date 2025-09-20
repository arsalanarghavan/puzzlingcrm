<?php
/**
 * Template for System Manager to Manage Customers - VISUALLY REVAMPED & UPGRADED
 * Lists all users with stats, live search, and provides a comprehensive edit/add form.
 * @package PuzzlingCRM
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user_to_edit = ($user_id > 0) ? get_user_by('ID', $user_id) : null;
?>
<div class="pzl-dashboard-section">
    <?php if ($action === 'edit' || $action === 'add'):
        $phone_number = $user_to_edit ? get_user_meta($user_to_edit->ID, 'pzl_mobile_phone', true) : '';
    ?>
        <div class="pzl-card-header">
            <h3><i class="fas fa-user-edit"></i> <?php echo $user_id > 0 ? 'ویرایش اطلاعات کاربر' : 'افزودن کاربر جدید'; ?></h3>
            <a href="<?php echo remove_query_arg(['action', 'user_id']); ?>" class="pzl-button">&larr; بازگشت به لیست کاربران</a>
        </div>
        <div class="pzl-card">
            <form method="post" class="pzl-form pzl-ajax-form" id="pzl-customer-form" data-action="puzzling_manage_user">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <?php wp_nonce_field('puzzlingcrm-ajax-nonce', 'security'); ?>

                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="first_name">نام</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo $user_to_edit ? esc_attr($user_to_edit->first_name) : ''; ?>">
                    </div>
                    <div class="form-group half-width">
                        <label for="last_name">نام خانوادگی</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo $user_to_edit ? esc_attr($user_to_edit->last_name) : ''; ?>" required>
                    </div>
                </div>

                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="email">ایمیل</label>
                        <input type="email" id="email" name="email" value="<?php echo $user_to_edit ? esc_attr($user_to_edit->user_email) : ''; ?>" required>
                    </div>
                    <div class="form-group half-width">
                        <label for="pzl_mobile_phone">شماره موبایل</label>
                        <input type="text" id="pzl_mobile_phone" name="pzl_mobile_phone" value="<?php echo esc_attr($phone_number); ?>" class="ltr-input">
                    </div>
                </div>
                 
                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="role">نقش کاربری</label>
                        <select name="role" id="role" required>
                            <?php
                            $editable_roles = get_editable_roles();
                            $current_role = $user_to_edit && !empty($user_to_edit->roles) ? $user_to_edit->roles[0] : 'customer';
                            foreach ($editable_roles as $role_key => $role_details) {
                                echo '<option value="' . esc_attr($role_key) . '" ' . selected($current_role, $role_key, false) . '>' . esc_html($role_details['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group half-width">
                        <label for="password">رمز عبور</label>
                        <input type="password" id="password" name="password" <?php echo $user_id === 0 ? 'required' : ''; ?>>
                        <?php if ($user_id > 0): ?><p class="description">برای عدم تغییر، خالی بگذارید.</p><?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group form-submit">
                    <button type="submit" class="pzl-button"><?php echo $user_id > 0 ? 'ذخیره تغییرات' : 'ایجاد کاربر'; ?></button>
                </div>
            </form>
        </div>
    <?php else: 
        // --- Calculate Stats ---
        $user_counts = count_users();
        $total_users = $user_counts['total_users'];
        $customer_count = $user_counts['avail_roles']['customer'] ?? 0;
        $staff_roles = ['system_manager', 'finance_manager', 'team_member', 'administrator'];
        $staff_count = 0;
        foreach($staff_roles as $role) {
            $staff_count += $user_counts['avail_roles'][$role] ?? 0;
        }
    ?>
        <div class="pzl-dashboard-stats-grid">
             <div class="stat-widget-card gradient-1">
                <div class="stat-widget-icon"><i class="fas fa-users"></i></div>
                <div class="stat-widget-content">
                    <span class="stat-number"><?php echo esc_html($total_users); ?></span>
                    <span class="stat-title">کل کاربران</span>
                </div>
            </div>
            <div class="stat-widget-card gradient-2">
                <div class="stat-widget-icon"><i class="fas fa-user-tie"></i></div>
                <div class="stat-widget-content">
                    <span class="stat-number"><?php echo esc_html($customer_count); ?></span>
                    <span class="stat-title">مشتریان</span>
                </div>
            </div>
            <div class="stat-widget-card gradient-4">
                <div class="stat-widget-icon"><i class="fas fa-user-shield"></i></div>
                <div class="stat-widget-content">
                    <span class="stat-number"><?php echo esc_html($staff_count); ?></span>
                    <span class="stat-title">کارمندان</span>
                </div>
            </div>
        </div>

        <div class="pzl-card">
             <div class="pzl-card-header">
                <h3><i class="fas fa-users"></i> مدیریت کاربران و مشتریان</h3>
                <a href="<?php echo add_query_arg(['action' => 'add']); ?>" class="pzl-button">افزودن کاربر جدید</a>
            </div>

            <div class="pzl-search-form-container">
                <div class="form-group">
                    <i class="fas fa-search pzl-search-icon"></i>
                    <input type="search" id="user-live-search-input" placeholder="جستجوی زنده بر اساس نام، موبایل، کد ملی و...">
                </div>
            </div>

            <table class="pzl-table" style="margin-top: 20px;">
                <thead><tr><th>نام کامل</th><th>ایمیل</th><th>نقش</th><th>تاریخ ثبت‌نام</th><th>عملیات</th></tr></thead>
                <tbody id="pzl-users-table-body">
                    <?php
                    $all_users = get_users(['orderby' => 'display_name', 'order' => 'ASC']);
                    if (empty($all_users)) {
                        echo '<tr><td colspan="5">هیچ کاربری یافت نشد.</td></tr>';
                    } else {
                        foreach($all_users as $user): ?>
                            <tr>
                                <td><?php echo get_avatar($user->ID, 32); ?> <?php echo esc_html($user->display_name); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo !empty($user->roles) ? esc_html(wp_roles()->roles[$user->roles[0]]['name']) : '---'; ?></td>
                                <td><?php echo date_i18n('Y/m/d', strtotime($user->user_registered)); ?></td>
                                <td>
                                    <a href="<?php echo add_query_arg(['action' => 'edit', 'user_id' => $user->ID]); ?>" class="pzl-button pzl-button-sm">ویرایش</a>
                                    <button class="pzl-button pzl-button-sm send-sms-btn" data-user-id="<?php echo esc_attr($user->ID); ?>" data-user-name="<?php echo esc_attr($user->display_name); ?>"><i class="fas fa-sms"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; 
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="pzl-sms-modal-backdrop" style="display: none;"></div>
<div id="pzl-sms-modal-wrap" style="display: none;">
    <div id="pzl-sms-modal-content">
        <button id="pzl-close-sms-modal-btn">&times;</button>
        <div id="pzl-sms-modal-body">
            <h3>ارسال پیامک به <span id="sms-modal-user-name"></span></h3>
            <form id="pzl-send-sms-form">
                <div class="form-group">
                    <label for="sms_message">متن پیام:</label>
                    <textarea id="sms_message" name="sms_message" rows="5" required></textarea>
                </div>
                <input type="hidden" id="sms-modal-user-id" name="user_id">
                <div class="form-submit">
                    <button type="submit" class="pzl-button">ارسال پیامک</button>
                </div>
            </form>
        </div>
    </div>
</div>