<?php
/**
 * Team Member Dashboard Template - V2 (Awesome Edition)
 * Provides detailed stats, charts, and task lists for the team member.
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// --- Fetch & Calculate Stats for the current team member ---
$all_tasks_query_args = [
    'post_type' => 'task',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_assigned_to',
            'value' => $user_id,
        ],
    ],
];

$all_tasks = get_posts($all_tasks_query_args);
$total_tasks_count = count($all_tasks);
$project_ids = [];
$status_counts = [];
$completed_tasks_count = 0;
$overdue_tasks_count = 0;
$today_str = date('Y-m-d');

foreach ($all_tasks as $task) {
    // Collect project IDs
    $project_id = get_post_meta($task->ID, '_project_id', true);
    if ($project_id) {
        $project_ids[] = $project_id;
    }

    // Count tasks by status
    $statuses = get_the_terms($task->ID, 'task_status');
    if ($statuses && !is_wp_error($statuses)) {
        $status_name = $statuses[0]->name;
        $status_slug = $statuses[0]->slug;
        
        if (!isset($status_counts[$status_name])) {
            $status_counts[$status_name] = 0;
        }
        $status_counts[$status_name]++;
        
        if ($status_slug === 'done') {
            $completed_tasks_count++;
        } else {
            // Check for overdue tasks
            $due_date = get_post_meta($task->ID, '_due_date', true);
            if ($due_date && $due_date < $today_str) {
                $overdue_tasks_count++;
            }
        }
    }
}

$active_tasks_count = $total_tasks_count - $completed_tasks_count;
$total_projects_count = count(array_unique($project_ids));

?>

<div class="pzl-dashboard-section">
    <h3><i class="fas fa-user-circle"></i> داشبورد عملکرد شما</h3>

    <div class="pzl-dashboard-stats-grid">
        <div class="stat-widget-card gradient-1">
            <div class="stat-widget-icon"><i class="fas fa-briefcase"></i></div>
            <div class="stat-widget-content">
                <span class="stat-number"><?php echo esc_html($total_projects_count); ?></span>
                <span class="stat-title">پروژه‌های درگیر</span>
            </div>
        </div>
        <div class="stat-widget-card gradient-2">
            <div class="stat-widget-icon"><i class="fas fa-spinner"></i></div>
            <div class="stat-widget-content">
                <span class="stat-number"><?php echo esc_html($active_tasks_count); ?></span>
                <span class="stat-title">وظایف فعال</span>
            </div>
        </div>
        <div class="stat-widget-card gradient-4">
            <div class="stat-widget-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-widget-content">
                <span class="stat-number"><?php echo esc_html($completed_tasks_count); ?></span>
                <span class="stat-title">وظایف تکمیل‌شده</span>
            </div>
        </div>
        <div class="stat-widget-card" style="background: linear-gradient(45deg, #dc3545, #b21f2d);">
            <div class="stat-widget-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-widget-content">
                <span class="stat-number"><?php echo esc_html($overdue_tasks_count); ?></span>
                <span class="stat-title">وظایف دارای تأخیر</span>
            </div>
        </div>
    </div>

    <div class="pzl-dashboard-grid-2-col">
        <div class="pzl-card">
            <div class="pzl-card-header">
                <h3><i class="fas fa-chart-pie"></i> وظایف شما بر اساس وضعیت</h3>
            </div>
            <div class="pzl-chart-container pzl-pie-chart-container" style="height: auto; min-height: 300px; display: block;">
                <?php if (!empty($status_counts) && $total_tasks_count > 0): ?>
                    <div class="pzl-chart-legend">
                    <?php 
                    $hue_step = 360 / count($status_counts);
                    $current_hue = 0;
                    foreach($status_counts as $status => $count): 
                        $percentage = round(($count / $total_tasks_count) * 100);
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
            <div class="pzl-card-header">
                <h3><i class="fas fa-list-ul"></i> نگاه سریع به وظایف</h3>
            </div>
            <div class="pzl-activity-feed">
                <h5><i class="fas fa-clock"></i> وظایف نزدیک به ددلاین یا معوق</h5>
                <ul class="pzl-activity-list">
                <?php 
                $upcoming_tasks = get_posts([
                    'post_type' => 'task', 'posts_per_page' => 5,
                    'meta_query' => [
                        'relation' => 'AND',
                        ['_key' => '_assigned_to', 'value' => $user_id],
                        ['_key' => '_due_date', 'value' => '', 'compare' => '!=']
                    ],
                    'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done', 'operator' => 'NOT IN']],
                    'meta_key' => '_due_date', 'orderby' => 'meta_value', 'order' => 'ASC'
                ]);
                if (!empty($upcoming_tasks)): foreach($upcoming_tasks as $task): ?>
                     <li>
                        <a href="#" class="open-task-modal" data-task-id="<?php echo esc_attr($task->ID); ?>"><?php echo esc_html($task->post_title); ?></a>
                        <span class="meta" style="<?php echo (get_post_meta($task->ID, '_due_date', true) < $today_str) ? 'color: var(--pzl-danger-color); font-weight: bold;' : ''; ?>">
                            <?php echo esc_html(date_i18n('Y/m/d', strtotime(get_post_meta($task->ID, '_due_date', true)))); ?>
                        </span>
                    </li>
                <?php endforeach; else: ?><li>موردی یافت نشد.</li><?php endif; ?>
                </ul>
                <h5><i class="fas fa-check"></i> وظایف اخیراً تکمیل شده</h5>
                <ul class="pzl-activity-list">
                <?php 
                $done_tasks = get_posts([
                    'post_type' => 'task', 'posts_per_page' => 5,
                    'meta_key' => '_assigned_to', 'meta_value' => $user_id,
                    'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done']],
                    'orderby' => 'modified', 'order' => 'DESC'
                ]);
                if (!empty($done_tasks)): foreach($done_tasks as $task): ?>
                     <li>
                        <a href="#" class="open-task-modal" data-task-id="<?php echo esc_attr($task->ID); ?>"><?php echo esc_html($task->post_title); ?></a>
                        <span class="meta"><?php echo esc_html(human_time_diff(get_the_modified_time('U', $task->ID), current_time('timestamp'))) . ' پیش'; ?></span>
                    </li>
                <?php endforeach; else: ?><li>موردی یافت نشد.</li><?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div id="pzl-task-modal-backdrop" style="display: none;"></div>
<div id="pzl-task-modal-wrap" style="display: none;">
    <div id="pzl-task-modal-content">
        <button id="pzl-close-modal-btn">&times;</button>
        <div id="pzl-task-modal-body">
            <div class="pzl-loader"></div>
        </div>
    </div>
</div>