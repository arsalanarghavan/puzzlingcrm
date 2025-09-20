<?php
/**
 * System Manager Dashboard Template - COMPLETELY REDESIGNED & FUNCTIONAL
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- Fetch Dashboard Stats (Cached) ---
if ( false === ( $stats = get_transient( 'puzzling_system_manager_stats' ) ) ) {
    $total_projects = wp_count_posts('project')->publish;
    $active_tasks_count = count(get_posts(['post_type' => 'task', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'task_status', 'field' => 'slug', 'terms' => 'done', 'operator' => 'NOT IN']]]));
    
    $active_subscriptions = 0;
    if ( function_exists('wcs_get_subscription_count') ) {
        $active_subscriptions = wcs_get_subscription_count( 'active' );
    }
    $open_tickets = count(get_posts(['post_type' => 'ticket', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'ticket_status', 'field' => 'slug', 'terms' => 'closed', 'operator' => 'NOT IN']]]));

    $stats = [
        'total_projects' => $total_projects,
        'active_tasks_count' => $active_tasks_count,
        'open_tickets' => $open_tickets,
        'active_subscriptions' => $active_subscriptions,
    ];
    set_transient( 'puzzling_system_manager_stats', $stats, HOUR_IN_SECONDS );
}

// --- Fetch Recent Activities ---
$recent_projects = get_posts(['post_type' => 'project', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);
$recent_tasks = get_posts(['post_type' => 'task', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);
$recent_tickets = get_posts(['post_type' => 'ticket', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);

?>
<div class="pzl-dashboard-stats-grid">
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
        <div class="stat-widget-icon"><i class="fas fa-life-ring"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['open_tickets']); ?></span>
            <span class="stat-title">تیکت‌های باز</span>
        </div>
    </div>
    <div class="stat-widget-card gradient-4">
        <div class="stat-widget-icon"><i class="fas fa-sync-alt"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($stats['active_subscriptions']); ?></span>
            <span class="stat-title">اشتراک‌های فعال</span>
        </div>
    </div>
</div>

<div class="pzl-dashboard-grid">
    <div class="pzl-card">
        <div class="pzl-card-header">
            <h3><i class="fas fa-briefcase"></i> آخرین پروژه‌ها</h3>
        </div>
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
    </div>

    <div class="pzl-card">
        <div class="pzl-card-header">
            <h3><i class="fas fa-tasks"></i> آخرین وظایف</h3>
        </div>
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
    </div>
    
    <div class="pzl-card">
        <div class="pzl-card-header">
            <h3><i class="fas fa-life-ring"></i> آخرین تیکت‌ها</h3>
        </div>
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