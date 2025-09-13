<?php
/**
 * Template for System Manager to Manage Customers - VISUALLY REVAMPED
 * Lists all users and provides a comprehensive edit/add form.
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
        $phone_number = $user_to_edit ? get_user_meta($user_to_edit->ID, 'puzzling_phone_number', true) : '';
    ?>
        <div class="pzl-card-header">
            <h3><i class="fas fa-user-edit"></i> <?php echo $user_id > 0 ? 'ویرایش اطلاعات کاربر' : 'افزودن کاربر جدید'; ?></h3>
            <a href="<?php echo remove_query_arg(['action', 'user_id']); ?>" class="pzl-button">&larr; بازگشت به لیست کاربران</a>
        </div>
        <div class="pzl-card">
            <form method="post" class="pzl-form">
                <input type="hidden" name="puzzling_action" value="manage_user">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <?php wp_nonce_field('puzzling_manage_user'); ?>

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
                        <label for="puzzling_phone_number">شماره موبایل</label>
                        <input type="text" id="puzzling_phone_number" name="puzzling_phone_number" value="<?php echo esc_attr($phone_number); ?>" class="ltr-input">
                    </div>
                </div>
                 
                <div class="pzl-form-row">
                    <div class="form-group half-width">
                        <label for="role">نقش کاربری</label>
                        <select name="role" id="role" required>
                            <?php 
                            $editable_roles = get_editable_roles();
                            $current_role = $user_to_edit ? $user_to_edit->roles[0] : 'customer';
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
    <?php else: ?>
        <div class="pzl-card-header">
            <h3><i class="fas fa-users"></i> مدیریت کاربران و مشتریان</h3>
            <a href="<?php echo add_query_arg(['action' => 'add']); ?>" class="pzl-button">افزودن کاربر جدید</a>
        </div>
        <div class="pzl-card">
            <table class="pzl-table">
                <thead><tr><th>نام کامل</th><th>ایمیل</th><th>نقش</th><th>تاریخ ثبت‌نام</th><th>عملیات</th></tr></thead>
                <tbody>
                    <?php 
                    $all_users = get_users(['orderby' => 'display_name', 'order' => 'ASC']);
                    foreach($all_users as $user): ?>
                        <tr>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo !empty($user->roles) ? esc_html(wp_roles()->roles[$user->roles[0]]['name']) : '---'; ?></td>
                            <td><?php echo date_i18n('Y/m/d', strtotime($user->user_registered)); ?></td>
                            <td><a href="<?php echo add_query_arg(['action' => 'edit', 'user_id' => $user->ID]); ?>" class="pzl-button pzl-button-sm">ویرایش</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>