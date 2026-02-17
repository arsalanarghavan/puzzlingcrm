<?php
/**
 * Accounting Module bootstrap – loads classes and registers AJAX.
 *
 * @package PuzzlingCRM
 * @subpackage Accounting
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PuzzlingCRM_Accounting_Module
 */
class PuzzlingCRM_Accounting_Module {

	/**
	 * Whether the module is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( ! class_exists( 'PuzzlingCRM_Sidebar_Menu_Builder' ) ) {
			return true;
		}
		return PuzzlingCRM_Sidebar_Menu_Builder::is_module_enabled( 'accounting' );
	}

	/**
	 * Load accounting module (classes + AJAX handler).
	 */
	public static function load() {
		if ( ! self::is_enabled() ) {
			return;
		}

		$dir = PUZZLINGCRM_PLUGIN_DIR . 'includes/modules/accounting/';
		require_once $dir . 'class-fiscal-year.php';
		require_once $dir . 'class-chart-of-accounts.php';
		require_once $dir . 'class-journal-entry.php';
		require_once $dir . 'class-ledger.php';
		require_once $dir . 'class-accounting-reports.php';
		require_once $dir . 'class-chart-seeder.php';
		require_once $dir . 'class-accounting-person-category.php';
		require_once $dir . 'class-accounting-person.php';
		require_once $dir . 'class-accounting-product-category.php';
		require_once $dir . 'class-accounting-unit.php';
		require_once $dir . 'class-accounting-price-list.php';
		require_once $dir . 'class-accounting-product.php';
		require_once $dir . 'class-accounting-user-defaults.php';
		require_once $dir . 'class-accounting-invoice.php';
		require_once $dir . 'class-accounting-invoice-line.php';
		require_once $dir . 'class-accounting-cash-account.php';
		require_once $dir . 'class-accounting-receipt-voucher.php';
		require_once $dir . 'class-accounting-check.php';
		require_once $dir . 'ajax/class-accounting-ajax-handler.php';

		new PuzzlingCRM_Accounting_Ajax_Handler();
	}
}
