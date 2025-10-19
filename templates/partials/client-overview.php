<?php
/**
 * Client Dashboard Overview Template
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_user_id = get_current_user_id();

// Fetch customer-specific stats
$total_projects = count_user_posts($current_user_id, 'project');
$open_tickets = count(get_posts([
    'post_type' => 'ticket',
    'author' => $current_user_id,
    'posts_per_page' => -1,
    'tax_query' => [['taxonomy' => 'ticket_status', 'field' => 'slug', 'terms' => 'closed', 'operator' => 'NOT IN']]
]));

$contracts = get_posts(['post_type' => 'contract', 'author' => $current_user_id, 'posts_per_page' => -1]);
$pending_installments = 0;
if ($contracts) {
    foreach ($contracts as $contract) {
        $installments = get_post_meta($contract->ID, '_installments', true);
        if (is_array($installments)) {
            foreach ($installments as $inst) {
                if (($inst['status'] ?? 'pending') === 'pending') {
                    $pending_installments++;
                }
            }
        }
    }
}
?>
<div class="pzl-dashboard-stats-grid">
    <div class="stat-widget-card gradient-1">
        <div class="stat-widget-icon"><i class="ri-folder-2-line"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($total_projects); ?></span>
            <span class="stat-title">پروژه‌های شما</span>
        </div>
    </div>
    <div class="stat-widget-card gradient-3">
        <div class="stat-widget-icon"><i class="ri-customer-service-2-line"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($open_tickets); ?></span>
            <span class="stat-title">تیکت‌های باز</span>
        </div>
    </div>
     <div class="stat-widget-card gradient-2">
        <div class="stat-widget-icon"><i class="ri-file-line-invoice-dollar"></i></div>
        <div class="stat-widget-content">
            <span class="stat-number"><?php echo esc_html($pending_installments); ?></span>
            <span class="stat-title">اقساط باقی‌مانده</span>
        </div>
    </div>
</div>

<div class="pzl-card">
    <div class="pzl-card-header">
        <h3><i class="ri-history-line"></i> آخرین فعالیت‌های شما</h3>
    </div>
    <p>در این بخش می‌توانید خلاصه‌ای از آخرین پروژه‌ها، تیکت‌ها و قراردادهای خود را مشاهده کنید.</p>
</div>