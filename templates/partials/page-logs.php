<?php
/**
 * Template wrapper for displaying system logs for managers.
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) return;
?>
<div class="pzl-dashboard-section">
    <?php include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/view-logs.php'; ?>
</div>