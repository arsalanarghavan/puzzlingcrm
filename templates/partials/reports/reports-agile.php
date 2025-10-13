<?php
/**
 * Agile Reports Template - Burndown Chart
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

// For this example, we'll fetch the most recent sprint
$latest_sprint = get_posts([
    'post_type' => 'pzl_sprint',
    'posts_per_page' => 1,
    'orderby' => 'post_date',
    'order' => 'DESC'
]);

$sprint_data = null;
if ($latest_sprint) {
    $sprint_id = $latest_sprint[0]->ID;
    $sprint_name = $latest_sprint[0]->post_title;
    $start_date_str = get_post_meta($sprint_id, '_sprint_start_date', true);
    $end_date_str = get_post_meta($sprint_id, '_sprint_end_date', true);

    if ($start_date_str && $end_date_str) {
        $start_date = new DateTime($start_date_str);
        $end_date = new DateTime($end_date_str);
        $sprint_duration = $end_date->diff($start_date)->days;
        
        $sprint_tasks = get_posts([
            'post_type' => 'task',
            'posts_per_page' => -1,
            'meta_key' => '_sprint_id',
            'meta_value' => $sprint_id
        ]);
        
        $total_story_points = 0;
        foreach ($sprint_tasks as $task) {
            $total_story_points += (int)get_post_meta($task->ID, '_story_points', true);
        }
        
        // This is a simplified calculation for demonstration. A real implementation would track completion date.
        $completed_tasks = get_posts(array_merge(['post__in' => wp_list_pluck($sprint_tasks, 'ID')], ['tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done']]]));
        $completed_story_points = 0;
         foreach ($completed_tasks as $task) {
            $completed_story_points += (int)get_post_meta($task->ID, '_story_points', true);
        }
        
        $sprint_data = [
            'name' => $sprint_name,
            'duration' => $sprint_duration,
            'total_points' => $total_story_points,
            'remaining_points' => $total_story_points - $completed_story_points,
            'days_passed' => (new DateTime())->diff($start_date)->days
        ];
    }
}
?>

<div class="pzl-card">
    <h4><i class="fas fa-chart-line"></i> نمودار پیشرفت (Burndown Chart) - آخرین اسپرینت</h4>

    <?php if ($sprint_data && $sprint_data['total_points'] > 0): 
        $ideal_rate = $sprint_data['total_points'] / $sprint_data['duration'];
        $ideal_remaining = max(0, $sprint_data['total_points'] - ($ideal_rate * $sprint_data['days_passed']));
        $actual_height = ($sprint_data['remaining_points'] / $sprint_data['total_points']) * 100;
        $ideal_height = ($ideal_remaining / $sprint_data['total_points']) * 100;
    ?>
    <div class="burndown-chart-container">
        <div class="chart-area">
            <svg width="100%" height="300" viewBox="0 0 800 300" preserveAspectRatio="none">
                <line x1="50" y1="250" x2="750" y2="250" stroke="#ccc" /> <line x1="50" y1="50" x2="50" y2="250" stroke="#ccc" />  <text x="10" y="50" fill="#666"><?php echo esc_html($sprint_data['total_points']); ?></text>
                <text x="10" y="250" fill="#666">0</text>

                <line x1="50" y1="50" x2="750" y2="250" stroke="#4caf50" stroke-dasharray="5,5" stroke-width="2"/>

                <polyline points="50,50 <?php echo 50 + (($sprint_data['days_passed'] / $sprint_data['duration']) * 700); ?>,<?php echo 50 + ((100 - $actual_height) / 100) * 200; ?>" fill="none" stroke="#f44336" stroke-width="3"/>
            </svg>
        </div>
        <div class="chart-legend">
            <div><span style="background-color: #f44336;"></span> <?php esc_html_e('کار باقی‌مانده واقعی', 'puzzlingcrm'); ?></div>
            <div><span style="background-color: #4caf50;"></span> <?php esc_html_e('خط روند ایده‌آل', 'puzzlingcrm'); ?></div>
        </div>
    </div>
    <?php else: ?>
        <p><?php esc_html_e('داده‌ای برای نمایش نمودار یافت نشد. لطفاً مطمئن شوید که یک اسپرینت فعال با وظایف دارای امتیاز داستان (Story Point) وجود دارد.', 'puzzlingcrm'); ?></p>
    <?php endif; ?>
</div>

<style>
.burndown-chart-container { padding: 20px; }
.chart-legend { display: flex; justify-content: center; gap: 20px; margin-top: 15px; font-size: 14px; }
.chart-legend span { display: inline-block; width: 15px; height: 15px; margin-left: 5px; vertical-align: middle; }
</style>