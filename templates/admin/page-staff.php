<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<h1><span class="dashicons dashicons-groups"></span> مدیریت کارکنان</h1>
<p>در این بخش می‌توانید کاربران با نقش‌های مدیریتی افزونه (مدیر سیستم، مدیر مالی، عضو تیم) را مشاهده کنید.</p>
<?php
    $staff_roles = ['system_manager', 'finance_manager', 'team_member', 'administrator'];
    $staff = get_users(['role__in' => $staff_roles]);
?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>نام نمایشی</th>
            <th>ایمیل</th>
            <th>نقش‌ها</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($staff as $user): ?>
            <tr>
                <td><?php echo esc_html($user->display_name); ?></td>
                <td><?php echo esc_html($user->user_email); ?></td>
                <td><?php echo implode(', ', array_map('ucfirst', $user->roles)); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>