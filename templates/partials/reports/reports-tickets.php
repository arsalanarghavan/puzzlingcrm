<?php
/**
 * Tickets Reports Template - V2 (SLA & CSAT Metrics)
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
    if (!in_array($status->slug, ['closed', 'resolved'])) { // Using 'resolved' as another possible closed slug
        $open_tickets_count += $count;
    }
}

// --- SLA & CSAT Calculation ---
$total_first_response_time = 0;
$total_resolution_time = 0;
$responded_tickets_count = 0;
$resolved_tickets_count = 0;
$total_rating = 0;
$rated_tickets_count = 0;

$all_tickets_query = new WP_Query(['post_type' => 'ticket', 'posts_per_page' => -1, 'post_status' => 'publish']);
$all_tickets = $all_tickets_query->get_posts();

foreach ($all_tickets as $ticket) {
    $creation_time = get_post_meta($ticket->ID, '_creation_timestamp', true);
    $first_response_time = get_post_meta($ticket->ID, '_first_response_timestamp', true);
    $resolution_time = get_post_meta($ticket->ID, '_resolution_timestamp', true);
    $rating = get_post_meta($ticket->ID, '_ticket_rating', true);

    if ($creation_time && $first_response_time) {
        $total_first_response_time += ($first_response_time - $creation_time);
        $responded_tickets_count++;
    }

    if ($creation_time && $resolution_time) {
        $total_resolution_time += ($resolution_time - $creation_time);
        $resolved_tickets_count++;
    }
    
    if ($rating) {
        $total_rating += (int)$rating;
        $rated_tickets_count++;
    }
}

$avg_first_response_hours = $responded_tickets_count > 0 ? round(($total_first_response_time / $responded_tickets_count) / 3600, 2) : 0;
$avg_resolution_hours = $resolved_tickets_count > 0 ? round(($total_resolution_time / $resolved_tickets_count) / 3600, 2) : 0;
$avg_csat = $rated_tickets_count > 0 ? round(($total_rating / $rated_tickets_count), 2) : 'N/A';

// Ticket counts by priority
$priority_terms = get_terms(['taxonomy' => 'ticket_priority', 'hide_empty' => false]);
$priority_counts = [];
foreach ($priority_terms as $priority) {
    $priority_counts[$priority->name] = $priority->count;
}

// Ticket counts by department
$department_terms = get_terms(['taxonomy' => 'organizational_position', 'hide_empty' => false, 'parent' => 0]);
$department_counts = [];
if (!is_wp_error($department_terms)) {
    foreach ($department_terms as $department) {
        $args = [
            'post_type' => 'ticket',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'organizational_position',
                    'field'    => 'term_id',
                    'terms'    => $department->term_id,
                ],
            ],
        ];
        $department_tickets = new WP_Query($args);
        $department_counts[$department->name] = $department_tickets->post_count;
    }
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
        <h4><i class="fas fa-hourglass-half"></i> میانگین اولین پاسخ</h4>
        <span class="stat-number"><?php echo esc_html($avg_first_response_hours); ?></span>
        <span class="stat-label">ساعت</span>
    </div>
    <div class="report-card">
        <h4><i class="fas fa-check-double"></i> میانگین زمان حل</h4>
        <span class="stat-number"><?php echo esc_html($avg_resolution_hours); ?></span>
        <span class="stat-label">ساعت</span>
    </div>
    <div class="report-card">
        <h4><i class="fas fa-star"></i> رضایت مشتری (از ۵)</h4>
        <span class="stat-number" style="color: var(--pzl-info-color);"><?php echo esc_html($avg_csat); ?></span>
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