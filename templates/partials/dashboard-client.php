<?php
/**
 * Client Dashboard Template (Redesigned with Tabs - Final Version)
 *
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
$base_url = puzzling_get_dashboard_url();
?>
<div class="pzl-dashboard-tabs">
    <a href="<?php echo esc_url(remove_query_arg('tab', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> <?php esc_html_e('Overview', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('tab', 'appointments', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'appointments' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> <?php esc_html_e('Schedule Appointment', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('tab', 'projects', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'projects' ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i> <?php esc_html_e('Projects', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('tab', 'contracts', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'contracts' ? 'active' : ''; ?>"><i class="fas fa-file-signature"></i> <?php esc_html_e('Contracts', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('tab', 'invoices', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'invoices' ? 'active' : ''; ?>"><i class="fas fa-file-invoice"></i> <?php esc_html_e('Invoices', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('tab', 'pro_invoices', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'pro_invoices' ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> <?php esc_html_e('Pro-forma Invoices', 'puzzlingcrm'); ?></a>
    <a href="<?php echo esc_url(add_query_arg('tab', 'tickets', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'tickets' ? 'active' : ''; ?>"><i class="fas fa-life-ring"></i> <?php esc_html_e('Support Tickets', 'puzzlingcrm'); ?></a>
</div>

<div class="pzl-dashboard-tab-content">
<?php
    // FIX: Switched to a more robust way of handling views to prevent logic errors.
    $view_map = [
        'appointments' => 'page-client-appointments.php',
        'projects'     => 'list-projects.php',
        'contracts'    => 'page-client-contracts.php',
        'invoices'     => 'list-client-payments.php', // Corrected this line
        'pro_invoices' => 'page-client-pro-invoices.php',
        'tickets'      => 'list-tickets.php',
        'overview'     => 'client-overview.php', // A placeholder for the default view
    ];

    $template_file = $view_map[$active_tab] ?? 'client-overview.php';
    $template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . $template_file;

    if (file_exists($template_path) && $active_tab !== 'overview') {
        include $template_path;
    } else {
        // Default overview content
        echo '<h3>' . sprintf(esc_html__('Welcome, %s!', 'puzzlingcrm'), wp_get_current_user()->display_name) . '</h3>';
        echo '<p>' . esc_html__('This is your dashboard. You can use the tabs above to navigate through different sections.', 'puzzlingcrm') . '</p>';
    }
?>
</div>