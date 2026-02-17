<?php
/**
 * Accounting AJAX handler – CRUD for chart, fiscal year, journals, reports.
 *
 * @package PuzzlingCRM
 * @subpackage Accounting
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PuzzlingCRM_Accounting_Ajax_Handler
 */
class PuzzlingCRM_Accounting_Ajax_Handler {

	/**
	 * Constructor – register AJAX actions.
	 */
	public function __construct() {
		$prefix = 'wp_ajax_puzzlingcrm_accounting_';
		add_action( $prefix . 'fiscal_years', array( $this, 'fiscal_years' ) );
		add_action( $prefix . 'fiscal_year_save', array( $this, 'fiscal_year_save' ) );
		add_action( $prefix . 'fiscal_year_delete', array( $this, 'fiscal_year_delete' ) );
		add_action( $prefix . 'chart_list', array( $this, 'chart_list' ) );
		add_action( $prefix . 'chart_save', array( $this, 'chart_save' ) );
		add_action( $prefix . 'chart_delete', array( $this, 'chart_delete' ) );
		add_action( $prefix . 'journal_list', array( $this, 'journal_list' ) );
		add_action( $prefix . 'journal_get', array( $this, 'journal_get' ) );
		add_action( $prefix . 'journal_save', array( $this, 'journal_save' ) );
		add_action( $prefix . 'journal_post', array( $this, 'journal_post' ) );
		add_action( $prefix . 'journal_delete', array( $this, 'journal_delete' ) );
		add_action( $prefix . 'ledger', array( $this, 'ledger' ) );
		add_action( $prefix . 'report_trial_balance', array( $this, 'report_trial_balance' ) );
		add_action( $prefix . 'report_balance_sheet', array( $this, 'report_balance_sheet' ) );
		add_action( $prefix . 'report_profit_loss', array( $this, 'report_profit_loss' ) );
		add_action( $prefix . 'settings_get', array( $this, 'settings_get' ) );
		add_action( $prefix . 'settings_save', array( $this, 'settings_save' ) );
		add_action( $prefix . 'seed_chart', array( $this, 'seed_chart' ) );
		// Phase 1: Persons
		add_action( $prefix . 'person_categories', array( $this, 'person_categories' ) );
		add_action( $prefix . 'person_category_save', array( $this, 'person_category_save' ) );
		add_action( $prefix . 'person_category_delete', array( $this, 'person_category_delete' ) );
		add_action( $prefix . 'persons_list', array( $this, 'persons_list' ) );
		add_action( $prefix . 'person_get', array( $this, 'person_get' ) );
		add_action( $prefix . 'person_save', array( $this, 'person_save' ) );
		add_action( $prefix . 'person_delete', array( $this, 'person_delete' ) );
		// Phase 1: Products, units, price lists
		add_action( $prefix . 'product_categories', array( $this, 'product_categories' ) );
		add_action( $prefix . 'product_category_save', array( $this, 'product_category_save' ) );
		add_action( $prefix . 'product_category_delete', array( $this, 'product_category_delete' ) );
		add_action( $prefix . 'units_list', array( $this, 'units_list' ) );
		add_action( $prefix . 'unit_save', array( $this, 'unit_save' ) );
		add_action( $prefix . 'unit_delete', array( $this, 'unit_delete' ) );
		add_action( $prefix . 'price_lists', array( $this, 'price_lists' ) );
		add_action( $prefix . 'price_list_get', array( $this, 'price_list_get' ) );
		add_action( $prefix . 'price_list_save', array( $this, 'price_list_save' ) );
		add_action( $prefix . 'price_list_delete', array( $this, 'price_list_delete' ) );
		add_action( $prefix . 'price_list_items', array( $this, 'price_list_items' ) );
		add_action( $prefix . 'price_list_items_save', array( $this, 'price_list_items_save' ) );
		add_action( $prefix . 'products_list', array( $this, 'products_list' ) );
		add_action( $prefix . 'product_get', array( $this, 'product_get' ) );
		add_action( $prefix . 'product_save', array( $this, 'product_save' ) );
		add_action( $prefix . 'product_delete', array( $this, 'product_delete' ) );
		add_action( $prefix . 'user_defaults_get', array( $this, 'user_defaults_get' ) );
		add_action( $prefix . 'user_defaults_save', array( $this, 'user_defaults_save' ) );
		// Phase 2: Invoices
		add_action( $prefix . 'invoice_list', array( $this, 'invoice_list' ) );
		add_action( $prefix . 'invoice_get', array( $this, 'invoice_get' ) );
		add_action( $prefix . 'invoice_save', array( $this, 'invoice_save' ) );
		add_action( $prefix . 'invoice_delete', array( $this, 'invoice_delete' ) );
		add_action( $prefix . 'invoice_next_number', array( $this, 'invoice_next_number' ) );
		add_action( $prefix . 'invoice_confirm', array( $this, 'invoice_confirm' ) );
		// Phase 3: Cash accounts (صندوق/بانک/تنخواه) and Receipt/Payment
		add_action( $prefix . 'cash_accounts_list', array( $this, 'cash_accounts_list' ) );
		add_action( $prefix . 'cash_account_get', array( $this, 'cash_account_get' ) );
		add_action( $prefix . 'cash_account_save', array( $this, 'cash_account_save' ) );
		add_action( $prefix . 'cash_account_delete', array( $this, 'cash_account_delete' ) );
		add_action( $prefix . 'receipt_voucher_list', array( $this, 'receipt_voucher_list' ) );
		add_action( $prefix . 'receipt_voucher_get', array( $this, 'receipt_voucher_get' ) );
		add_action( $prefix . 'receipt_voucher_save', array( $this, 'receipt_voucher_save' ) );
		add_action( $prefix . 'receipt_voucher_post', array( $this, 'receipt_voucher_post' ) );
		add_action( $prefix . 'receipt_voucher_delete', array( $this, 'receipt_voucher_delete' ) );
		add_action( $prefix . 'receipt_voucher_next_number', array( $this, 'receipt_voucher_next_number' ) );
		// Phase 4: Cheques (چک دریافتی و پرداختی)
		add_action( $prefix . 'check_list', array( $this, 'check_list' ) );
		add_action( $prefix . 'check_get', array( $this, 'check_get' ) );
		add_action( $prefix . 'check_save', array( $this, 'check_save' ) );
		add_action( $prefix . 'check_delete', array( $this, 'check_delete' ) );
		add_action( $prefix . 'check_set_status', array( $this, 'check_set_status' ) );
	}

	/**
	 * Check nonce and capability.
	 *
	 * @return bool
	 */
	private function check_access() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ), 403 );
		}
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : ( isset( $_POST['security'] ) ? $_POST['security'] : '' );
		if ( ! $nonce || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'puzzlingcrm-ajax-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'خطای امنیتی.', 'puzzlingcrm' ) ), 403 );
		}
		return true;
	}

	/**
	 * GET fiscal years list.
	 */
	public function fiscal_years() {
		$this->check_access();
		$list = PuzzlingCRM_Accounting_Fiscal_Year::get_all();
		wp_send_json_success( array( 'items' => $list ) );
	}

	/**
	 * Create or update fiscal year.
	 */
	public function fiscal_year_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$data = array(
			'name'       => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'start_date' => isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '',
			'end_date'   => isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '',
			'is_active'  => isset( $_POST['is_active'] ) ? 1 : 0,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Fiscal_Year::update( $id, $data );
			wp_send_json_success( array( 'updated' => $ok, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Fiscal_Year::create( $data );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد سال مالی.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	/**
	 * Delete fiscal year.
	 */
	public function fiscal_year_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Fiscal_Year::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف این سال مالی وجود ندارد (دارای سند است).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	/**
	 * GET chart of accounts tree.
	 */
	public function chart_list() {
		$this->check_access();
		$fiscal_year_id = isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0;
		if ( $fiscal_year_id <= 0 ) {
			$active = PuzzlingCRM_Accounting_Fiscal_Year::get_active();
			$fiscal_year_id = $active ? (int) $active->id : 0;
		}
		if ( $fiscal_year_id <= 0 ) {
			wp_send_json_success( array( 'items' => array() ) );
		}
		$items = PuzzlingCRM_Accounting_Chart_Of_Accounts::get_tree( $fiscal_year_id );
		wp_send_json_success( array( 'items' => $items ) );
	}

	/**
	 * Create or update chart account.
	 */
	public function chart_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$data = array(
			'code'           => isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '',
			'title'          => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'level'          => isset( $_POST['level'] ) ? (int) $_POST['level'] : 1,
			'parent_id'      => isset( $_POST['parent_id'] ) ? (int) $_POST['parent_id'] : 0,
			'account_type'   => isset( $_POST['account_type'] ) ? sanitize_key( wp_unslash( $_POST['account_type'] ) ) : 'asset',
			'fiscal_year_id' => isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0,
			'sort_order'     => isset( $_POST['sort_order'] ) ? (int) $_POST['sort_order'] : 0,
		);
		if ( $data['fiscal_year_id'] <= 0 ) {
			$active = PuzzlingCRM_Accounting_Fiscal_Year::get_active();
			$data['fiscal_year_id'] = $active ? (int) $active->id : 0;
		}
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Chart_Of_Accounts::update( $id, $data );
			wp_send_json_success( array( 'updated' => $ok, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Chart_Of_Accounts::create( $data );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد حساب (احتمالاً کد تکراری است).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	/**
	 * Delete chart account.
	 */
	public function chart_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Chart_Of_Accounts::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف این حساب وجود ندارد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	/**
	 * List journal entries.
	 */
	public function journal_list() {
		$this->check_access();
		$args = array(
			'fiscal_year_id' => isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0,
			'status'        => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '',
			'date_from'     => isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '',
			'date_to'       => isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '',
			'per_page'      => isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : 20,
			'page'          => isset( $_POST['page'] ) ? (int) $_POST['page'] : 1,
		);
		$result = PuzzlingCRM_Accounting_Journal_Entry::list_entries( $args );
		wp_send_json_success( $result );
	}

	/**
	 * Get single journal entry with lines.
	 */
	public function journal_get() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$entry = PuzzlingCRM_Accounting_Journal_Entry::get( $id );
		if ( ! $entry ) {
			wp_send_json_error( array( 'message' => __( 'سند یافت نشد.', 'puzzlingcrm' ) ) );
		}
		$lines = PuzzlingCRM_Accounting_Journal_Entry::get_lines( $id );
		wp_send_json_success( array( 'entry' => $entry, 'lines' => $lines ) );
	}

	/**
	 * Create or update journal entry.
	 */
	public function journal_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$lines = array();
		if ( ! empty( $_POST['lines'] ) && is_array( $_POST['lines'] ) ) {
			foreach ( $_POST['lines'] as $l ) {
				$lines[] = array(
					'account_id'  => isset( $l['account_id'] ) ? (int) $l['account_id'] : 0,
					'debit'       => isset( $l['debit'] ) ? (float) $l['debit'] : 0,
					'credit'      => isset( $l['credit'] ) ? (float) $l['credit'] : 0,
					'description' => isset( $l['description'] ) ? sanitize_textarea_field( wp_unslash( $l['description'] ) ) : '',
				);
			}
		}
		$data = array(
			'fiscal_year_id'  => isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0,
			'voucher_date'    => isset( $_POST['voucher_date'] ) ? sanitize_text_field( wp_unslash( $_POST['voucher_date'] ) ) : '',
			'description'     => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'reference_type'  => isset( $_POST['reference_type'] ) ? sanitize_key( wp_unslash( $_POST['reference_type'] ) ) : null,
			'reference_id'    => isset( $_POST['reference_id'] ) ? (int) $_POST['reference_id'] : null,
			'status'         => 'draft',
			'lines'          => $lines,
		);
		if ( $id > 0 ) {
			$data['lines'] = $lines;
			$ok = PuzzlingCRM_Accounting_Journal_Entry::update( $id, $data );
			wp_send_json_success( array( 'updated' => $ok, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Journal_Entry::create( $data, get_current_user_id() );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'جمع بدهکار و بستانکار برابر نیست یا خطای دیگر.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	/**
	 * Post (finalize) journal entry.
	 */
	public function journal_post() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Journal_Entry::post( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان ثبت سند وجود ندارد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	/**
	 * Delete draft journal entry.
	 */
	public function journal_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Journal_Entry::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف سند وجود ندارد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	/**
	 * Ledger (account turnover).
	 */
	public function ledger() {
		$this->check_access();
		$account_id     = isset( $_POST['account_id'] ) ? (int) $_POST['account_id'] : 0;
		$fiscal_year_id = isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0;
		$date_from      = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
		$date_to        = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
		if ( $account_id <= 0 || $fiscal_year_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'حساب و سال مالی الزامی است.', 'puzzlingcrm' ) ) );
		}
		$result = PuzzlingCRM_Accounting_Reports::account_turnover( $account_id, $fiscal_year_id, $date_from, $date_to );
		wp_send_json_success( $result );
	}

	/**
	 * Report: trial balance.
	 */
	public function report_trial_balance() {
		$this->check_access();
		$fiscal_year_id = isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0;
		$date_from      = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
		$date_to        = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
		$data = PuzzlingCRM_Accounting_Reports::trial_balance( $fiscal_year_id, $date_from, $date_to );
		wp_send_json_success( $data );
	}

	/**
	 * Report: balance sheet.
	 */
	public function report_balance_sheet() {
		$this->check_access();
		$fiscal_year_id = isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0;
		$as_of_date     = isset( $_POST['as_of_date'] ) ? sanitize_text_field( wp_unslash( $_POST['as_of_date'] ) ) : '';
		$data = PuzzlingCRM_Accounting_Reports::balance_sheet( $fiscal_year_id, $as_of_date );
		wp_send_json_success( $data );
	}

	/**
	 * Report: profit and loss.
	 */
	public function report_profit_loss() {
		$this->check_access();
		$fiscal_year_id = isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0;
		$date_from      = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
		$date_to        = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
		$data = PuzzlingCRM_Accounting_Reports::profit_and_loss( $fiscal_year_id, $date_from, $date_to );
		wp_send_json_success( $data );
	}

	/**
	 * Get accounting settings (currency, default fiscal year, etc.).
	 */
	public function settings_get() {
		$this->check_access();
		$settings = class_exists( 'PuzzlingCRM_Settings_Handler' ) ? PuzzlingCRM_Settings_Handler::get_all_settings() : array();
		$out = array(
			'currency'        => isset( $settings['accounting_currency'] ) ? $settings['accounting_currency'] : 'rial',
			'fiscal_year_id'  => isset( $settings['accounting_default_fiscal_year_id'] ) ? (int) $settings['accounting_default_fiscal_year_id'] : 0,
		);
		wp_send_json_success( $out );
	}

	/**
	 * Save accounting settings.
	 */
	public function settings_save() {
		$this->check_access();
		$current = class_exists( 'PuzzlingCRM_Settings_Handler' ) ? PuzzlingCRM_Settings_Handler::get_all_settings() : array();
		if ( isset( $_POST['currency'] ) ) {
			$current['accounting_currency'] = sanitize_text_field( wp_unslash( $_POST['currency'] ) );
		}
		if ( isset( $_POST['fiscal_year_id'] ) ) {
			$current['accounting_default_fiscal_year_id'] = (int) $_POST['fiscal_year_id'];
		}
		PuzzlingCRM_Settings_Handler::update_settings( $current );
		wp_send_json_success();
	}

	/**
	 * Seed default Iranian chart of accounts (called from settings or install).
	 */
	public function seed_chart() {
		$this->check_access();
		$fiscal_year_id = isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0;
		if ( $fiscal_year_id <= 0 ) {
			$active = PuzzlingCRM_Accounting_Fiscal_Year::get_active();
			$fiscal_year_id = $active ? (int) $active->id : 0;
		}
		if ( $fiscal_year_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'ابتدا یک سال مالی تعریف کنید.', 'puzzlingcrm' ) ) );
		}
		if ( ! class_exists( 'PuzzlingCRM_Accounting_Chart_Seeder' ) ) {
			wp_send_json_error( array( 'message' => __( 'سرویس بارگذاری کدینگ در دسترس نیست.', 'puzzlingcrm' ) ) );
		}
		$seeder = new PuzzlingCRM_Accounting_Chart_Seeder();
		$count  = $seeder->seed( $fiscal_year_id );
		wp_send_json_success( array( 'count' => $count ) );
	}

	// --- Phase 1: Person categories ---
	public function person_categories() {
		$this->check_access();
		$tree = isset( $_POST['tree'] ) && $_POST['tree'];
		if ( $tree ) {
			$items = PuzzlingCRM_Accounting_Person_Category::get_tree( 0 );
		} else {
			$items = PuzzlingCRM_Accounting_Person_Category::get_all_flat();
		}
		wp_send_json_success( array( 'items' => $items ) );
	}

	public function person_category_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$data = array(
			'name'       => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'parent_id'  => isset( $_POST['parent_id'] ) ? (int) $_POST['parent_id'] : 0,
			'sort_order' => isset( $_POST['sort_order'] ) ? (int) $_POST['sort_order'] : 0,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Person_Category::update( $id, $data );
			wp_send_json_success( array( 'updated' => $ok, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Person_Category::create( $data );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد دسته.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	public function person_category_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Person_Category::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف دسته وجود ندارد (دارای زیردسته یا شخص است).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	// --- Phase 1: Persons ---
	public function persons_list() {
		$this->check_access();
		$args = array(
			'category_id'  => isset( $_POST['category_id'] ) ? (int) $_POST['category_id'] : 0,
			'person_type'  => isset( $_POST['person_type'] ) ? sanitize_text_field( wp_unslash( $_POST['person_type'] ) ) : '',
			'search'       => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'is_active'    => isset( $_POST['is_active'] ) ? (int) $_POST['is_active'] : '',
			'per_page'     => isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : 50,
			'page'         => isset( $_POST['page'] ) ? (int) $_POST['page'] : 1,
			'orderby'      => isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'id',
			'order'        => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
		);
		if ( empty( $args['category_id'] ) ) {
			unset( $args['category_id'] );
		}
		if ( empty( $args['person_type'] ) ) {
			unset( $args['person_type'] );
		}
		if ( empty( $args['search'] ) ) {
			unset( $args['search'] );
		}
		if ( $args['is_active'] === '' ) {
			unset( $args['is_active'] );
		}
		$result = PuzzlingCRM_Accounting_Person::get_list( $args );
		wp_send_json_success( $result );
	}

	public function person_get() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$person = PuzzlingCRM_Accounting_Person::get( $id );
		if ( ! $person ) {
			wp_send_json_error( array( 'message' => __( 'شخص یافت نشد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'person' => $person ) );
	}

	public function person_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$cat_id = isset( $_POST['category_id'] ) ? (int) $_POST['category_id'] : null;
		$grp_id = isset( $_POST['group_id'] ) ? (int) $_POST['group_id'] : null;
		$pl_id  = isset( $_POST['default_price_list_id'] ) ? (int) $_POST['default_price_list_id'] : null;
		$data = array(
			'name'                   => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'code'                   => isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : null,
			'category_id'            => $cat_id > 0 ? $cat_id : null,
			'credit_limit'           => isset( $_POST['credit_limit'] ) ? floatval( $_POST['credit_limit'] ) : null,
			'national_id'            => isset( $_POST['national_id'] ) ? sanitize_text_field( wp_unslash( $_POST['national_id'] ) ) : null,
			'economic_code'          => isset( $_POST['economic_code'] ) ? sanitize_text_field( wp_unslash( $_POST['economic_code'] ) ) : null,
			'registration_no'        => isset( $_POST['registration_no'] ) ? sanitize_text_field( wp_unslash( $_POST['registration_no'] ) ) : null,
			'phone'                  => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : null,
			'mobile'                 => isset( $_POST['mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['mobile'] ) ) : null,
			'extra_phones'           => isset( $_POST['extra_phones'] ) ? sanitize_textarea_field( wp_unslash( $_POST['extra_phones'] ) ) : null,
			'address'                => isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : null,
			'person_type'            => isset( $_POST['person_type'] ) ? sanitize_text_field( wp_unslash( $_POST['person_type'] ) ) : 'both',
			'group_id'               => $grp_id > 0 ? $grp_id : null,
			'image_url'              => isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : null,
			'note'                   => isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : null,
			'default_price_list_id'  => $pl_id > 0 ? $pl_id : null,
			'is_active'              => isset( $_POST['is_active'] ) ? 1 : 0,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Person::update( $id, $data );
			wp_send_json_success( array( 'updated' => $ok, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Person::create( $data );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد شخص.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	public function person_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Person::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف شخص وجود ندارد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	// --- Phase 1: Product categories ---
	public function product_categories() {
		$this->check_access();
		$tree = isset( $_POST['tree'] ) && $_POST['tree'];
		if ( $tree ) {
			$items = PuzzlingCRM_Accounting_Product_Category::get_tree( 0 );
		} else {
			$items = PuzzlingCRM_Accounting_Product_Category::get_all_flat();
		}
		wp_send_json_success( array( 'items' => $items ) );
	}

	public function product_category_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$data = array(
			'name'       => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'parent_id'  => isset( $_POST['parent_id'] ) ? (int) $_POST['parent_id'] : 0,
			'sort_order' => isset( $_POST['sort_order'] ) ? (int) $_POST['sort_order'] : 0,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Product_Category::update( $id, $data );
			wp_send_json_success( array( 'updated' => $ok, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Product_Category::create( $data );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد دسته کالا.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	public function product_category_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Product_Category::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف دسته وجود ندارد (دارای زیردسته یا کالا است).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	// --- Phase 1: Units ---
	public function units_list() {
		$this->check_access();
		$items = PuzzlingCRM_Accounting_Unit::get_all();
		wp_send_json_success( array( 'items' => $items ) );
	}

	public function unit_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$data = array(
			'name'          => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'symbol'        => isset( $_POST['symbol'] ) ? sanitize_text_field( wp_unslash( $_POST['symbol'] ) ) : null,
			'is_main'       => isset( $_POST['is_main'] ) ? (int) $_POST['is_main'] : 1,
			'base_unit_id'  => isset( $_POST['base_unit_id'] ) ? (int) $_POST['base_unit_id'] : null,
			'ratio_to_base' => isset( $_POST['ratio_to_base'] ) ? floatval( $_POST['ratio_to_base'] ) : 1,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Unit::update( $id, $data );
			wp_send_json_success( array( 'updated' => $ok, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Unit::create( $data );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد واحد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	public function unit_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Unit::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف واحد وجود ندارد (در کالاها استفاده شده).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	// --- Phase 1: Price lists ---
	public function price_lists() {
		$this->check_access();
		$items = PuzzlingCRM_Accounting_Price_List::get_all();
		wp_send_json_success( array( 'items' => $items ) );
	}

	public function price_list_get() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$list = PuzzlingCRM_Accounting_Price_List::get( $id );
		if ( ! $list ) {
			wp_send_json_error( array( 'message' => __( 'لیست قیمت یافت نشد.', 'puzzlingcrm' ) ) );
		}
		$items = PuzzlingCRM_Accounting_Price_List::get_items( $id );
		wp_send_json_success( array( 'price_list' => $list, 'items' => $items ) );
	}

	public function price_list_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$data = array(
			'name'        => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : null,
			'is_default'  => isset( $_POST['is_default'] ) ? 1 : 0,
			'valid_from'  => isset( $_POST['valid_from'] ) ? sanitize_text_field( wp_unslash( $_POST['valid_from'] ) ) : null,
			'valid_to'    => isset( $_POST['valid_to'] ) ? sanitize_text_field( wp_unslash( $_POST['valid_to'] ) ) : null,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Price_List::update( $id, $data );
			wp_send_json_success( array( 'updated' => $ok, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Price_List::create( $data );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد لیست قیمت.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	public function price_list_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Price_List::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف لیست قیمت وجود ندارد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	public function price_list_items() {
		$this->check_access();
		$price_list_id = isset( $_POST['price_list_id'] ) ? (int) $_POST['price_list_id'] : 0;
		if ( $price_list_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'لیست قیمت نامعتبر است.', 'puzzlingcrm' ) ) );
		}
		$items = PuzzlingCRM_Accounting_Price_List::get_items( $price_list_id );
		wp_send_json_success( array( 'items' => $items ) );
	}

	public function price_list_items_save() {
		$this->check_access();
		$price_list_id = isset( $_POST['price_list_id'] ) ? (int) $_POST['price_list_id'] : 0;
		if ( $price_list_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'لیست قیمت نامعتبر است.', 'puzzlingcrm' ) ) );
		}
		$items = array();
		if ( isset( $_POST['items'] ) ) {
			if ( is_array( $_POST['items'] ) ) {
				$items = $_POST['items'];
			} elseif ( is_string( $_POST['items'] ) ) {
				$decoded = json_decode( sanitize_text_field( wp_unslash( $_POST['items'] ) ), true );
				$items   = is_array( $decoded ) ? $decoded : array();
			}
		}
		// Replace semantics: remove existing items then insert submitted ones.
		global $wpdb;
		$items_table = $wpdb->prefix . 'puzzlingcrm_accounting_price_list_items';
		$wpdb->delete( $items_table, array( 'price_list_id' => $price_list_id ), array( '%d' ) );
		foreach ( $items as $row ) {
			$product_id = isset( $row['product_id'] ) ? (int) $row['product_id'] : 0;
			$price      = isset( $row['price'] ) ? floatval( $row['price'] ) : 0;
			$min_qty    = isset( $row['min_quantity'] ) ? floatval( $row['min_quantity'] ) : 1;
			if ( $product_id > 0 ) {
				PuzzlingCRM_Accounting_Price_List::set_item( $price_list_id, $product_id, $price, $min_qty );
			}
		}
		wp_send_json_success();
	}

	// --- Phase 1: Products ---
	public function products_list() {
		$this->check_access();
		$args = array(
			'category_id' => isset( $_POST['category_id'] ) ? (int) $_POST['category_id'] : 0,
			'search'      => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'is_active'   => isset( $_POST['is_active'] ) ? (int) $_POST['is_active'] : '',
			'per_page'    => isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : 50,
			'page'        => isset( $_POST['page'] ) ? (int) $_POST['page'] : 1,
			'orderby'     => isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'id',
			'order'       => isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC',
		);
		if ( empty( $args['category_id'] ) ) {
			unset( $args['category_id'] );
		}
		if ( empty( $args['search'] ) ) {
			unset( $args['search'] );
		}
		if ( $args['is_active'] === '' ) {
			unset( $args['is_active'] );
		}
		$result = PuzzlingCRM_Accounting_Product::get_list( $args );
		wp_send_json_success( $result );
	}

	public function product_get() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$product = PuzzlingCRM_Accounting_Product::get( $id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'کالا یافت نشد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'product' => $product ) );
	}

	public function product_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$cat_id   = isset( $_POST['category_id'] ) ? (int) $_POST['category_id'] : null;
		$sub_id   = isset( $_POST['sub_unit_id'] ) ? (int) $_POST['sub_unit_id'] : null;
		$data = array(
			'code'                  => isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '',
			'name'                  => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'category_id'           => $cat_id > 0 ? $cat_id : null,
			'main_unit_id'          => isset( $_POST['main_unit_id'] ) ? (int) $_POST['main_unit_id'] : 0,
			'sub_unit_id'           => $sub_id > 0 ? $sub_id : null,
			'sub_unit_ratio'        => isset( $_POST['sub_unit_ratio'] ) ? floatval( $_POST['sub_unit_ratio'] ) : 1,
			'purchase_price'        => isset( $_POST['purchase_price'] ) ? floatval( $_POST['purchase_price'] ) : null,
			'barcode'               => isset( $_POST['barcode'] ) ? sanitize_textarea_field( wp_unslash( $_POST['barcode'] ) ) : null,
			'inventory_controlled'  => isset( $_POST['inventory_controlled'] ) ? 1 : 0,
			'reorder_point'         => isset( $_POST['reorder_point'] ) ? floatval( $_POST['reorder_point'] ) : null,
			'tax_rate_sales'        => isset( $_POST['tax_rate_sales'] ) ? floatval( $_POST['tax_rate_sales'] ) : null,
			'tax_rate_purchase'     => isset( $_POST['tax_rate_purchase'] ) ? floatval( $_POST['tax_rate_purchase'] ) : null,
			'image_url'             => isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : null,
			'note'                  => isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : null,
			'is_active'             => isset( $_POST['is_active'] ) ? 1 : 0,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Product::update( $id, $data );
			wp_send_json_success( array( 'updated' => $ok, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Product::create( $data );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد کالا (احتمالاً کد تکراری است).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	public function product_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Product::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف کالا وجود ندارد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	// --- Phase 1: User defaults ---
	public function user_defaults_get() {
		$this->check_access();
		$defaults = PuzzlingCRM_Accounting_User_Defaults::get();
		wp_send_json_success( array( 'defaults' => $defaults ) );
	}

	public function user_defaults_save() {
		$this->check_access();
		$data = array(
			'default_invoice_person_id' => isset( $_POST['default_invoice_person_id'] ) ? (int) $_POST['default_invoice_person_id'] : null,
			'default_price_list_id'    => isset( $_POST['default_price_list_id'] ) ? (int) $_POST['default_price_list_id'] : null,
		);
		$ok = PuzzlingCRM_Accounting_User_Defaults::save( $data );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ذخیره پیش‌فرض‌ها.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	// --- Phase 2: Invoices ---
	public function invoice_list() {
		$this->check_access();
		$args = array(
			'fiscal_year_id' => isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0,
			'person_id'      => isset( $_POST['person_id'] ) ? (int) $_POST['person_id'] : 0,
			'invoice_type'   => isset( $_POST['invoice_type'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_type'] ) ) : '',
			'status'         => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '',
			'date_from'      => isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '',
			'date_to'        => isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '',
			'per_page'       => isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : 20,
			'page'           => isset( $_POST['page'] ) ? (int) $_POST['page'] : 1,
		);
		if ( $args['fiscal_year_id'] <= 0 ) {
			$active = PuzzlingCRM_Accounting_Fiscal_Year::get_active();
			$args['fiscal_year_id'] = $active ? (int) $active->id : 0;
		}
		foreach ( array( 'person_id', 'invoice_type', 'status', 'date_from', 'date_to' ) as $k ) {
			if ( empty( $args[ $k ] ) ) {
				unset( $args[ $k ] );
			}
		}
		$result = PuzzlingCRM_Accounting_Invoice::get_list( $args );
		wp_send_json_success( $result );
	}

	public function invoice_get() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$inv = PuzzlingCRM_Accounting_Invoice::get( $id );
		if ( ! $inv ) {
			wp_send_json_error( array( 'message' => __( 'فاکتور یافت نشد.', 'puzzlingcrm' ) ) );
		}
		$lines = PuzzlingCRM_Accounting_Invoice_Line::get_lines( $id );
		wp_send_json_success( array( 'invoice' => $inv, 'lines' => $lines ) );
	}

	public function invoice_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$lines = array();
		if ( isset( $_POST['lines'] ) ) {
			if ( is_array( $_POST['lines'] ) ) {
				$lines = $_POST['lines'];
			} elseif ( is_string( $_POST['lines'] ) ) {
				$decoded = json_decode( sanitize_text_field( wp_unslash( $_POST['lines'] ) ), true );
				$lines   = is_array( $decoded ) ? $decoded : array();
			}
		}
		$lines_processed = array();
		foreach ( $lines as $l ) {
			$lines_processed[] = array(
				'product_id'        => isset( $l['product_id'] ) ? (int) $l['product_id'] : 0,
				'quantity'          => isset( $l['quantity'] ) ? floatval( $l['quantity'] ) : 1,
				'unit_id'           => isset( $l['unit_id'] ) ? (int) $l['unit_id'] : null,
				'unit_price'        => isset( $l['unit_price'] ) ? floatval( $l['unit_price'] ) : 0,
				'discount_percent'  => isset( $l['discount_percent'] ) ? floatval( $l['discount_percent'] ) : null,
				'discount_amount'   => isset( $l['discount_amount'] ) ? floatval( $l['discount_amount'] ) : null,
				'tax_percent'       => isset( $l['tax_percent'] ) ? floatval( $l['tax_percent'] ) : null,
				'tax_amount'        => isset( $l['tax_amount'] ) ? floatval( $l['tax_amount'] ) : null,
				'description'       => isset( $l['description'] ) ? sanitize_textarea_field( wp_unslash( $l['description'] ) ) : null,
			);
		}
		$lines = $lines_processed;
		$data = array(
			'fiscal_year_id'   => isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0,
			'invoice_no'       => isset( $_POST['invoice_no'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_no'] ) ) : '',
			'invoice_type'     => isset( $_POST['invoice_type'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_type'] ) ) : 'sales',
			'person_id'        => isset( $_POST['person_id'] ) ? (int) $_POST['person_id'] : 0,
			'invoice_date'     => isset( $_POST['invoice_date'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_date'] ) ) : '',
			'due_date'         => isset( $_POST['due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['due_date'] ) ) : null,
			'seller_id'        => isset( $_POST['seller_id'] ) ? (int) $_POST['seller_id'] : null,
			'project_id'       => isset( $_POST['project_id'] ) ? (int) $_POST['project_id'] : null,
			'shipping_cost'    => isset( $_POST['shipping_cost'] ) ? floatval( $_POST['shipping_cost'] ) : null,
			'extra_additions'  => isset( $_POST['extra_additions'] ) ? floatval( $_POST['extra_additions'] ) : null,
			'extra_deductions' => isset( $_POST['extra_deductions'] ) ? floatval( $_POST['extra_deductions'] ) : null,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Invoice::update( $id, $data );
			if ( ! $ok ) {
				wp_send_json_error( array( 'message' => __( 'امکان ویرایش فاکتور وجود ندارد (فقط پیش‌نویس).', 'puzzlingcrm' ) ) );
			}
			// Only replace lines when explicitly sent (avoid wiping lines on header-only update).
			if ( isset( $_POST['lines'] ) ) {
				PuzzlingCRM_Accounting_Invoice_Line::save_lines( $id, $lines );
			}
			wp_send_json_success( array( 'updated' => true, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Invoice::create( $data, get_current_user_id() );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد فاکتور (سال مالی یا طرف حساب نامعتبر).', 'puzzlingcrm' ) ) );
		}
		PuzzlingCRM_Accounting_Invoice_Line::save_lines( $new_id, $lines );
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	public function invoice_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Invoice::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف فاکتور وجود ندارد (فقط پیش‌نویس).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	public function invoice_next_number() {
		$this->check_access();
		$fiscal_year_id = isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0;
		$invoice_type   = isset( $_POST['invoice_type'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_type'] ) ) : 'sales';
		if ( $fiscal_year_id <= 0 ) {
			$active = PuzzlingCRM_Accounting_Fiscal_Year::get_active();
			$fiscal_year_id = $active ? (int) $active->id : 0;
		}
		if ( $fiscal_year_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'سال مالی انتخاب نشده.', 'puzzlingcrm' ) ) );
		}
		$next = PuzzlingCRM_Accounting_Invoice::get_next_number( $fiscal_year_id, $invoice_type );
		wp_send_json_success( array( 'invoice_no' => $next ) );
	}

	/**
	 * Confirm (post) a draft invoice. Phase 2.
	 */
	public function invoice_confirm() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Invoice::confirm( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان تأیید فاکتور وجود ندارد (فقط پیش‌نویس قابل تأیید است).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	// --- Phase 3: Cash accounts (صندوق/بانک/تنخواه) ---
	public function cash_accounts_list() {
		$this->check_access();
		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$is_active = isset( $_POST['is_active'] ) ? (int) $_POST['is_active'] : '';
		$args = array();
		if ( $type !== '' ) {
			$args['type'] = $type;
		}
		if ( $is_active !== '' ) {
			$args['is_active'] = $is_active;
		}
		$items = PuzzlingCRM_Accounting_Cash_Account::get_all( $args );
		wp_send_json_success( array( 'items' => $items ) );
	}

	public function cash_account_get() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$item = PuzzlingCRM_Accounting_Cash_Account::get( $id );
		if ( ! $item ) {
			wp_send_json_error( array( 'message' => __( 'حساب یافت نشد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'cash_account' => $item ) );
	}

	public function cash_account_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$data = array(
			'name'             => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'type'             => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'bank',
			'code'             => isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : null,
			'description'      => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : null,
			'card_no'          => isset( $_POST['card_no'] ) ? sanitize_text_field( wp_unslash( $_POST['card_no'] ) ) : null,
			'sheba'            => isset( $_POST['sheba'] ) ? sanitize_text_field( wp_unslash( $_POST['sheba'] ) ) : null,
			'chart_account_id' => isset( $_POST['chart_account_id'] ) ? (int) $_POST['chart_account_id'] : null,
			'is_active'        => isset( $_POST['is_active'] ) ? 1 : 0,
			'sort_order'       => isset( $_POST['sort_order'] ) ? (int) $_POST['sort_order'] : 0,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Cash_Account::update( $id, $data );
			wp_send_json_success( array( 'updated' => $ok, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Cash_Account::create( $data );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد حساب.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	public function cash_account_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Cash_Account::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف حساب وجود ندارد (در رسید/پرداخت استفاده شده).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	// --- Phase 3: Receipt/Payment/Transfer vouchers ---
	public function receipt_voucher_list() {
		$this->check_access();
		$args = array(
			'fiscal_year_id'   => isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0,
			'type'             => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '',
			'cash_account_id'  => isset( $_POST['cash_account_id'] ) ? (int) $_POST['cash_account_id'] : 0,
			'person_id'        => isset( $_POST['person_id'] ) ? (int) $_POST['person_id'] : 0,
			'status'           => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '',
			'date_from'        => isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '',
			'date_to'          => isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '',
			'per_page'         => isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : 20,
			'page'             => isset( $_POST['page'] ) ? (int) $_POST['page'] : 1,
		);
		if ( $args['fiscal_year_id'] <= 0 ) {
			$active = PuzzlingCRM_Accounting_Fiscal_Year::get_active();
			$args['fiscal_year_id'] = $active ? (int) $active->id : 0;
		}
		foreach ( array( 'type', 'cash_account_id', 'person_id', 'status', 'date_from', 'date_to' ) as $k ) {
			if ( empty( $args[ $k ] ) ) {
				unset( $args[ $k ] );
			}
		}
		$result = PuzzlingCRM_Accounting_Receipt_Voucher::get_list( $args );
		wp_send_json_success( $result );
	}

	public function receipt_voucher_get() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$voucher = PuzzlingCRM_Accounting_Receipt_Voucher::get( $id );
		if ( ! $voucher ) {
			wp_send_json_error( array( 'message' => __( 'رسید/پرداخت یافت نشد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'voucher' => $voucher ) );
	}

	public function receipt_voucher_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$data = array(
			'fiscal_year_id'              => isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0,
			'voucher_no'                  => isset( $_POST['voucher_no'] ) ? sanitize_text_field( wp_unslash( $_POST['voucher_no'] ) ) : '',
			'voucher_date'                => isset( $_POST['voucher_date'] ) ? sanitize_text_field( wp_unslash( $_POST['voucher_date'] ) ) : '',
			'type'                        => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'receipt',
			'cash_account_id'             => isset( $_POST['cash_account_id'] ) ? (int) $_POST['cash_account_id'] : 0,
			'transfer_to_cash_account_id' => isset( $_POST['transfer_to_cash_account_id'] ) ? (int) $_POST['transfer_to_cash_account_id'] : null,
			'person_id'                   => isset( $_POST['person_id'] ) ? (int) $_POST['person_id'] : null,
			'amount'                      => isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0,
			'description'                 => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : null,
			'invoice_id'                  => isset( $_POST['invoice_id'] ) ? (int) $_POST['invoice_id'] : null,
			'project_id'                  => isset( $_POST['project_id'] ) ? (int) $_POST['project_id'] : null,
			'bank_fee'                    => isset( $_POST['bank_fee'] ) ? floatval( $_POST['bank_fee'] ) : null,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Receipt_Voucher::update( $id, $data );
			if ( ! $ok ) {
				wp_send_json_error( array( 'message' => __( 'امکان ویرایش وجود ندارد (فقط پیش‌نویس).', 'puzzlingcrm' ) ) );
			}
			wp_send_json_success( array( 'updated' => true, 'id' => $id ) );
		}
		$new_id = PuzzlingCRM_Accounting_Receipt_Voucher::create( $data, get_current_user_id() );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ایجاد رسید/پرداخت (حساب یا سال مالی نامعتبر).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	public function receipt_voucher_post() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Receipt_Voucher::post( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان ثبت رسید/پرداخت وجود ندارد (فقط پیش‌نویس).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	public function receipt_voucher_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Receipt_Voucher::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف وجود ندارد (فقط پیش‌نویس).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	public function receipt_voucher_next_number() {
		$this->check_access();
		$fiscal_year_id = isset( $_POST['fiscal_year_id'] ) ? (int) $_POST['fiscal_year_id'] : 0;
		if ( $fiscal_year_id <= 0 ) {
			$active = PuzzlingCRM_Accounting_Fiscal_Year::get_active();
			$fiscal_year_id = $active ? (int) $active->id : 0;
		}
		if ( $fiscal_year_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'سال مالی انتخاب نشده.', 'puzzlingcrm' ) ) );
		}
		$next = PuzzlingCRM_Accounting_Receipt_Voucher::get_next_number( $fiscal_year_id );
		wp_send_json_success( array( 'voucher_no' => $next ) );
	}

	// --- Phase 4: Cheques (چک دریافتی و پرداختی) ---
	public function check_list() {
		$this->check_access();
		$args = array(
			'type'             => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '',
			'person_id'        => isset( $_POST['person_id'] ) ? (int) $_POST['person_id'] : 0,
			'cash_account_id'  => isset( $_POST['cash_account_id'] ) ? (int) $_POST['cash_account_id'] : 0,
			'status'           => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '',
			'due_from'         => isset( $_POST['due_from'] ) ? sanitize_text_field( wp_unslash( $_POST['due_from'] ) ) : '',
			'due_to'           => isset( $_POST['due_to'] ) ? sanitize_text_field( wp_unslash( $_POST['due_to'] ) ) : '',
			'check_no'         => isset( $_POST['check_no'] ) ? sanitize_text_field( wp_unslash( $_POST['check_no'] ) ) : '',
			'per_page'         => isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : 20,
			'page'             => isset( $_POST['page'] ) ? (int) $_POST['page'] : 1,
		);
		foreach ( array( 'type', 'person_id', 'cash_account_id', 'status', 'due_from', 'due_to', 'check_no' ) as $k ) {
			if ( $k === 'person_id' || $k === 'cash_account_id' ) {
				if ( $args[ $k ] <= 0 ) {
					unset( $args[ $k ] );
				}
			} elseif ( empty( $args[ $k ] ) ) {
				unset( $args[ $k ] );
			}
		}
		$result = PuzzlingCRM_Accounting_Check::get_list( $args );
		wp_send_json_success( $result );
	}

	public function check_get() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$check = PuzzlingCRM_Accounting_Check::get( $id );
		if ( ! $check ) {
			wp_send_json_error( array( 'message' => __( 'چک یافت نشد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'check' => $check ) );
	}

	public function check_save() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$data = array(
			'type'               => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'receivable',
			'check_no'           => isset( $_POST['check_no'] ) ? sanitize_text_field( wp_unslash( $_POST['check_no'] ) ) : '',
			'check_date'         => isset( $_POST['check_date'] ) ? sanitize_text_field( wp_unslash( $_POST['check_date'] ) ) : null,
			'amount'             => isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0,
			'cash_account_id'    => isset( $_POST['cash_account_id'] ) ? (int) $_POST['cash_account_id'] : 0,
			'person_id'          => isset( $_POST['person_id'] ) ? (int) $_POST['person_id'] : 0,
			'due_date'           => isset( $_POST['due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['due_date'] ) ) : '',
			'description'        => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : null,
			'receipt_voucher_id' => isset( $_POST['receipt_voucher_id'] ) ? (int) $_POST['receipt_voucher_id'] : null,
		);
		if ( $id > 0 ) {
			$ok = PuzzlingCRM_Accounting_Check::update( $id, $data );
			if ( ! $ok ) {
				wp_send_json_error( array( 'message' => __( 'خطا در ویرایش چک.', 'puzzlingcrm' ) ) );
			}
			wp_send_json_success( array( 'updated' => true, 'id' => $id ) );
		}
		$data['status'] = 'in_safe';
		$new_id = PuzzlingCRM_Accounting_Check::create( $data );
		if ( $new_id === false ) {
			wp_send_json_error( array( 'message' => __( 'خطا در ثبت چک (شماره، بانک، طرف حساب و سررسید الزامی است).', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success( array( 'id' => $new_id ) );
	}

	public function check_delete() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$ok = PuzzlingCRM_Accounting_Check::delete( $id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'امکان حذف چک وجود ندارد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}

	/**
	 * Change check status: collected (وصول), returned (برگشتی), spent (خرج‌شده).
	 */
	public function check_set_status() {
		$this->check_access();
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$valid = array( 'in_safe', 'collected', 'returned', 'spent' );
		if ( ! in_array( $status, $valid, true ) ) {
			wp_send_json_error( array( 'message' => __( 'وضعیت نامعتبر است.', 'puzzlingcrm' ) ) );
		}
		$ok = PuzzlingCRM_Accounting_Check::set_status( $id, $status );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'تغییر وضعیت انجام نشد.', 'puzzlingcrm' ) ) );
		}
		wp_send_json_success();
	}
}
