<?php
if (!defined('ABSPATH')) exit;
$plans = get_terms(['taxonomy' => 'subscription_plan', 'hide_empty' => false]);
$customers = get_users(['role' => 'customer']);
$subscriptions = get_posts(['post_type' => 'pzl_subscription', 'posts_per_page' => -1]);
?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-update-alt"></span> مدیریت اشتراک‌ها</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="pzl-form-container">
            <h4>مدیریت پلن‌های اشتراک</h4>
            <form method="post">
                <input type="hidden" name="puzzling_action" value="manage_subscription_plan">
                <?php wp_nonce_field('puzzling_manage_subscription_plan'); ?>
                <div class="form-group"><label>نام پلن:</label><input type="text" name="plan_name" required></div>
                <div class="form-group"><label>مبلغ (تومان):</label><input type="number" name="price" required></div>
                <div class="form-group"><label>دوره:</label><select name="interval" required><option value="month">ماهانه</option><option value="year">سالانه</option></select></div>
                <button type="submit" class="pzl-button pzl-button-secondary">ذخیره پلن</button>
            </form>
            <hr>
            <ul><?php foreach($plans as $plan) { echo '<li>' . esc_html($plan->name) . '</li>'; } ?></ul>
        </div>
        <div class="pzl-form-container">
            <h4>تخصیص اشتراک به مشتری</h4>
            <form method="post">
                <input type="hidden" name="puzzling_action" value="assign_subscription">
                <?php wp_nonce_field('puzzling_assign_subscription'); ?>
                <div class="form-group"><label>مشتری:</label><select name="customer_id" required><option value="">-- انتخاب --</option><?php foreach($customers as $c){ echo "<option value='{$c->ID}'>{$c->display_name}</option>"; } ?></select></div>
                <div class="form-group"><label>پلن:</label><select name="plan_id" required><option value="">-- انتخاب --</option><?php foreach($plans as $p){ echo "<option value='{$p->term_id}'>{$p->name}</option>"; } ?></select></div>
                <div class="form-group"><label>تاریخ شروع:</label><input type="date" name="start_date" required></div>
                <button type="submit" class="pzl-button pzl-button-primary">ثبت اشتراک</button>
            </form>
        </div>
    </div>
    <hr>
    <h4>لیست اشتراک‌های فعال</h4>
    <table class="pzl-table">
        <thead><tr><th>مشتری</th><th>پلن</th><th>وضعیت</th><th>تاریخ شروع</th><th>تاریخ پرداخت بعدی</th></tr></thead>
        <tbody>
            <?php if (!empty($subscriptions)): foreach($subscriptions as $sub):
                $plan_id = get_post_meta($sub->ID, '_plan_id', true);
                $plan = $plan_id ? get_term($plan_id, 'subscription_plan') : null;
                $status_terms = get_the_terms($sub->ID, 'subscription_status');
            ?>
            <tr>
                <td><?php echo get_the_author_meta('display_name', $sub->post_author); ?></td>
                <td><?php echo $plan ? $plan->name : '---'; ?></td>
                <td><?php echo !empty($status_terms) ? $status_terms[0]->name : '---'; ?></td>
                <td><?php echo get_post_meta($sub->ID, '_start_date', true); ?></td>
                <td><?php echo get_post_meta($sub->ID, '_next_payment_date', true); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5">هیچ اشتراکی ثبت نشده است.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>