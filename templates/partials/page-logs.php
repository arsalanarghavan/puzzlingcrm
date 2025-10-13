<?php
/**
 * Template wrapper for displaying system logs for managers. - V2 with Tabs
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;

$active_tab = isset( $_GET[ 'log_tab' ] ) ? sanitize_key( $_GET[ 'log_tab' ] ) : 'events';
$base_url = remove_query_arg(['puzzling_notice', 'log_tab']);
?>
<div class="pzl-dashboard-section">
    <h3><i class="fas fa-history"></i> لاگ‌های سیستم</h3>

    <div class="pzl-dashboard-tabs">
        <a href="<?php echo add_query_arg('log_tab', 'events', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'events' ? 'active' : ''; ?>"><i class="fas fa-user-clock"></i> لاگ رویدادها</a>
        <a href="<?php echo add_query_arg('log_tab', 'system', $base_url); ?>" class="pzl-tab <?php echo $active_tab == 'system' ? 'active' : ''; ?>"><i class="fas fa-cogs"></i> لاگ سیستم</a>
    </div>

    <div class="pzl-dashboard-tab-content">
        <?php
        if ( $active_tab == 'system' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/view-system-logs.php';
        } else {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/view-logs.php';
        }
        ?>
    </div>
</div>