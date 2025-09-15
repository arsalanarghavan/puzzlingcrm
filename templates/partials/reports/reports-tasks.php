<?php
/**
 * Tasks Reports Template - V2 with Charts and Advanced Stats
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

// --- Stats Calculation ---
$total_tasks = wp_count_posts('task')->publish;

// Task counts by status
$task_statuses = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false]);
$status_counts = [];
$done_tasks_count = 0;
foreach ($task_statuses as $status) {
    $count = count(get_posts(['post_type' => 'task', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => $status->slug]]]));
    $status_counts[$status->name] = $count;
    if ($status->slug === 'done') {
        $done_tasks_count = $count;
    }
}
$active_tasks_count = $total_tasks - $done_tasks_count;

// Overdue tasks
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


// Performance by team member
$team_members_stats = [];
$team_users = get_users(['role__in' => ['team_member', 'system_manager', 'administrator']]);
foreach ($team_users as $user) {
    $completed_count = count(get_posts([
        'post_type' => 'task', 'posts_per_page' => -1,
        'meta_key' => '_assigned_to', 'meta_value' => $user->ID,
        'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done']]
    ]));
    $active_count = count(get_posts([
        'post_type' => 'task', 'posts_per_page' => -1,
        'meta_key' => '_assigned_to', 'meta_value' => $user->ID,
        'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done', 'operator' => 'NOT IN']]
    ]));
    if ($completed_count > 0 || $active_count > 0) {
        $team_members_stats[$user->display_name] = ['completed' => $completed_count, 'active' => $active_count];
    }
}
?>

<div class="finance-report-grid">
    <div class="report-card">
        <h4><i class="fas fa-tasks"></i> کل وظایف</h4>
        <span class="stat-number"><?php echo esc_html($total_tasks); ?></span>
    </div>
    <div class="report-card">
        <h4><i class="fas fa-spinner"></i> وظایف فعال</h4>
        <span class="stat-number"><?php echo esc_html($active_tasks_count); ?></span>
    </div>
    <div class="report-card">
        <h4><i class="fas fa-check-circle"></i> وظایف انجام شده</h4>
        <span class="stat-number"><?php echo esc_html($done_tasks_count); ?></span>
    </div>
    <div class="report-card">
        <h4><i class="fas fa-exclamation-triangle"></i> وظایف دارای تاخیر</h4>
        <span class="stat-number" style="color: var(--pzl-danger-color);"><?php echo esc_html($overdue_tasks_count); ?></span>
    </div>
</div>

<div class="pzl-reports-grid">
    <div class="pzl-card">
        <h4><i class="fas fa-pie-chart"></i> وظایف بر اساس وضعیت</h4>
        <div class="pzl-chart-container pzl-pie-chart-container">
            <?php if (!empty($status_counts) && $total_tasks > 0): ?>
                <div class="pzl-chart-legend">
                <?php 
                $hue_step = 360 / count($status_counts);
                $current_hue = 0;
                foreach($status_counts as $status => $count): 
                    $percentage = round(($count / $total_tasks) * 100);
                ?>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: hsl(<?php echo $current_hue; ?>, 70%, 50%);"></span>
                        <?php echo esc_html($status) . ' (' . esc_html($count) . ' - ' . esc_html($percentage) . '%)'; ?>
                    </div>
                <?php $current_hue += $hue_step; endforeach; ?>
                </div>
            <?php else: ?>
                <p>داده‌ای برای نمایش نمودار وجود ندارد.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="pzl-card">
        <h4><i class="fas fa-users"></i> عملکرد اعضای تیم</h4>
        <div class="pzl-team-performance">
            <?php if(!empty($team_members_stats)): ?>
                 <?php foreach($team_members_stats as $name => $stats): 
                    $total = $stats['active'] + $stats['completed'];
                    $completed_perc = $total > 0 ? round(($stats['completed'] / $total) * 100) : 0;
                 ?>
                 <div class="team-member-stat">
                    <span class="member-name"><?php echo esc_html($name); ?></span>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo esc_attr($completed_perc); ?>%;"></div>
                    </div>
                    <span class="member-numbers"><?php echo esc_html($stats['completed']); ?>/<?php echo esc_html($total); ?> انجام شده</span>
                 </div>
                 <?php endforeach; ?>
            <?php else: ?>
                <p>آماری برای نمایش وجود ندارد.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($overdue_tasks_query->have_posts()): ?>
<div class="pzl-card">
    <h4><i class="fas fa-list-ul"></i> لیست وظایف دارای تاخیر</h4>
    <table class="pzl-table">
        <thead>
            <tr><th>عنوان وظیفه</th><th>پروژه</th><th>تخصیص به</th><th>ددلاین</th></tr>
        </thead>
        <tbody>
        <?php while($overdue_tasks_query->have_posts()): $overdue_tasks_query->the_post();
            $project_id = get_post_meta(get_the_ID(), '_project_id', true);
            $assigned_id = get_post_meta(get_the_ID(), '_assigned_to', true);
        ?>
            <tr>
                <td><a href="#" class="open-task-modal" data-task-id="<?php echo get_the_ID(); ?>"><?php the_title(); ?></a></td>
                <td><?php echo $project_id ? get_the_title($project_id) : '---'; ?></td>
                <td><?php echo $assigned_id ? get_the_author_meta('display_name', $assigned_id) : '---'; ?></td>
                <td><?php echo get_post_meta(get_the_ID(), '_due_date', true); ?></td>
            </tr>
        <?php endwhile; wp_reset_postdata(); ?>
        </tbody>
    </table>
</div>
<?php endif; ?>