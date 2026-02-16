<?php
/**
 * Template wrapper for displaying system and user logs for managers.
 * Tabs: User logs (DB), System logs (DB).
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$active_tab = isset( $_GET['log_tab'] ) ? sanitize_key( $_GET['log_tab'] ) : 'user';
$base_url   = remove_query_arg( array( 'puzzling_notice', 'log_tab' ) );
?>
<div class="pzl-dashboard-section">
    <h3><i class="ri-history-line"></i> لاگ‌های سیستم</h3>

    <div class="pzl-dashboard-tabs">
        <a href="<?php echo esc_url( add_query_arg( 'log_tab', 'user', $base_url ) ); ?>" class="pzl-tab <?php echo $active_tab === 'user' ? 'active' : ''; ?>"><i class="ri-user-line-clock"></i> لاگ کاربر</a>
        <a href="<?php echo esc_url( add_query_arg( 'log_tab', 'system', $base_url ) ); ?>" class="pzl-tab <?php echo $active_tab === 'system' ? 'active' : ''; ?>"><i class="ri-settings-3-lines"></i> لاگ سیستم</a>
    </div>

    <div class="pzl-dashboard-tab-content">
        <?php
        if ( $active_tab === 'system' ) {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/view-system-logs.php';
        } else {
            include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/view-user-logs.php';
        }
        ?>
    </div>
</div>
