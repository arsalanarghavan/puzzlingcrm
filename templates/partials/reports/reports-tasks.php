<?php
/**
 * Tasks Reports Template
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

// Stats Calculation
$total_tasks = wp_count_posts('task')->publish;
$done_tasks_count = count(get_posts(['post_type' => 'task', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done']]]));
$active_tasks = $total_tasks - $done_tasks_count;

$today_str = date('Y-m-d');
$overdue_tasks_query = new WP_Query([
    'post_type' => 'task',
    'posts_per_page' => -1,
    'meta_query' => [
        'relation' => 'AND',
        ['key' => '_due_date', 'value' => $today_str, 'compare' => '<'],
        ['key' => '_due_date', 'value' => '', 'compare' => '!=']
    ],
    'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done', 'operator' => 'NOT IN']]
]);
$overdue_tasks_count = $overdue_tasks_query->post_count;
?>
<h3><i class="fas fa-chart-pie"></i> گزارش وظایف</h3>
<div class="pzl-dashboard-stats" style="margin-top:20px;">
    <div class="stat-widget">
        <h4>کل وظایف ثبت شده</h4>
        <span class="stat-number"><?php echo esc_html($total_tasks); ?></span>
    </div>
    <div class="stat-widget">
        <h4>وظایف فعال</h4>
        <span class="stat-number"><?php echo esc_html($active_tasks); ?></span>
    </div>
     <div class="stat-widget">
        <h4>وظایف انجام شده</h4>
        <span class="stat-number"><?php echo esc_html($done_tasks_count); ?></span>
    </div>
    <div class="stat-widget">
        <h4>وظایف دارای تاخیر</h4>
        <span class="stat-number" style="color: #dc3545;"><?php echo esc_html($overdue_tasks_count); ?></span>
    </div>
</div>

<?php if ($overdue_tasks_query->have_posts()): ?>
<div class="pzl-dashboard-section">
    <h4><i class="fas fa-exclamation-triangle"></i> لیست وظایف دارای تاخیر</h4>
    <table class="pzl-table">
        <thead>
            <tr><th>عنوان وظیفه</th><th>مربوط به پروژه</th><th>تخصیص به</th><th>ددلاین</th></tr>
        </thead>
        <tbody>
        <?php while($overdue_tasks_query->have_posts()): $overdue_tasks_query->the_post();
            $project_id = get_post_meta(get_the_ID(), '_project_id', true);
            $assigned_id = get_post_meta(get_the_ID(), '_assigned_to', true);
        ?>
            <tr>
                <td><?php the_title(); ?></td>
                <td><?php echo $project_id ? get_the_title($project_id) : '---'; ?></td>
                <td><?php echo $assigned_id ? get_the_author_meta('display_name', $assigned_id) : '---'; ?></td>
                <td><?php echo get_post_meta(get_the_ID(), '_due_date', true); ?></td>
            </tr>
        <?php endwhile; wp_reset_postdata(); ?>
        </tbody>
    </table>
</div>
<?php endif; ?>