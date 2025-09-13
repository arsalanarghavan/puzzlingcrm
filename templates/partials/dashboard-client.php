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
    <a href="<?php echo esc_url(add_query_arg('tab', 'tickets', $base_url)); ?>" class="pzl-tab <?php echo $active_tab === 'tickets' ? 'active' : ''; ?>"><i class="fas fa-headset"></i> <?php esc_html_e('Support Tickets', 'puzzlingcrm'); ?></a>
</div>

<div class="pzl-dashboard-tab-content">
<?php
    switch ($active_tab) {
        case 'appointments':
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/page-client-appointments.php';
            break;
        case 'projects':
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/list-projects.php';
            break;
        case 'contracts':
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/page-client-contracts.php';
            break;
        case 'invoices':
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/list-client-payments.php';
            break;
        case 'pro_invoices':
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/page-client-pro-invoices.php';
            break;
        case 'tickets':
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/list-tickets.php';
            break;
        case 'overview':
        default:
            echo '<h3>' . sprintf(esc_html__('Welcome, %s!', 'puzzlingcrm'), wp_get_current_user()->display_name) . '</h3>';
            echo '<p>' . esc_html__('This is your dashboard. You can use the tabs above to navigate through different sections.', 'puzzlingcrm') . '</p>';
            break;
    }
?>
</div>