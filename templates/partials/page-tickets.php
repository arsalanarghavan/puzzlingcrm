<?php
/**
 * Tickets Management Page
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Include the tickets list based on user role
$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

if (in_array('administrator', $user_roles) || in_array('system_manager', $user_roles)) {
    // مدیران همه تیکت‌ها رو می‌بینن
    include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/list-tickets.php';
} else {
    // سایر کاربران فقط تیکت‌های خودشون رو می‌بینن
    include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/list-tickets.php';
}

