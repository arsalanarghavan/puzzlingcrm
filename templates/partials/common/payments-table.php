<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$is_manager_view = current_user_can('manage_options');
$query_args = [
    'post_type' => 'contract',
    'posts_per_page' => -1,
    'post_status' => 'publish',
];
if(!$is_manager_view){
    $query_args['author'] = get_current_user_id();
}
$contracts = get_posts($query_args);
?>
<?php if (empty($contracts)) : ?>
    <p>هیچ برنامه پرداختی ثبت نشده است.</p>
<?php else : ?>
<table class="pzl-table">
    <thead>
        <tr>
            <?php if($is_manager_view) echo '<th>مشتری</th>'; ?>
            <th>پروژه</th>
            <th>مبلغ قسط (تومان)</th>
            <th>تاریخ سررسید</th>
            <th>وضعیت</th>
            <th>عملیات</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($contracts as $contract) {
            // ... (The rest of the table rendering logic from dashboard-client.php) ...
        }
        ?>
    </tbody>
</table>
<?php endif; ?>
<style>
/* ... table styles ... */
</style>