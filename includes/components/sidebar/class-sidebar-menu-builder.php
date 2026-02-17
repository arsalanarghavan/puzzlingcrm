<?php
/**
 * Sidebar Menu Builder
 *
 * Builds menu structure based on user role.
 * Menu is organized in four modules: Dashboard, Projects, CRM, Accounting.
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
	 * Check if a module is enabled (for future use; all enabled by default).
	 *
	 * @param string $module_key Module key: dashboard, projects, crm, accounting.
	 * @return bool
	 */
	public static function is_module_enabled( $module_key ) {
		$settings = class_exists( 'PuzzlingCRM_Settings_Handler' ) ? PuzzlingCRM_Settings_Handler::get_all_settings() : array();
		$key      = 'module_' . $module_key . '_enabled';
		return isset( $settings[ $key ] ) ? ( (string) $settings[ $key ] === '1' ) : true;
	}

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

		$cache_key = 'menu_' . $user_role;
		if ( isset( self::$menu_cache[ $cache_key ] ) ) {
			return self::$menu_cache[ $cache_key ];
		}

		$menu_items = array();

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

		$menu_items = apply_filters( 'puzzlingcrm_sidebar_menu_items', $menu_items, $user_role );
		self::$menu_cache[ $cache_key ] = $menu_items;

		return $menu_items;
	}

	/**
	 * Get manager menu items (four modules: Dashboard, Projects, CRM, Accounting)
	 *
	 * @return array Menu items with categories.
	 */
	private static function get_manager_menu() {
		$dashboard_url = home_url( '/dashboard' );
		$items          = array();

		// Module 1: داشبورد پیشفرض
		if ( self::is_module_enabled( 'dashboard' ) ) {
			$items[] = array( 'type' => 'category', 'title' => __( 'داشبورد پیشفرض', 'puzzlingcrm' ) );
			$items[] = array( 'id' => 'dashboard', 'title' => __( 'داشبورد', 'puzzlingcrm' ), 'url' => $dashboard_url, 'icon' => 'ri-home-4-line' );
			$items[] = array( 'id' => 'reports', 'title' => __( 'گزارشات کلی', 'puzzlingcrm' ), 'url' => $dashboard_url . '/reports', 'icon' => 'ri-bar-chart-box-line' );
		}

		// Module 2: مدیریت پروژه
		if ( self::is_module_enabled( 'projects' ) ) {
			$items[] = array( 'type' => 'category', 'title' => __( 'مدیریت پروژه', 'puzzlingcrm' ) );
			$items[] = array( 'id' => 'projects', 'title' => __( 'پروژه‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/projects', 'icon' => 'ri-folder-2-line' );
			$items[] = array( 'id' => 'contracts', 'title' => __( 'قراردادها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/contracts', 'icon' => 'ri-file-text-line' );
			$items[] = array( 'id' => 'tasks', 'title' => __( 'وظایف', 'puzzlingcrm' ), 'url' => $dashboard_url . '/tasks', 'icon' => 'ri-task-line' );
			$items[] = array( 'id' => 'appointments', 'title' => __( 'قرار ملاقات‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/appointments', 'icon' => 'ri-calendar-check-line' );
		}

		// Module 3: ارتباط با مشتری (CRM)
		if ( self::is_module_enabled( 'crm' ) ) {
			$items[] = array( 'type' => 'category', 'title' => __( 'ارتباط با مشتری', 'puzzlingcrm' ) );
			$items[] = array( 'id' => 'leads', 'title' => __( 'سرنخ‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/leads', 'icon' => 'ri-user-add-line' );
			$items[] = array( 'id' => 'customers', 'title' => __( 'مشتریان', 'puzzlingcrm' ), 'url' => $dashboard_url . '/customers', 'icon' => 'ri-group-line' );
			$items[] = array( 'id' => 'tickets', 'title' => __( 'تیکت‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/tickets', 'icon' => 'ri-customer-service-2-line' );
			$items[] = array( 'id' => 'invoices', 'title' => __( 'پیش‌فاکتورها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/invoices', 'icon' => 'ri-file-list-3-line' );
			$items[] = array( 'id' => 'services', 'title' => __( 'خدمات و محصولات', 'puzzlingcrm' ), 'url' => $dashboard_url . '/services', 'icon' => 'ri-service-line' );
			$items[] = array( 'id' => 'campaigns', 'title' => __( 'کمپین‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/campaigns', 'icon' => 'ri-megaphone-line' );
			$items[] = array( 'id' => 'consultations', 'title' => __( 'مشاوره‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/consultations', 'icon' => 'ri-discuss-line' );
			$items[] = array( 'id' => 'staff', 'title' => __( 'کارکنان', 'puzzlingcrm' ), 'url' => $dashboard_url . '/staff', 'icon' => 'ri-user-star-line' );
		}

		// Module 4: حسابداری
		if ( self::is_module_enabled( 'accounting' ) ) {
			$items[] = array( 'type' => 'category', 'title' => __( 'حسابداری', 'puzzlingcrm' ) );
			$items[] = array( 'id' => 'accounting', 'title' => __( 'داشبورد حسابداری', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting', 'icon' => 'ri-calculator-line' );
			$items[] = array( 'id' => 'accounting-persons', 'title' => __( 'اشخاص (طرف‌های حساب)', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/persons', 'icon' => 'ri-user-line' );
			$items[] = array( 'id' => 'accounting-products', 'title' => __( 'کالا و خدمات', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/products', 'icon' => 'ri-box-3-line' );
			$items[] = array( 'id' => 'accounting-invoices', 'title' => __( 'فاکتورها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/invoices', 'icon' => 'ri-file-list-3-line' );
			$items[] = array( 'id' => 'accounting-cash-accounts', 'title' => __( 'حساب‌های بانک/صندوق', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/cash-accounts', 'icon' => 'ri-bank-line' );
			$items[] = array( 'id' => 'accounting-receipts', 'title' => __( 'رسید و پرداخت', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/receipts', 'icon' => 'ri-exchange-dollar-line' );
			$items[] = array( 'id' => 'accounting-checks', 'title' => __( 'چک‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/checks', 'icon' => 'ri-bank-card-line' );
			$items[] = array( 'id' => 'accounting-chart', 'title' => __( 'نمودار حساب‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/chart', 'icon' => 'ri-book-open-line' );
			$items[] = array( 'id' => 'accounting-journals', 'title' => __( 'اسناد حسابداری', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/journals', 'icon' => 'ri-file-list-3-line' );
			$items[] = array( 'id' => 'accounting-ledger', 'title' => __( 'دفتر کل / معین', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/ledger', 'icon' => 'ri-book-2-line' );
			$items[] = array( 'id' => 'accounting-reports', 'title' => __( 'گزارشات مالی', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/reports', 'icon' => 'ri-bar-chart-2-line' );
			$items[] = array( 'id' => 'accounting-fiscal-year', 'title' => __( 'سال مالی', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/fiscal-year', 'icon' => 'ri-calendar-line' );
			$items[] = array( 'id' => 'accounting-settings', 'title' => __( 'تنظیمات حسابداری', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/settings', 'icon' => 'ri-settings-3-line' );
		}

		// System & Settings
		$items[] = array( 'type' => 'category', 'title' => __( 'سیستم و تنظیمات', 'puzzlingcrm' ) );
		$items[] = array( 'id' => 'licenses', 'title' => __( 'لایسنس‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/licenses', 'icon' => 'ri-key-2-line' );
		$items[] = array( 'id' => 'logs', 'title' => __( 'لاگ‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/logs', 'icon' => 'ri-file-list-2-line' );
		$items[] = array( 'id' => 'visitor-statistics', 'title' => __( 'آمار بازدید', 'puzzlingcrm' ), 'url' => $dashboard_url . '/visitor-statistics', 'icon' => 'ri-line-chart-line' );
		$items[] = array( 'id' => 'settings', 'title' => __( 'تنظیمات عمومی', 'puzzlingcrm' ), 'url' => $dashboard_url . '/settings', 'icon' => 'ri-settings-3-line' );

		return $items;
	}

	/**
	 * Get sales consultant menu items
	 *
	 * @return array Menu items.
	 */
	private static function get_sales_consultant_menu() {
		$dashboard_url = home_url( '/dashboard' );

		return array(
			array( 'type' => 'category', 'title' => __( 'داشبورد پیشفرض', 'puzzlingcrm' ) ),
			array( 'id' => 'dashboard', 'title' => __( 'داشبورد', 'puzzlingcrm' ), 'url' => $dashboard_url, 'icon' => 'ri-home-4-line' ),
			array( 'type' => 'category', 'title' => __( 'ارتباط با مشتری', 'puzzlingcrm' ) ),
			array( 'id' => 'leads', 'title' => __( 'سرنخ‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/leads', 'icon' => 'ri-user-add-line' ),
			array( 'id' => 'contracts', 'title' => __( 'قراردادها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/contracts', 'icon' => 'ri-file-text-line' ),
			array( 'id' => 'customers', 'title' => __( 'مشتریان', 'puzzlingcrm' ), 'url' => $dashboard_url . '/customers', 'icon' => 'ri-group-line' ),
		);
	}

	/**
	 * Get finance manager menu items
	 *
	 * @return array Menu items.
	 */
	private static function get_finance_menu() {
		$dashboard_url = home_url( '/dashboard' );
		$items          = array();

		$items[] = array( 'type' => 'category', 'title' => __( 'داشبورد پیشفرض', 'puzzlingcrm' ) );
		$items[] = array( 'id' => 'dashboard', 'title' => __( 'داشبورد', 'puzzlingcrm' ), 'url' => $dashboard_url, 'icon' => 'ri-home-4-line' );
		$items[] = array( 'id' => 'reports', 'title' => __( 'گزارشات', 'puzzlingcrm' ), 'url' => $dashboard_url . '/reports', 'icon' => 'ri-bar-chart-box-line' );
		$items[] = array( 'type' => 'category', 'title' => __( 'ارتباط با مشتری', 'puzzlingcrm' ) );
		$items[] = array( 'id' => 'contracts', 'title' => __( 'قراردادها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/contracts', 'icon' => 'ri-file-text-line' );
		$items[] = array( 'id' => 'invoices', 'title' => __( 'پیش‌فاکتورها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/invoices', 'icon' => 'ri-file-list-3-line' );

		if ( self::is_module_enabled( 'accounting' ) ) {
			$items[] = array( 'type' => 'category', 'title' => __( 'حسابداری', 'puzzlingcrm' ) );
			$items[] = array( 'id' => 'accounting', 'title' => __( 'داشبورد حسابداری', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting', 'icon' => 'ri-calculator-line' );
			$items[] = array( 'id' => 'accounting-persons', 'title' => __( 'اشخاص (طرف‌های حساب)', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/persons', 'icon' => 'ri-user-line' );
			$items[] = array( 'id' => 'accounting-products', 'title' => __( 'کالا و خدمات', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/products', 'icon' => 'ri-box-3-line' );
			$items[] = array( 'id' => 'accounting-invoices', 'title' => __( 'فاکتورها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/invoices', 'icon' => 'ri-file-list-3-line' );
			$items[] = array( 'id' => 'accounting-cash-accounts', 'title' => __( 'حساب‌های بانک/صندوق', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/cash-accounts', 'icon' => 'ri-bank-line' );
			$items[] = array( 'id' => 'accounting-receipts', 'title' => __( 'رسید و پرداخت', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/receipts', 'icon' => 'ri-exchange-dollar-line' );
			$items[] = array( 'id' => 'accounting-checks', 'title' => __( 'چک‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/checks', 'icon' => 'ri-bank-card-line' );
			$items[] = array( 'id' => 'accounting-chart', 'title' => __( 'نمودار حساب‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/chart', 'icon' => 'ri-book-open-line' );
			$items[] = array( 'id' => 'accounting-journals', 'title' => __( 'اسناد حسابداری', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/journals', 'icon' => 'ri-file-list-3-line' );
			$items[] = array( 'id' => 'accounting-ledger', 'title' => __( 'دفتر کل / معین', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/ledger', 'icon' => 'ri-book-2-line' );
			$items[] = array( 'id' => 'accounting-reports', 'title' => __( 'گزارشات مالی', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/reports', 'icon' => 'ri-bar-chart-2-line' );
			$items[] = array( 'id' => 'accounting-fiscal-year', 'title' => __( 'سال مالی', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/fiscal-year', 'icon' => 'ri-calendar-line' );
			$items[] = array( 'id' => 'accounting-settings', 'title' => __( 'تنظیمات حسابداری', 'puzzlingcrm' ), 'url' => $dashboard_url . '/accounting/settings', 'icon' => 'ri-settings-3-line' );
		}

		return $items;
	}

	/**
	 * Get team member menu items
	 *
	 * @return array Menu items.
	 */
	private static function get_team_member_menu() {
		$dashboard_url = home_url( '/dashboard' );

		return array(
			array( 'type' => 'category', 'title' => __( 'داشبورد پیشفرض', 'puzzlingcrm' ) ),
			array( 'id' => 'dashboard', 'title' => __( 'داشبورد', 'puzzlingcrm' ), 'url' => $dashboard_url, 'icon' => 'ri-home-4-line' ),
			array( 'type' => 'category', 'title' => __( 'مدیریت پروژه', 'puzzlingcrm' ) ),
			array( 'id' => 'projects', 'title' => __( 'پروژه‌های من', 'puzzlingcrm' ), 'url' => $dashboard_url . '/projects', 'icon' => 'ri-folder-2-line' ),
			array( 'id' => 'tasks', 'title' => __( 'وظایف من', 'puzzlingcrm' ), 'url' => $dashboard_url . '/tasks', 'icon' => 'ri-task-line' ),
			array( 'id' => 'tickets', 'title' => __( 'تیکت‌ها', 'puzzlingcrm' ), 'url' => $dashboard_url . '/tickets', 'icon' => 'ri-customer-service-2-line' ),
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
			array( 'id' => 'dashboard', 'title' => __( 'داشبورد', 'puzzlingcrm' ), 'url' => $dashboard_url, 'icon' => 'ri-home-4-line' ),
			array( 'id' => 'projects', 'title' => __( 'پروژه‌های من', 'puzzlingcrm' ), 'url' => $dashboard_url . '/projects', 'icon' => 'ri-folder-2-line' ),
			array( 'id' => 'contracts', 'title' => __( 'قراردادهای من', 'puzzlingcrm' ), 'url' => $dashboard_url . '/contracts', 'icon' => 'ri-file-text-line' ),
			array( 'id' => 'invoices', 'title' => __( 'پیش‌فاکتورهای من', 'puzzlingcrm' ), 'url' => $dashboard_url . '/invoices', 'icon' => 'ri-file-list-3-line' ),
			array( 'id' => 'tickets', 'title' => __( 'تیکت‌های پشتیبانی', 'puzzlingcrm' ), 'url' => $dashboard_url . '/tickets', 'icon' => 'ri-customer-service-2-line' ),
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
