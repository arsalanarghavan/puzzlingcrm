<?php
/**
 * Template for viewing a specific staff member's logs.
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') || !isset($_GET['user_id']) ) return;

$staff_id = intval($_GET['user_id']);
$staff_user = get_userdata($staff_id);

if (!$staff_user) {
    echo '<p>کاربر مورد نظر یافت نشد.</p>';
    return;
}

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$logs_query = new WP_Query([
    'post_type' => 'puzzling_log',
    'posts_per_page' => 20,
    'paged' => $paged,
    'author' => $staff_id, // Filter by the staff member
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>
<div class="pzl-card-header">
    <h3><i class="fas fa-history"></i> تاریخچه عملیات: <?php echo esc_html($staff_user->display_name); ?></h3>
    <a href="<?php echo remove_query_arg(['action', 'user_id']); ?>" class="pzl-button">&larr; بازگشت به لیست کارکنان</a>
</div>

<div class="pzl-card">
    <?php if ($logs_query->have_posts()): ?>
    <table class="pzl-table">
        <thead>
            <tr>
                <th>رویداد</th>
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
    <div class="pzl-empty-state">
        <h4>لاگی یافت نشد</h4>
        <p>هیچ تاریخچه عملیاتی برای این کاربر ثبت نشده است.</p>
    </div>
    <?php endif; ?>
</div>