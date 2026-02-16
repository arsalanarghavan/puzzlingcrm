<?php
/**
 * Sidebar Menu Builder
 * 
 * Builds menu structure based on user role
 *
 * @package PuzzlingCRM
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sidebar Menu Builder class
 */
class PuzzlingCRM_Sidebar_Menu_Builder {

	/**
	 * Menu items cache
	 *
	 * @var array
	 */
	private static $menu_cache = array();

	/**
	 * Build menu for specific user role
	 *
	 * @param string $user_role User role.
	 * @return array Menu items array.
	 */
	public static function build_menu( $user_role = '' ) {
		if ( empty( $user_role ) ) {
			$user_role = self::get_current_user_role();
		}

		// Check cache
		$cache_key = 'menu_' . $user_role;
		if ( isset( self::$menu_cache[ $cache_key ] ) ) {
			return self::$menu_cache[ $cache_key ];
		}

		$menu_items = array();

		// Build menu based on role
		switch ( $user_role ) {
			case 'system_manager':
			case 'administrator':
				$menu_items = self::get_manager_menu();
				break;

			case 'sales_consultant':
				$menu_items = self::get_sales_consultant_menu();
				break;

			case 'finance_manager':
				$menu_items = self::get_finance_menu();
				break;

			case 'team_member':
				$menu_items = self::get_team_member_menu();
				break;

			case 'customer':
				$menu_items = self::get_customer_menu();
				break;

			default:
				$menu_items = array();
				break;
		}

		/**
		 * Filter sidebar menu items
		 *
		 * @param array  $menu_items Menu items array.
		 * @param string $user_role User role.
		 */
		$menu_items = apply_filters( 'puzzlingcrm_sidebar_menu_items', $menu_items, $user_role );

		// Cache the result
		self::$menu_cache[ $cache_key ] = $menu_items;

		return $menu_items;
	}

	/**
	 * Get manager menu items
	 *
	 * @return array Menu items with categories.
	 */
	private static function get_manager_menu() {
		$dashboard_url = home_url( '/dashboard' );

		return array(
			// Main Category
			array(
				'type'  => 'category',
				'title' => __( 'Main', 'puzzlingcrm' ),
			),
			array(
				'id'    => 'dashboard',
				'title' => __( 'Dashboard', 'puzzlingcrm' ),
				'url'   => $dashboard_url,
				'icon'  => 'ri-home-4-line',
			),
			// Projects Category
			array(
				'type'  => 'category',
				'title' => __( 'Projects', 'puzzlingcrm' ),
			),
			array(
				'id'       => 'projects',
				'title'    => __( 'All Projects', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/projects',
				'icon'     => 'ri-folder-2-line',
			),
			array(
				'id'       => 'contracts',
				'title'    => __( 'Contracts', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/contracts',
				'icon'     => 'ri-file-text-line',
			),
			// Services & Products Category
			array(
				'type'  => 'category',
				'title' => __( 'Services & Products', 'puzzlingcrm' ),
			),
			array(
				'id'       => 'services',
				'title'    => __( 'Subscriptions & Services', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/services',
				'icon'     => 'ri-service-line',
			),
			// Finance Category
			array(
				'type'  => 'category',
				'title' => __( 'Finance', 'puzzlingcrm' ),
			),
			array(
				'id'       => 'invoices',
				'title'    => __( 'Invoices', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/invoices',
				'icon'     => 'ri-file-list-3-line',
			),
			// Support Category
			array(
				'type'  => 'category',
				'title' => __( 'Support', 'puzzlingcrm' ),
			),
			array(
				'id'       => 'tickets',
				'title'    => __( 'Tickets', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/tickets',
				'icon'     => 'ri-customer-service-2-line',
			),
			// Tasks Category
			array(
				'type'  => 'category',
				'title' => __( 'Tasks', 'puzzlingcrm' ),
			),
			array(
				'id'       => 'tasks',
				'title'    => __( 'همه وظایف', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/tasks',
				'icon'     => 'ri-task-line',
			),
			array(
				'id'       => 'appointments',
				'title'    => __( 'Appointments', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/appointments',
				'icon'     => 'ri-calendar-check-line',
			),
			// People Category
			array(
				'type'  => 'category',
				'title' => __( 'People', 'puzzlingcrm' ),
			),
			array(
				'id'       => 'leads',
				'title'    => __( 'Leads', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/leads',
				'icon'     => 'ri-user-add-line',
			),
			array(
				'id'       => 'customers',
				'title'    => __( 'Customers', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/customers',
				'icon'     => 'ri-group-line',
			),
			array(
				'id'       => 'staff',
				'title'    => __( 'Staff', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/staff',
				'icon'     => 'ri-user-star-line',
			),
			array(
				'id'       => 'consultations',
				'title'    => __( 'Consultations', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/consultations',
				'icon'     => 'ri-message-2-line',
			),
			// System Category
			array(
				'type'  => 'category',
				'title' => __( 'System', 'puzzlingcrm' ),
			),
			array(
				'id'       => 'licenses',
				'title'    => __( 'Licenses', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/licenses',
				'icon'     => 'ri-key-2-line',
			),
			array(
				'id'       => 'logs',
				'title'    => __( 'Logs', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/logs',
				'icon'     => 'ri-file-list-2-line',
			),
			// Campaigns (Coming Soon)
			array(
				'type'  => 'category',
				'title' => __( 'Marketing', 'puzzlingcrm' ),
			),
			array(
				'id'       => 'campaigns',
				'title'    => __( 'Campaigns (Coming Soon)', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/campaigns',
				'icon'     => 'ri-megaphone-line',
			),
			// Reports Category
			array(
				'type'  => 'category',
				'title' => __( 'Reports', 'puzzlingcrm' ),
			),
			array(
				'id'       => 'reports',
				'title'    => __( 'All Reports', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/reports',
				'icon'     => 'ri-bar-chart-box-line',
			),
			// Settings Category
			array(
				'type'  => 'category',
				'title' => __( 'Settings', 'puzzlingcrm' ),
			),
			array(
				'id'       => 'settings',
				'title'    => __( 'General Settings', 'puzzlingcrm' ),
				'url'      => $dashboard_url . '/settings',
				'icon'     => 'ri-settings-3-line',
			),
		);
	}

	/**
	 * Get sales consultant menu items
	 *
	 * @return array Menu items.
	 */
	private static function get_sales_consultant_menu() {
		$dashboard_url = home_url( '/dashboard' );

		return array(
			array(
				'id'    => 'dashboard',
				'title' => __( 'Dashboard', 'puzzlingcrm' ),
				'url'   => $dashboard_url,
				'icon'  => 'ri-home-4-line',
			),
			array(
				'type'  => 'category',
				'title' => __( 'Leads', 'puzzlingcrm' ),
			),
			array(
				'id'    => 'leads',
				'title' => __( 'Leads', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/leads',
				'icon'  => 'ri-user-add-line',
			),
			array(
				'type'  => 'category',
				'title' => __( 'Sales', 'puzzlingcrm' ),
			),
			array(
				'id'    => 'contracts',
				'title' => __( 'Contracts', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/contracts',
				'icon'  => 'ri-file-text-line',
			),
			array(
				'id'    => 'customers',
				'title' => __( 'Customers', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/customers',
				'icon'  => 'ri-group-line',
			),
		);
	}

	/**
	 * Get finance manager menu items
	 *
	 * @return array Menu items.
	 */
	private static function get_finance_menu() {
		$dashboard_url = home_url( '/dashboard' );

		return array(
			array(
				'id'    => 'dashboard',
				'title' => __( 'Dashboard', 'puzzlingcrm' ),
				'url'   => $dashboard_url,
				'icon'  => 'ri-home-4-line',
			),
			array(
				'id'    => 'contracts',
				'title' => __( 'Contracts', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/contracts',
				'icon'  => 'ri-file-text-line',
			),
			array(
				'id'    => 'invoices',
				'title' => __( 'Invoices', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/invoices',
				'icon'  => 'ri-file-list-3-line',
			),
			array(
				'id'    => 'reports',
				'title' => __( 'Reports', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/reports',
				'icon'  => 'ri-bar-chart-box-line',
			),
		);
	}

	/**
	 * Get team member menu items
	 *
	 * @return array Menu items.
	 */
	private static function get_team_member_menu() {
		$dashboard_url = home_url( '/dashboard' );

		return array(
			array(
				'id'    => 'dashboard',
				'title' => __( 'Dashboard', 'puzzlingcrm' ),
				'url'   => $dashboard_url,
				'icon'  => 'ri-home-4-line',
			),
			array(
				'id'    => 'projects',
				'title' => __( 'My Projects', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/projects',
				'icon'  => 'ri-folder-2-line',
			),
			array(
				'id'    => 'tasks',
				'title' => __( 'My Tasks', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/tasks',
				'icon'  => 'ri-task-line',
			),
			array(
				'id'    => 'tickets',
				'title' => __( 'Tickets', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/tickets',
				'icon'  => 'ri-customer-service-2-line',
			),
		);
	}

	/**
	 * Get customer menu items
	 *
	 * @return array Menu items.
	 */
	private static function get_customer_menu() {
		$dashboard_url = home_url( '/dashboard' );

		return array(
			array(
				'id'    => 'dashboard',
				'title' => __( 'Dashboard', 'puzzlingcrm' ),
				'url'   => $dashboard_url,
				'icon'  => 'ri-home-4-line',
			),
			array(
				'id'    => 'projects',
				'title' => __( 'My Projects', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/projects',
				'icon'  => 'ri-folder-2-line',
			),
			array(
				'id'    => 'contracts',
				'title' => __( 'My Contracts', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/contracts',
				'icon'  => 'ri-file-text-line',
			),
			array(
				'id'    => 'invoices',
				'title' => __( 'My Invoices', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/invoices',
				'icon'  => 'ri-file-list-3-line',
			),
			array(
				'id'    => 'tickets',
				'title' => __( 'Support Tickets', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/tickets',
				'icon'  => 'ri-customer-service-2-line',
			),
		);
	}

	/**
	 * Get current user role
	 *
	 * @return string User role.
	 */
	private static function get_current_user_role() {
		if ( ! is_user_logged_in() ) {
			return 'guest';
		}

		$user  = wp_get_current_user();
		$roles = (array) $user->roles;

		if ( in_array( 'administrator', $roles, true ) || in_array( 'system_manager', $roles, true ) ) {
			return 'system_manager';
		}

		if ( in_array( 'finance_manager', $roles, true ) ) {
			return 'finance_manager';
		}

		if ( in_array( 'team_member', $roles, true ) ) {
			return 'team_member';
		}

		if ( in_array( 'sales_consultant', $roles, true ) ) {
			return 'sales_consultant';
		}

		if ( in_array( 'customer', $roles, true ) ) {
			return 'customer';
		}

		return 'guest';
	}

	/**
	 * Clear menu cache
	 */
	public static function clear_cache() {
		self::$menu_cache = array();
	}
}

