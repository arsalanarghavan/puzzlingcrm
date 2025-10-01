<?php
/**
 * Tickets Reports Template - V1
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

// --- Stats Calculation ---
$total_tickets = wp_count_posts('ticket')->publish;

// Ticket counts by status
$status_terms = get_terms(['taxonomy' => 'ticket_status', 'hide_empty' => false]);
$status_counts = [];
$open_tickets_count = 0;
foreach ($status_terms as $status) {
    $count = $status->count;
    $status_counts[$status->name] = $count;
    if ($status->slug !== 'closed') {
        $open_tickets_count += $count;
    }
}

// Ticket counts by priority
$priority_terms = get_terms(['taxonomy' => 'ticket_priority', 'hide_empty' => false]);
$priority_counts = [];
foreach ($priority_terms as $priority) {
    $priority_counts[$priority->name] = $priority->count;
}

// Ticket counts by department
$department_terms = get_terms(['taxonomy' => 'organizational_position', 'hide_empty' => false, 'parent' => 0]);
$department_counts = [];
foreach ($department_terms as $department) {
    $department_counts[$department->name] = $department->count;
}

?>

<div class="finance-report-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
    <div class="report-card">
        <h4><i class="fas fa-ticket-alt"></i> کل تیکت‌ها</h4>
        <span class="stat-number"><?php echo esc_html($total_tickets); ?></span>
    </div>
    <div class="report-card">
        <h4><i class="fas fa-envelope-open"></i> تیکت‌های باز</h4>
        <span class="stat-number" style="color: var(--pzl-warning-color);"><?php echo esc_html($open_tickets_count); ?></span>
    </div>
    <div class="report-card">
        <h4><i class="fas fa-envelope"></i> تیکت‌های بسته</h4>
        <span class="stat-number" style="color: var(--pzl-success-color);"><?php echo esc_html($status_counts['Closed'] ?? 0); ?></span>
    </div>
</div>

<div class="pzl-reports-grid">
    <div class="pzl-card">
        <h4><i class="fas fa-chart-pie"></i> تیکت‌ها بر اساس وضعیت</h4>
        <div class="pzl-chart-container pzl-pie-chart-container">
            <?php if (!empty($status_counts) && $total_tickets > 0): ?>
                <div class="pzl-chart-legend">
                <?php
                $hue_step = 360 / count($status_counts);
                $current_hue = 0;
                foreach($status_counts as $status => $count):
                    if ($count == 0) continue;
                    $percentage = round(($count / $total_tickets) * 100);
                ?>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: hsl(<?php echo $current_hue; ?>, 70%, 50%);"></span>
                        <?php echo esc_html($status) . ' (' . esc_html($count) . ' - ' . esc_html($percentage) . '%)'; ?>
                    </div>
                <?php $current_hue += $hue_step; endforeach; ?>
                </div>
            <?php else: ?>
                <p>داده‌ای برای نمایش نمودار وضعیت وجود ندارد.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="pzl-card">
        <h4><i class="fas fa-chart-bar"></i> تیکت‌ها بر اساس اولویت</h4>
        <div class="pzl-chart-container" style="height: 300px;">
            <?php if (!empty($priority_counts)):
                $max_count = max($priority_counts) > 0 ? max($priority_counts) : 1;
            ?>
                <?php foreach($priority_counts as $priority => $count): ?>
                    <div class="chart-bar-wrapper">
                        <div class="chart-bar" style="height: <?php echo esc_attr(max(1, ($count / $max_count) * 250)); ?>px;" title="<?php echo esc_attr($priority) . ': ' . esc_attr($count); ?>"></div>
                        <div class="chart-label"><?php echo esc_html($priority); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>داده‌ای برای نمایش نمودار اولویت وجود ندارد.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="pzl-card">
    <h4><i class="fas fa-building"></i> تعداد تیکت‌ها در هر دپارتمان</h4>
    <table class="pzl-table">
        <thead>
            <tr><th>نام دپارتمان</th><th>تعداد تیکت‌ها</th></tr>
        </thead>
        <tbody>
        <?php if(!empty($department_counts)): ?>
            <?php foreach($department_counts as $name => $count): ?>
            <tr>
                <td><?php echo esc_html($name); ?></td>
                <td><?php echo esc_html($count); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2">داده‌ای برای نمایش وجود ندارد.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>