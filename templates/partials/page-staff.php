<?php
if (!defined('ABSPATH')) exit;
$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user_to_edit = ($user_id > 0) ? get_user_by('ID', $user_id) : null;
$staff_roles = ['system_manager' => 'مدیر سیستم', 'finance_manager' => 'مدیر مالی', 'team_member' => 'عضو تیم'];
?>
<div class="pzl-dashboard-section">
    <?php if ($action === 'edit' || $action === 'add'): ?>
        <h3><?php echo $user_id > 0 ? 'ویرایش کارمند' : 'افزودن کارمند جدید'; ?></h3>
        <a href="<?php echo remove_query_arg(['action', 'user_id']); ?>">&larr; بازگشت به لیست کارکنان</a>
        <form method="post" class="pzl-form-container" style="margin-top:20px;">
            <input type="hidden" name="puzzling_action" value="manage_user">
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
            <?php wp_nonce_field('puzzling_manage_user'); ?>
            <div class="form-group">
                <label for="display_name">نام نمایشی:</label>
                <input type="text" id="display_name" name="display_name" value="<?php echo $user_to_edit ? esc_attr($user_to_edit->display_name) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="email">ایمیل:</label>
                <input type="email" id="email" name="email" value="<?php echo $user_to_edit ? esc_attr($user_to_edit->user_email) : ''; ?>" required>
            </div>
             <div class="form-group">
                <label for="role">نقش:</label>
                <select name="role" id="role" required>
                    <?php foreach ($staff_roles as $role_key => $role_name): ?>
                        <option value="<?php echo $role_key; ?>" <?php if($user_to_edit) selected(in_array($role_key, $user_to_edit->roles)); ?>><?php echo $role_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="password">رمز عبور:</label>
                <input type="password" id="password" name="password" <?php echo $user_id === 0 ? 'required' : ''; ?>>
                <?php if ($user_id > 0): ?><small>برای عدم تغییر، خالی بگذارید.</small><?php endif; ?>
            </div>
            <button type="submit" class="pzl-button"><?php echo $user_id > 0 ? 'ذخیره تغییرات' : 'ایجاد کارمند'; ?></button>
        </form>
    <?php else: ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3><i class="fas fa-users-cog"></i> مدیریت کارکنان</h3>
            <a href="<?php echo add_query_arg(['action' => 'add']); ?>" class="pzl-button">افزودن کارمند جدید</a>
        </div>
        <table class="pzl-table">
            <thead><tr><th>نام</th><th>ایمیل</th><th>نقش</th><th>عملیات</th></tr></thead>
            <tbody>
                <?php foreach(get_users(['role__in' => array_keys($staff_roles)]) as $staff): ?>
                    <tr>
                        <td><?php echo esc_html($staff->display_name); ?></td>
                        <td><?php echo esc_html($staff->user_email); ?></td>
                        <td><?php echo esc_html(implode(', ', array_map(function($role) use ($staff_roles){ return $staff_roles[$role] ?? $role; }, $staff->roles))); ?></td>
                        <td><a href="<?php echo add_query_arg(['action' => 'edit', 'user_id' => $staff->ID]); ?>" class="pzl-button pzl-button-sm">ویرایش</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>