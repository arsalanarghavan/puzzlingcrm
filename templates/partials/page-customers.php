<?php
if (!defined('ABSPATH')) exit;
$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user_to_edit = ($user_id > 0) ? get_user_by('ID', $user_id) : null;
?>
<div class="pzl-dashboard-section">
    <?php if ($action === 'edit' || $action === 'add'): ?>
        <h3><?php echo $user_id > 0 ? 'ویرایش مشتری' : 'افزودن مشتری جدید'; ?></h3>
        <a href="<?php echo remove_query_arg(['action', 'user_id']); ?>">&larr; بازگشت به لیست مشتریان</a>
        <form method="post" class="pzl-form-container" style="margin-top:20px;">
            <input type="hidden" name="puzzling_action" value="manage_user">
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
            <input type="hidden" name="role" value="customer">
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
                <label for="password">رمز عبور:</label>
                <input type="password" id="password" name="password" <?php echo $user_id === 0 ? 'required' : ''; ?>>
                <?php if ($user_id > 0): ?><small>برای عدم تغییر، خالی بگذارید.</small><?php endif; ?>
            </div>
            <button type="submit" class="pzl-button pzl-button-primary"><?php echo $user_id > 0 ? 'ذخیره تغییرات' : 'ایجاد مشتری'; ?></button>
        </form>
    <?php else: ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3><span class="dashicons dashicons-admin-users"></span> مدیریت مشتریان</h3>
            <a href="<?php echo add_query_arg(['action' => 'add']); ?>" class="pzl-button pzl-button-primary">افزودن مشتری جدید</a>
        </div>
        <table class="pzl-table">
            <thead><tr><th>نام</th><th>ایمیل</th><th>تاریخ ثبت نام</th><th>عملیات</th></tr></thead>
            <tbody>
                <?php foreach(get_users(['role' => 'customer', 'orderby' => 'display_name', 'order' => 'ASC']) as $customer): ?>
                    <tr>
                        <td><?php echo esc_html($customer->display_name); ?></td>
                        <td><?php echo esc_html($customer->user_email); ?></td>
                        <td><?php echo date_i18n('Y/m/d', strtotime($customer->user_registered)); ?></td>
                        <td><a href="<?php echo add_query_arg(['action' => 'edit', 'user_id' => $customer->ID]); ?>" class="pzl-button pzl-button-secondary">ویرایش</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>