<?php
/**
 * System Manager Dashboard Template - COMPLETELY REDESIGNED & FUNCTIONAL
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- Fetch Dashboard Stats (Cached for 1 hour) ---
if ( false === ( $stats = get_transient( 'puzzling_system_manager_stats_v2' ) ) ) {
    
    // Project & Task Stats
    $total_projects = wp_count_posts('project')->publish;
    $active_tasks_count = count(get_posts(['post_type' => 'task', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done', 'operator' => 'NOT IN']]]));

    // Customer Stats
    $customer_count = count_users()['avail_roles']['customer'] ?? 0;
    
    // Ticket Stats
    $open_tickets = count(get_posts(['post_type' => 'ticket', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'ticket_status', 'field' => 'slug', 'terms' => 'closed', 'operator' => 'NOT IN']]]));

    // Subscription Stats
    $active_subscriptions = 0;
    if ( function_exists('wcs_get_subscription_count') ) {
        $active_subscriptions = wcs_get_subscription_count( 'active' );
    }

    // Financial Stats (Income This Month)
    $income_this_month = 0;
    $current_month_start = date('Y-m-01');
    $current_month_end = date('Y-m-t');
    $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);
    foreach ($contracts as $contract) {
        $installments = get_post_meta($contract->ID, '_installments', true);
        if (is_array($installments)) {
            foreach ($installments as $inst) {
                if (($inst['status'] ?? 'pending') === 'paid' && isset($inst['due_date'])) {
                    $due_date = strtotime($inst['due_date']);
                    if ($due_date >= strtotime($current_month_start) && $due_date <= strtotime($current_month_end)) {
                        $income_this_month += (int)($inst['amount'] ?? 0);
                    }
                }
            }
        }
    }


    $stats = [
        'total_projects' => $total_projects,
        'active_tasks_count' => $active_tasks_count,
        'customer_count' => $customer_count,
        'open_tickets' => $open_tickets,
        'active_subscriptions' => $active_subscriptions,
        'income_this_month' => $income_this_month,
    ];
    set_transient( 'puzzling_system_manager_stats_v2', $stats, HOUR_IN_SECONDS );
}

// --- Fetch Data for Chart ---
$task_status_terms = get_terms(['taxonomy' => 'task_status', 'hide_empty' => false, 'orderby' => 'term_order']);
$task_status_counts = [];
foreach ($task_status_terms as $status) {
    $task_status_counts[$status->name] = $status->count;
}

// --- Fetch Recent Activities ---
$recent_projects = get_posts(['post_type' => 'project', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);
$recent_tasks = get_posts(['post_type' => 'task', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);
$recent_tickets = get_posts(['post_type' => 'ticket', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);

?>
<div class="pzl-dashboard-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
    <div class="stat-widget-card gradient-1">
        <div class="stat-widget-icon"><i class="fas fa-briefcase"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['total_projects']); ?></span>
            <span class="stat-title">پروژه کل</span>
        </div>
    </div>
    <div class="stat-widget-card gradient-2">
        <div class="stat-widget-icon"><i class="fas fa-tasks"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['active_tasks_count']); ?></span>
            <span class="stat-title">وظایف فعال</span>
        </div>
    </div>
     <div class="stat-widget-card gradient-3">
        <div class="stat-widget-icon"><i class="fas fa-user-tie"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['customer_count']); ?></span>
            <span class="stat-title">تعداد مشتریان</span>
        </div>
    </div>
    <div class="stat-widget-card gradient-4">
        <div class="stat-widget-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html(number_format($stats['income_this_month'])); ?></span>
            <span class="stat-title">درآمد ماه جاری (تومان)</span>
        </div>
    </div>
    <div class="stat-widget-card" style="background: linear-gradient(45deg, #6c757d, #343a40);">
        <div class="stat-widget-icon"><i class="fas fa-life-ring"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['open_tickets']); ?></span>
            <span class="stat-title">تیکت‌های باز</span>
        </div>
    </div>
    <div class="stat-widget-card" style="background: linear-gradient(45deg, #17a2b8, #007bff);">
        <div class="stat-widget-icon"><i class="fas fa-sync-alt"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['active_subscriptions']); ?></span>
            <span class="stat-title">اشتراک‌های فعال</span>
        </div>
    </div>
</div>

<div class="pzl-dashboard-grid pzl-dashboard-grid-2-col">
    <div class="pzl-card">
        <div class="pzl-card-header">
            <h3><i class="fas fa-chart-bar"></i> توزیع وضعیت وظایف</h3>
        </div>
        <div class="pzl-chart-container" style="height: 350px;">
             <?php if (!empty($task_status_counts)): 
                $max_count = max($task_status_counts) > 0 ? max($task_status_counts) : 1;
                $colors = ['#dc3545', '#ffc107', '#28a745', '#6c757d', '#17a2b8'];
                $color_index = 0;
            ?>
                <?php foreach($task_status_counts as $status => $count): ?>
                    <div class="chart-bar-wrapper">
                        <div class="chart-bar" style="height: <?php echo esc_attr(max(1, ($count / $max_count) * 280)); ?>px; background-color: <?php echo $colors[$color_index % count($colors)]; ?>;" title="<?php echo esc_attr($status) . ': ' . esc_attr(number_format($count)); ?> وظیفه"></div>
                        <div class="chart-label"><?php echo esc_html($status); ?></div>
                    </div>
                <?php $color_index++; endforeach; ?>
            <?php else: ?>
                <p>داده‌ای برای نمایش نمودار وظایف وجود ندارد.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="pzl-card">
        <div class="pzl-card-header">
            <h3><i class="fas fa-history"></i> آخرین فعالیت‌ها</h3>
        </div>
        <div class="pzl-activity-feed">
            <h5><i class="fas fa-briefcase"></i> آخرین پروژه‌ها</h5>
            <ul class="pzl-activity-list">
                <?php if (!empty($recent_projects)): foreach($recent_projects as $p): 
                    $project_edit_url = add_query_arg(['view' => 'projects', 'action' => 'edit', 'project_id' => $p->ID]);
                ?>
                    <li>
                        <a href="<?php echo esc_url($project_edit_url); ?>"><?php echo esc_html($p->post_title); ?></a>
                        <span class="meta"><?php echo get_the_author_meta('display_name', $p->post_author); ?> - <?php echo date_i18n('Y/m/d', strtotime($p->post_date)); ?></span>
                    </li>
                <?php endforeach; else: ?>
                    <li>موردی یافت نشد.</li>
                <?php endif; ?>
            </ul>
             <h5><i class="fas fa-tasks"></i> آخرین وظایف</h5>
            <ul class="pzl-activity-list">
                <?php if (!empty($recent_tasks)): foreach($recent_tasks as $t): ?>
                    <li>
                        <span><?php echo esc_html($t->post_title); ?></span>
                        <span class="meta"><?php echo get_the_author_meta('display_name', get_post_meta($t->ID, '_assigned_to', true)); ?> - <?php echo date_i18n('Y/m/d', strtotime($t->post_date)); ?></span>
                    </li>
                <?php endforeach; else: ?>
                    <li>موردی یافت نشد.</li>
                <?php endif; ?>
            </ul>
             <h5><i class="fas fa-life-ring"></i> آخرین تیکت‌ها</h5>
            <ul class="pzl-activity-list">
                <?php if (!empty($recent_tickets)): foreach($recent_tickets as $ticket): 
                    $status_terms = get_the_terms($ticket->ID, 'ticket_status');
                    $status_name = !empty($status_terms) ? esc_html($status_terms[0]->name) : 'نامشخص';
                ?>
                    <li>
                        <span><?php echo esc_html($ticket->post_title); ?></span>
                        <span class="meta"><?php echo get_the_author_meta('display_name', $ticket->post_author); ?> - <span class="pzl-status-badge status-<?php echo esc_attr($status_terms[0]->slug); ?>"><?php echo $status_name; ?></span></span>
                    </li>
                <?php endforeach; else: ?>
                    <li>موردی یافت نشد.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>