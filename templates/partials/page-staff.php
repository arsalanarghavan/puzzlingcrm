<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-groups"></span> مدیریت کارکنان</h3>
     <table class="pzl-table">
        <thead>
            <tr><th>نام</th><th>ایمیل</th><th>نقش</th></tr>
        </thead>
        <tbody>
            <?php foreach(get_users(['role__in' => ['system_manager', 'finance_manager', 'team_member']]) as $user): ?>
                <tr>
                    <td><?php echo esc_html($user->display_name); ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>