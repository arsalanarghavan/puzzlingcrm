<?php
/**
 * PuzzlingCRM Dashboard AJAX Handler (stats for SPA dashboard)
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PuzzlingCRM_Dashboard_Ajax_Handler {

	public function __construct() {
		add_action( 'wp_ajax_puzzlingcrm_get_dashboard_stats', [ $this, 'get_dashboard_stats' ] );
		add_action( 'wp_ajax_puzzlingcrm_get_dashboard_full', [ $this, 'get_dashboard_full' ] );
		add_action( 'wp_ajax_puzzlingcrm_get_team_member_stats', [ $this, 'get_team_member_stats' ] );
		add_action( 'wp_ajax_puzzlingcrm_get_client_stats', [ $this, 'get_client_stats' ] );
		add_action( 'wp_ajax_puzzlingcrm_get_logs', [ $this, 'get_logs' ] );
		add_action( 'wp_ajax_puzzlingcrm_get_reports', [ $this, 'get_reports' ] );
		// System & user logs (DB tables)
		add_action( 'wp_ajax_puzzlingcrm_get_system_logs', [ $this, 'get_system_logs' ] );
		add_action( 'wp_ajax_puzzlingcrm_get_user_logs', [ $this, 'get_user_logs' ] );
		add_action( 'wp_ajax_puzzlingcrm_log_console', [ $this, 'log_console' ] );
		add_action( 'wp_ajax_puzzlingcrm_log_user_action', [ $this, 'log_user_action' ] );
		add_action( 'wp_ajax_puzzlingcrm_delete_system_logs', [ $this, 'delete_system_logs' ] );
		add_action( 'wp_ajax_puzzlingcrm_export_reports_csv', [ $this, 'export_reports_csv' ] );
		// Visitor statistics: track (nopriv + logged-in), get stats (admin only)
		add_action( 'wp_ajax_puzzlingcrm_track_visit', [ $this, 'track_visit' ] );
		add_action( 'wp_ajax_nopriv_puzzlingcrm_track_visit', [ $this, 'track_visit' ] );
		add_action( 'wp_ajax_puzzlingcrm_get_visitor_stats', [ $this, 'get_visitor_stats' ] );
	}

	/**
	 * Get logs (events or system) for React dashboard.
	 */
	public function get_logs() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}

		$log_type = isset( $_POST['log_tab'] ) ? sanitize_key( $_POST['log_tab'] ) : 'events';
		$paged = isset( $_POST['paged'] ) ? max( 1, intval( $_POST['paged'] ) ) : 1;
		$meta_value = ( $log_type === 'system' ) ? 'system_error' : 'log';

		$query = new WP_Query( [
			'post_type'   => 'puzzling_log',
			'posts_per_page' => 20,
			'paged'       => $paged,
			'meta_key'    => '_log_type',
			'meta_value'  => $meta_value,
			'orderby'     => 'date',
			'order'       => 'DESC',
		] );

		$items = [];
		foreach ( $query->posts as $p ) {
			$items[] = [
				'id'      => $p->ID,
				'title'   => $p->post_title,
				'content' => $p->post_content,
				'author'  => get_the_author_meta( 'display_name', $p->post_author ),
				'date'    => get_the_date( 'Y/m/d H:i', $p ),
			];
		}

		wp_send_json_success( [
			'logs'         => $items,
			'total'        => $query->found_posts,
			'total_pages'  => $query->max_num_pages,
			'current_page' => $paged,
		] );
	}

	/**
	 * Return dashboard stats for system manager (for SPA dashboard widgets).
	 */
	public function get_dashboard_stats() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'لطفاً وارد شوید.', 'puzzlingcrm' ) ] );
		}

		$user = wp_get_current_user();
		$roles = (array) $user->roles;
		$is_manager = in_array( 'administrator', $roles, true ) || in_array( 'system_manager', $roles, true );

		if ( ! $is_manager ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}

		if ( ! class_exists( 'PuzzlingCRM_Dashboard_Stats' ) ) {
			require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-dashboard-stats.php';
		}

		$stats = PuzzlingCRM_Dashboard_Stats::get_system_manager_stats();
		wp_send_json_success( $stats );
	}

	/**
	 * Return full dashboard data for system manager (stats, team, running projects, etc.).
	 */
	public function get_dashboard_full() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'لطفاً وارد شوید.', 'puzzlingcrm' ) ] );
		}

		$user  = wp_get_current_user();
		$roles = (array) $user->roles;
		$is_manager = in_array( 'administrator', $roles, true ) || in_array( 'system_manager', $roles, true );

		if ( ! $is_manager ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}

		if ( ! class_exists( 'PuzzlingCRM_Dashboard_Stats' ) ) {
			require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-dashboard-stats.php';
		}

		$data = PuzzlingCRM_Dashboard_Stats::get_system_manager_dashboard_full();
		wp_send_json_success( $data );
	}

	/**
	 * Return dashboard stats for team member.
	 */
	public function get_team_member_stats() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'لطفاً وارد شوید.', 'puzzlingcrm' ) ] );
		}

		$user   = wp_get_current_user();
		$roles  = (array) $user->roles;
		$is_tm  = in_array( 'team_member', $roles, true );

		if ( ! $is_tm ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}

		if ( ! class_exists( 'PuzzlingCRM_Dashboard_Stats' ) ) {
			require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-dashboard-stats.php';
		}

		$stats = PuzzlingCRM_Dashboard_Stats::get_team_member_stats();
		wp_send_json_success( $stats );
	}

	/**
	 * Return dashboard stats for client/customer.
	 */
	public function get_client_stats() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'لطفاً وارد شوید.', 'puzzlingcrm' ) ] );
		}

		$user   = wp_get_current_user();
		$roles  = (array) $user->roles;
		$is_client = in_array( 'customer', $roles, true ) || in_array( 'client', $roles, true );

		if ( ! $is_client ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}

		if ( ! class_exists( 'PuzzlingCRM_Dashboard_Stats' ) ) {
			require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-dashboard-stats.php';
		}

		$stats = PuzzlingCRM_Dashboard_Stats::get_client_stats();
		wp_send_json_success( $stats );
	}

	/**
	 * Get reports data for React (tab: overview, finance, tasks, tickets, agile).
	 */
	public function get_reports() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}

		$tab = isset( $_POST['tab'] ) ? sanitize_key( $_POST['tab'] ) : 'overview';
		$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : date( 'Y-m-01' );
		$date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : date( 'Y-m-d' );

		$stats = PuzzlingCRM_Reports_Dashboard::get_overall_statistics( $date_from, $date_to );
		$data = [
			'tab'       => $tab,
			'date_from' => $date_from,
			'date_to'   => $date_to,
			'stats'     => $stats,
		];

		if ( $tab === 'overview' ) {
			$data['daily']   = PuzzlingCRM_Reports_Dashboard::get_daily_statistics( $date_from, $date_to );
			$data['monthly'] = PuzzlingCRM_Reports_Dashboard::get_monthly_performance( 6 );
			$data['status_distribution'] = PuzzlingCRM_Reports_Dashboard::get_status_distribution( $date_from, $date_to );
			$data['growth'] = PuzzlingCRM_Reports_Dashboard::get_growth_statistics( $date_from, $date_to );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Export reports as CSV (contracts or tasks).
	 */
	public function export_reports_csv() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}
		$type = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : 'contracts';
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : date( 'Y-m-01' );
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : date( 'Y-m-d' );
		if ( ! in_array( $type, array( 'contracts', 'tasks' ), true ) ) {
			$type = 'contracts';
		}
		PuzzlingCRM_Reports_Dashboard::export_to_csv( array( 'type' => $type, 'date_from' => $date_from, 'date_to' => $date_to ) );
	}

	/**
	 * Get system logs (from puzzlingcrm_system_logs table).
	 */
	public function get_system_logs() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}
		$args = [
			'date_from' => isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '',
			'date_to'   => isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '',
			'log_type'  => isset( $_POST['log_type'] ) ? sanitize_text_field( wp_unslash( $_POST['log_type'] ) ) : '',
			'severity'  => isset( $_POST['severity'] ) ? sanitize_text_field( wp_unslash( $_POST['severity'] ) ) : '',
			'user_id'   => isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : '',
			'search'    => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'limit'     => isset( $_POST['limit'] ) ? min( 100, max( 1, (int) $_POST['limit'] ) ) : 50,
			'offset'    => isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0,
		];
		$logs  = PuzzlingCRM_Logger::get_system_logs( $args );
		$total = PuzzlingCRM_Logger::get_system_logs_count( $args );
		wp_send_json_success( [ 'logs' => $logs, 'total' => $total ] );
	}

	/**
	 * Get user logs (from puzzlingcrm_user_logs table).
	 */
	public function get_user_logs() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}
		$args = [
			'date_from'   => isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '',
			'date_to'     => isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '',
			'user_id'     => isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : '',
			'action_type' => isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '',
			'target_type' => isset( $_POST['target_type'] ) ? sanitize_text_field( wp_unslash( $_POST['target_type'] ) ) : '',
			'search'      => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'limit'       => isset( $_POST['limit'] ) ? min( 100, max( 1, (int) $_POST['limit'] ) ) : 50,
			'offset'      => isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0,
		];
		$logs  = PuzzlingCRM_Logger::get_user_logs( $args );
		$total = PuzzlingCRM_Logger::get_user_logs_count( $args );
		wp_send_json_success( [ 'logs' => $logs, 'total' => $total ] );
	}

	/**
	 * Log console message from frontend.
	 */
	public function log_console() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		$log_type = isset( $_POST['log_type'] ) ? sanitize_text_field( wp_unslash( $_POST['log_type'] ) ) : 'console';
		$severity = isset( $_POST['severity'] ) ? sanitize_text_field( wp_unslash( $_POST['severity'] ) ) : 'info';
		$message  = isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '';
		$context  = [];
		if ( ! empty( $_POST['context'] ) && is_string( $_POST['context'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['context'] ), true );
			if ( is_array( $decoded ) ) {
				$context = $decoded;
			}
		}
		$id = PuzzlingCRM_Logger::log_console( $message, $log_type, $severity, $context );
		wp_send_json_success( [ 'id' => $id ] );
	}

	/**
	 * Log user action from frontend (button_click, form_submit, ajax_call, page_view).
	 */
	public function log_user_action() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! is_user_logged_in() ) {
			wp_send_json_success( [ 'id' => null ] );
			return;
		}
		$action_type        = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : 'button_click';
		$action_description = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
		$target_type        = isset( $_POST['target_type'] ) ? sanitize_text_field( wp_unslash( $_POST['target_type'] ) ) : null;
		$target_id          = isset( $_POST['target_id'] ) ? absint( $_POST['target_id'] ) : null;
		$metadata           = [];
		if ( ! empty( $_POST['metadata'] ) && is_string( $_POST['metadata'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['metadata'] ), true );
			if ( is_array( $decoded ) ) {
				$metadata = $decoded;
			}
		}
		$id = PuzzlingCRM_Logger::log_user_action( $action_type, $action_description, $target_type, $target_id, $metadata );
		wp_send_json_success( [ 'id' => $id ] );
	}

	/**
	 * Delete all system logs (manage_options only).
	 */
	public function delete_system_logs() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}
		$deleted = PuzzlingCRM_Logger::delete_all_system_logs();
		wp_send_json_success( [ 'deleted' => $deleted, 'message' => __( 'همه لاگ‌های سیستم حذف شد.', 'puzzlingcrm' ) ] );
	}

	/**
	 * Track a visit (frontend or server-side). No auth required; rate-limited by IP inside class.
	 */
	public function track_visit() {
		$page_url   = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : null;
		$page_title = isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : null;
		$referrer   = isset( $_POST['referrer'] ) ? esc_url_raw( wp_unslash( $_POST['referrer'] ) ) : null;
		$entity_id  = isset( $_POST['entity_id'] ) ? absint( $_POST['entity_id'] ) : null;
		$ok = PuzzlingCRM_Visitor_Statistics::track_visit( $page_url, $page_title, $referrer, $entity_id );
		wp_send_json_success( [ 'tracked' => $ok ] );
	}

	/**
	 * Get visitor statistics for dashboard (manage_options only).
	 */
	public function get_visitor_stats() {
		if ( ! check_ajax_referer( 'puzzlingcrm-ajax-nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'درخواست نامعتبر.', 'puzzlingcrm' ) ] );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'دسترسی غیرمجاز.', 'puzzlingcrm' ) ] );
		}
		$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : date( 'Y-m-d', strtotime( '-30 days' ) );
		$date_to   = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : date( 'Y-m-d' );
		$data = [
			'overall'    => PuzzlingCRM_Visitor_Statistics::get_overall_stats( $date_from, $date_to ),
			'daily'      => PuzzlingCRM_Visitor_Statistics::get_daily_visits( $date_from, $date_to ),
			'top_pages'  => PuzzlingCRM_Visitor_Statistics::get_top_pages( 10, $date_from, $date_to ),
			'browsers'   => PuzzlingCRM_Visitor_Statistics::get_browser_stats( $date_from, $date_to ),
			'os'         => PuzzlingCRM_Visitor_Statistics::get_os_stats( $date_from, $date_to ),
			'devices'    => PuzzlingCRM_Visitor_Statistics::get_device_stats( $date_from, $date_to ),
			'referrers'  => PuzzlingCRM_Visitor_Statistics::get_referrer_stats( 10, $date_from, $date_to ),
			'recent'     => PuzzlingCRM_Visitor_Statistics::get_recent_visitors( 50, $date_from, $date_to ),
			'online'     => PuzzlingCRM_Visitor_Statistics::get_online_visitors(),
		];
		wp_send_json_success( $data );
	}
}
