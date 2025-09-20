<?php
/**
 * Client Dashboard Template (Redesigned without Tabs)
 *
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$current_view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'overview';
$project_id_to_view = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

$base_url = get_permalink();

// Special handling for single project view
if ($current_view === 'projects' && $project_id_to_view > 0) {
    global $puzzling_project;
    $puzzling_project = get_post($project_id_to_view);

    // Security check: Make sure the current user is the author of the project.
    if ($puzzling_project && $puzzling_project->post_author == get_current_user_id()) {
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/single-project-client.php';
        return; // Stop further execution
    } else {
        // If not allowed, just fall back to the main projects list
        $current_view = 'projects'; 
    }
}

// A map of views to their corresponding template files and titles
$dashboard_pages = [
    'overview'      => ['file' => 'client-overview.php', 'title' => 'نمای کلی', 'icon' => 'fa-tachometer-alt'],
    'projects'      => ['file' => 'list-projects.php', 'title' => 'پروژه‌ها', 'icon' => 'fa-briefcase'],
    'contracts'     => ['file' => 'page-client-contracts.php', 'title' => 'قراردادها', 'icon' => 'fa-file-signature'],
    'invoices'      => ['file' => 'list-client-payments.php', 'title' => 'فاکتورها و پرداخت‌ها', 'icon' => 'fa-file-invoice-dollar'],
    'pro_invoices'  => ['file' => 'page-client-pro-invoices.php', 'title' => 'پیش‌فاکتورها', 'icon' => 'fa-file-invoice'],
    'appointments'  => ['file' => 'page-client-appointments.php', 'title' => 'قرار ملاقات', 'icon' => 'fa-calendar-check'],
    'tickets'       => ['file' => 'list-tickets.php', 'title' => 'تیکت‌های پشتیبانی', 'icon' => 'fa-life-ring'],
];

$template_to_load = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . ($dashboard_pages[$current_view]['file'] ?? 'client-overview.php');

?>
<div class="pzl-dashboard-content-wrapper">
    <?php if ($current_view !== 'overview'): ?>
        <a href="<?php echo esc_url($base_url); ?>" class="pzl-button back-to-dashboard-btn">&larr; بازگشت به داشبورد</a>
    <?php endif; ?>

    <?php
    // If a specific view is requested, load its template
    if (isset($dashboard_pages[$current_view]) && file_exists($template_to_load)) {
        include $template_to_load;
    } else {
        // Otherwise, show the main dashboard grid
    ?>
        <h3><i class="fas fa-th-large"></i> داشبورد شما</h3>
        <p>از طریق بخش‌های زیر می‌توانید به امکانات مختلف پنل خود دسترسی داشته باشید.</p>
        <div class="pzl-dashboard-grid-nav">
            <?php foreach ($dashboard_pages as $slug => $page):
                if ($slug === 'overview') continue; // Don't show overview as a card
                $page_url = add_query_arg('view', $slug, $base_url);
            ?>
                <a href="<?php echo esc_url($page_url); ?>" class="pzl-dashboard-nav-card">
                    <i class="fas <?php echo esc_attr($page['icon']); ?>"></i>
                    <span><?php echo esc_html($page['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <hr style="margin: 30px 0;">
        <?php
        // Also include the overview stats on the main dashboard page
        include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/client-overview.php';
    }
    ?>
</div>

<style>
/* Add these styles to your main CSS file (puzzlingcrm-styles.css) */
.pzl-dashboard-grid-nav {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 25px;
}
.pzl-dashboard-nav-card {
    background-color: var(--pzl-card-bg);
    border: 1px solid var(--pzl-border-color);
    border-radius: var(--pzl-border-radius);
    padding: 25px;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: var(--pzl-box-shadow);
}
.pzl-dashboard-nav-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: var(--pzl-primary-color);
}
.pzl-dashboard-nav-card i {
    font-size: 36px;
    color: var(--pzl-primary-color);
    margin-bottom: 15px;
    display: block;
}
.pzl-dashboard-nav-card span {
    font-size: 16px;
    font-weight: 600;
    color: var(--pzl-secondary-color);
}
.back-to-dashboard-btn {
    margin-bottom: 25px;
}
</style>