<?php
/**
 * Template for viewing system logs (errors, bugs, etc.) for managers.
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$logs_query = new WP_Query([
    'post_type' => 'puzzling_log',
    'posts_per_page' => 20,
    'paged' => $paged,
    'meta_key' => '_log_type',
    'meta_value' => 'system_error', // Only fetch system errors
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<h3><i class="ri-settings-3-lines"></i> لاگ خطاهای سیستم</h3>
<p class="description">در این بخش خطاهای سیستمی، مشکلات مربوط به API و سایر باگ‌های ثبت شده نمایش داده می‌شود.</p>

<?php if ($logs_query->have_posts()): ?>
<table class="pzl-table">
    <thead>
        <tr>
            <th>عنوان خطا</th>
            <th>جزئیات</th>
            <th>تاریخ</th>
        </tr>
    </thead>
    <tbody>
        <?php while($logs_query->have_posts()): $logs_query->the_post(); ?>
        <tr>
            <td><strong><?php the_title(); ?></strong></td>
            <td><?php echo wp_kses_post(get_the_content()); ?></td>
            <td><?php echo esc_html(get_the_date('Y/m/d H:i')); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="pagination">
    <?php
    echo paginate_links([
        'base' => add_query_arg('paged', '%#%'),
        'total' => $logs_query->max_num_pages,
        'current' => max( 1, $paged ),
        'format' => '?paged=%#%',
    ]);
    ?>
</div>
<?php wp_reset_postdata(); else: ?>
<div class="pzl-empty-state" style="margin-top: 20px;">
    <i class="ri-checkbox-circle-line"></i>
    <h4>سیستم در وضعیت پایدار است</h4>
    <p>هیچ خطای سیستمی برای نمایش وجود ندارد.</p>
</div>
<?php endif; ?>