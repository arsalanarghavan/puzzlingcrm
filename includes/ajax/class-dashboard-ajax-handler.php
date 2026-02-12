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
		$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : date( 'Y-m-01' );
		$date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : date( 'Y-m-d' );

		$data = [
			'tab'       => $tab,
			'date_from' => $date_from,
			'date_to'   => $date_to,
		];

		$total_projects  = (int) wp_count_posts( 'project' )->publish;
		$total_tasks     = (int) wp_count_posts( 'task' )->publish;
		$total_tickets   = (int) wp_count_posts( 'ticket' )->publish;
		$total_contracts = (int) wp_count_posts( 'contract' )->publish;
		$user_counts     = count_users();
		$total_customers  = $user_counts['avail_roles']['customer'] ?? 0;
		$total_leads     = (int) wp_count_posts( 'pzl_lead' )->publish;

		if ( $tab === 'overview' ) {
			$done_term = get_term_by( 'slug', 'done', 'task_status' );
			$completed_tasks = $done_term ? $done_term->count : 0;
			$project_statuses = get_terms( [ 'taxonomy' => 'project_status', 'hide_empty' => false ] );
			$project_by_status = [];
			if ( $project_statuses && ! is_wp_error( $project_statuses ) ) {
				foreach ( $project_statuses as $t ) {
					$project_by_status[] = [ 'name' => $t->name, 'count' => $t->count ];
				}
			}
			$task_statuses = get_terms( [ 'taxonomy' => 'task_status', 'hide_empty' => false ] );
			$task_by_status = [];
			if ( $task_statuses && ! is_wp_error( $task_statuses ) ) {
				foreach ( $task_statuses as $t ) {
					$task_by_status[] = [ 'name' => $t->name, 'count' => $t->count ];
				}
			}
			$data['stats'] = [
				'total_projects'   => $total_projects,
				'total_tasks'      => $total_tasks,
				'completed_tasks'  => $completed_tasks,
				'total_tickets'    => $total_tickets,
				'total_leads'      => $total_leads,
				'total_customers'  => $total_customers,
				'total_contracts'  => $total_contracts,
				'project_by_status' => $project_by_status,
				'task_by_status'   => $task_by_status,
			];
		} elseif ( $tab === 'finance' ) {
			$data['stats'] = [
				'total_contracts' => $total_contracts,
				'total_revenue'   => $total_contracts * 50000000,
				'currency'       => 'تومان',
			];
		} elseif ( $tab === 'tasks' ) {
			$done_term = get_term_by( 'slug', 'done', 'task_status' );
			$data['stats'] = [
				'total_tasks'     => $total_tasks,
				'completed_tasks' => $done_term ? $done_term->count : 0,
			];
		} elseif ( $tab === 'tickets' ) {
			$data['stats'] = [
				'total_tickets' => $total_tickets,
			];
		} else {
			$data['stats'] = [
				'total_projects' => $total_projects,
				'total_tasks'    => $total_tasks,
			];
		}

		wp_send_json_success( $data );
	}
}
