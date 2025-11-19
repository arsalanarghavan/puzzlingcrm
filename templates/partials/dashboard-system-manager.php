<?php
/**
 * System Manager Dashboard - Complete Implementation
 * Based on index9.html structure with full features
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;

// Load helper classes
if (!class_exists('PuzzlingCRM_Date_Formatter')) {
	if (defined('PUZZLINGCRM_PLUGIN_DIR')) {
		$date_formatter_path = PUZZLINGCRM_PLUGIN_DIR . 'includes/helpers/class-date-formatter.php';
	} else {
		$date_formatter_path = plugin_dir_path(__FILE__) . '../../../includes/helpers/class-date-formatter.php';
	}
	if (file_exists($date_formatter_path)) {
		require_once $date_formatter_path;
	}
}
if (!class_exists('PuzzlingCRM_Number_Formatter')) {
	if (defined('PUZZLINGCRM_PLUGIN_DIR')) {
		$number_formatter_path = PUZZLINGCRM_PLUGIN_DIR . 'includes/helpers/class-number-formatter.php';
	} else {
		$number_formatter_path = plugin_dir_path(__FILE__) . '../../../includes/helpers/class-number-formatter.php';
	}
	if (file_exists($number_formatter_path)) {
		require_once $number_formatter_path;
	}
}

// Helper functions for safe formatting
if (!function_exists('pzl_format_number')) {
	function pzl_format_number($number, $decimals = 0) {
		// Get language preference (user meta > cookie > locale)
		$is_persian = false;
		
		// Priority 1: User meta
		if (is_user_logged_in()) {
			$user_lang = get_user_meta(get_current_user_id(), 'pzl_language', true);
			if ($user_lang === 'fa') {
				$is_persian = true;
			} elseif ($user_lang === 'en') {
				$is_persian = false;
			}
		}
		
		// Priority 2: Cookie (if user meta not set)
		if ($is_persian === false && !isset($user_lang)) {
			$cookie_lang = isset($_COOKIE['pzl_language']) ? sanitize_text_field($_COOKIE['pzl_language']) : '';
			if ($cookie_lang === 'fa') {
				$is_persian = true;
			} elseif ($cookie_lang === 'en') {
				$is_persian = false;
			}
		}
		
		// Priority 3: WordPress locale (only if explicitly Persian)
		if ($is_persian === false && !isset($user_lang) && empty($cookie_lang)) {
			$locale = get_locale();
			$is_persian = (strpos($locale, 'fa') !== false || strpos($locale, 'fa_IR') !== false);
		}
		
		// If English, return English digits (no conversion)
		if (!$is_persian) {
			return number_format($number, $decimals);
		}
		
		// If Persian, use class formatter or convert manually
		if (class_exists('PuzzlingCRM_Number_Formatter') && method_exists('PuzzlingCRM_Number_Formatter', 'format_number')) {
			return PuzzlingCRM_Number_Formatter::format_number($number, $decimals);
		}
		
		// Manual conversion to Persian
		$formatted = number_format($number, $decimals);
		$persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
		$english_digits = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
		return str_replace($english_digits, $persian_digits, $formatted);
	}
}

if (!function_exists('pzl_format_percentage')) {
	function pzl_format_percentage($value, $decimals = 1) {
		// Get language preference (user meta > cookie > locale)
		$is_persian = false;
		
		// Priority 1: User meta
		if (is_user_logged_in()) {
			$user_lang = get_user_meta(get_current_user_id(), 'pzl_language', true);
			if ($user_lang === 'fa') {
				$is_persian = true;
			} elseif ($user_lang === 'en') {
				$is_persian = false;
			}
		}
		
		// Priority 2: Cookie (if user meta not set)
		if ($is_persian === false && !isset($user_lang)) {
			$cookie_lang = isset($_COOKIE['pzl_language']) ? sanitize_text_field($_COOKIE['pzl_language']) : '';
			if ($cookie_lang === 'fa') {
				$is_persian = true;
			} elseif ($cookie_lang === 'en') {
				$is_persian = false;
			}
		}
		
		// Priority 3: WordPress locale (only if explicitly Persian)
		if ($is_persian === false && !isset($user_lang) && empty($cookie_lang)) {
			$locale = get_locale();
			$is_persian = (strpos($locale, 'fa') !== false || strpos($locale, 'fa_IR') !== false);
		}
		
		// Format number
		$formatted = number_format($value, $decimals);
		
		// If Persian, convert to Persian digits
		if ($is_persian) {
			if (class_exists('PuzzlingCRM_Number_Formatter') && method_exists('PuzzlingCRM_Number_Formatter', 'format_percentage')) {
				return PuzzlingCRM_Number_Formatter::format_percentage($value, $decimals);
			}
			$persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
			$english_digits = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
			$formatted = str_replace($english_digits, $persian_digits, $formatted);
		}
		
		return $formatted . '%';
	}
}

if (!function_exists('pzl_format_currency')) {
	function pzl_format_currency($amount, $currency = '') {
		// Get language preference (user meta > cookie > locale)
		$is_persian = false;
		
		// Priority 1: User meta
		if (is_user_logged_in()) {
			$user_lang = get_user_meta(get_current_user_id(), 'pzl_language', true);
			if ($user_lang === 'fa') {
				$is_persian = true;
			} elseif ($user_lang === 'en') {
				$is_persian = false;
			}
		}
		
		// Priority 2: Cookie (if user meta not set)
		if ($is_persian === false && !isset($user_lang)) {
			$cookie_lang = isset($_COOKIE['pzl_language']) ? sanitize_text_field($_COOKIE['pzl_language']) : '';
			if ($cookie_lang === 'fa') {
				$is_persian = true;
			} elseif ($cookie_lang === 'en') {
				$is_persian = false;
			}
		}
		
		// Priority 3: WordPress locale (only if explicitly Persian)
		if ($is_persian === false && !isset($user_lang) && empty($cookie_lang)) {
			$locale = get_locale();
			$is_persian = (strpos($locale, 'fa') !== false || strpos($locale, 'fa_IR') !== false);
		}
		
		// Format number
		$formatted = number_format($amount);
		
		// If Persian, convert to Persian digits
		if ($is_persian) {
			if (class_exists('PuzzlingCRM_Number_Formatter') && method_exists('PuzzlingCRM_Number_Formatter', 'format_currency')) {
				return PuzzlingCRM_Number_Formatter::format_currency($amount, $currency);
			}
			$persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
			$english_digits = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
			$formatted = str_replace($english_digits, $persian_digits, $formatted);
			$default_currency = empty($currency) ? 'تومان' : $currency;
			return $formatted . ' ' . $default_currency;
		} else {
			// English: return English digits
			$default_currency = empty($currency) ? '$' : $currency;
			return $default_currency . $formatted;
		}
	}
}

if (!function_exists('pzl_format_date')) {
	function pzl_format_date($date = '', $format = 'Y/m/d') {
		if (class_exists('PuzzlingCRM_Date_Formatter') && method_exists('PuzzlingCRM_Date_Formatter', 'format_date')) {
			return PuzzlingCRM_Date_Formatter::format_date($date, $format);
		}
		if (empty($date)) {
			return date_i18n($format);
		}
		if (is_numeric($date)) {
			return date_i18n($format, $date);
		}
		return date_i18n($format, strtotime($date));
	}
}

if (!function_exists('pzl_human_time_diff')) {
	function pzl_human_time_diff($from, $to = '') {
		if (class_exists('PuzzlingCRM_Date_Formatter') && method_exists('PuzzlingCRM_Date_Formatter', 'human_time_diff')) {
			return PuzzlingCRM_Date_Formatter::human_time_diff($from, $to);
		}
		if (empty($to)) {
			$to = current_time('timestamp');
		}
		if (!is_numeric($from)) {
			$from = strtotime($from);
		}
		if (!is_numeric($to)) {
			$to = strtotime($to);
		}
		return human_time_diff($from, $to);
	}
}

// Get current user
$current_user = wp_get_current_user();
$user_display_name = $current_user->display_name;

// --- Fetch Dashboard Stats (Cached for 15 minutes) ---
if (false === ($stats = get_transient('puzzling_system_manager_stats_v4'))) {
	
	// Project Stats
	$all_projects = get_posts(['post_type' => 'project', 'posts_per_page' => -1, 'post_status' => 'publish']);
	$total_projects = count($all_projects);
	
	$new_projects_this_month = 0;
	$completed_projects = 0;
	$in_progress_projects = 0;
	$pending_projects = 0;
	
	$current_month_start = date('Y-m-01');
	
	foreach ($all_projects as $project) {
		// Check if new this month
		if (strtotime($project->post_date) >= strtotime($current_month_start)) {
			$new_projects_this_month++;
		}
		
		// Get project status from taxonomy
		$status_terms = wp_get_post_terms($project->ID, 'project_status');
		$status_slug = !empty($status_terms) ? $status_terms[0]->slug : 'pending';
		
		if ($status_slug === 'completed' || $status_slug === 'done') {
			$completed_projects++;
		} elseif ($status_slug === 'in-progress' || $status_slug === 'active') {
			$in_progress_projects++;
		} else {
			$pending_projects++;
		}
	}
	
	// Calculate project growth
	$last_month_projects = count(get_posts([
		'post_type' => 'project',
		'posts_per_page' => -1,
		'date_query' => [
			[
				'after' => date('Y-m-01', strtotime('-1 month')),
				'before' => date('Y-m-t', strtotime('-1 month')),
				'inclusive' => true
			]
		]
	]));
	$new_projects_growth = $last_month_projects > 0 ? (($new_projects_this_month - $last_month_projects) / $last_month_projects) * 100 : 0;
	
	// Task Stats
	$all_tasks = get_posts(['post_type' => 'task', 'posts_per_page' => -1]);
	$total_tasks = count($all_tasks);
	$completed_tasks = 0;
	$pending_tasks = 0;
	$overdue_tasks = 0;
	
	foreach ($all_tasks as $task) {
		$status_terms = wp_get_post_terms($task->ID, 'task_status');
		$status = !empty($status_terms) ? $status_terms[0]->slug : 'todo';
		
		if ($status === 'done') {
			$completed_tasks++;
		} else {
			$pending_tasks++;
			
			$due_date = get_post_meta($task->ID, '_due_date', true);
			if ($due_date && strtotime($due_date) < strtotime('today')) {
				$overdue_tasks++;
			}
		}
	}
	
	// Customer Stats
	$customer_count = count_users()['avail_roles']['customer'] ?? 0;
	$new_customers_this_month = count(get_users([
		'role' => 'customer',
		'date_query' => [
			[
				'after' => date('Y-m-01'),
				'inclusive' => true
			]
		]
	]));
	
	// Ticket Stats
	$all_tickets = get_posts(['post_type' => 'ticket', 'posts_per_page' => -1]);
	$total_tickets = count($all_tickets);
	$open_tickets = 0;
	$resolved_tickets = 0;
	
	foreach ($all_tickets as $ticket) {
		$status = get_post_meta($ticket->ID, '_ticket_status', true) ?: 'open';
		if (in_array($status, ['open', 'pending'])) {
			$open_tickets++;
		} else {
			$resolved_tickets++;
		}
	}
	
	// Financial Stats
	$income_this_month = 0;
	$income_last_month = 0;
	$total_revenue = 0;
	
	$current_month_start = date('Y-m-01');
	$last_month_start = date('Y-m-01', strtotime('-1 month'));
	$last_month_end = date('Y-m-t', strtotime('-1 month'));
	
	$contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);
	foreach ($contracts as $contract) {
		$amount = (float) get_post_meta($contract->ID, '_total_amount', true);
		$total_revenue += $amount;
		
		$installments = get_post_meta($contract->ID, '_installments', true);
		if (is_array($installments)) {
			foreach ($installments as $inst) {
				if (($inst['status'] ?? 'pending') === 'paid' && isset($inst['due_date'])) {
					$due_date = strtotime($inst['due_date']);
					if ($due_date >= strtotime($current_month_start)) {
						$income_this_month += (int)($inst['amount'] ?? 0);
					}
					if ($due_date >= strtotime($last_month_start) && $due_date <= strtotime($last_month_end)) {
						$income_last_month += (int)($inst['amount'] ?? 0);
					}
				}
			}
		}
	}
	
	// Calculate growth
	$revenue_growth = $income_last_month > 0 ? (($income_this_month - $income_last_month) / $income_last_month) * 100 : 0;
	$completion_rate = $total_tasks > 0 ? ($completed_tasks / $total_tasks) * 100 : 0;
	
	$stats = [
		'total_projects' => $total_projects,
		'new_projects_this_month' => $new_projects_this_month,
		'new_projects_growth' => $new_projects_growth,
		'completed_projects' => $completed_projects,
		'in_progress_projects' => $in_progress_projects,
		'pending_projects' => $pending_projects,
		'total_tasks' => $total_tasks,
		'completed_tasks' => $completed_tasks,
		'pending_tasks' => $pending_tasks,
		'overdue_tasks' => $overdue_tasks,
		'completion_rate' => $completion_rate,
		'customer_count' => $customer_count,
		'new_customers_this_month' => $new_customers_this_month,
		'total_tickets' => $total_tickets,
		'open_tickets' => $open_tickets,
		'resolved_tickets' => $resolved_tickets,
		'income_this_month' => $income_this_month,
		'total_revenue' => $total_revenue,
		'revenue_growth' => $revenue_growth,
	];
	
	set_transient('puzzling_system_manager_stats_v4', $stats, 15 * MINUTE_IN_SECONDS);
}

// Team members with detailed stats
$team_members = get_users(['role__in' => ['team_member', 'system_manager', 'administrator']]);
$team_count = count($team_members);

// Get team member stats
$team_member_stats = [];
foreach ($team_members as $member) {
	$member_tasks = get_posts([
		'post_type' => 'task',
		'posts_per_page' => -1,
		'meta_query' => [
			['key' => '_assigned_to', 'value' => $member->ID, 'compare' => '=']
		]
	]);
	
	$member_completed = 0;
	$member_total = count($member_tasks);
	
	foreach ($member_tasks as $task) {
		$status_terms = wp_get_post_terms($task->ID, 'task_status');
		if (!empty($status_terms) && $status_terms[0]->slug === 'done') {
			$member_completed++;
		}
	}
	
	// Check if user is online (simple check - last activity within 15 minutes)
	$last_activity = get_user_meta($member->ID, 'last_activity', true);
	$is_online = $last_activity && (time() - $last_activity) < 900;
	
	$team_member_stats[] = [
		'user' => $member,
		'total_tasks' => $member_total,
		'completed_tasks' => $member_completed,
		'progress' => $member_total > 0 ? ($member_completed / $member_total) * 100 : 0,
		'is_online' => $is_online,
	];
}

// Sort by completed tasks
usort($team_member_stats, function($a, $b) {
	return $b['completed_tasks'] - $a['completed_tasks'];
});

// Running projects (active projects with progress)
$running_projects = get_posts([
	'post_type' => 'project',
	'posts_per_page' => 5,
	'post_status' => 'publish',
	'tax_query' => [
		[
			'taxonomy' => 'project_status',
			'field' => 'slug',
			'terms' => ['in-progress', 'active'],
			'operator' => 'IN'
		]
	],
	'orderby' => 'modified',
	'order' => 'DESC'
]);

$running_projects_data = [];
foreach ($running_projects as $project) {
	$project_tasks = get_posts([
		'post_type' => 'task',
		'posts_per_page' => -1,
		'meta_query' => [
			['key' => '_project_id', 'value' => $project->ID, 'compare' => '=']
		]
	]);
	
	$done_tasks = 0;
	foreach ($project_tasks as $task) {
		$status_terms = wp_get_post_terms($task->ID, 'task_status');
		if (!empty($status_terms) && $status_terms[0]->slug === 'done') {
			$done_tasks++;
		}
	}
	
	$progress = count($project_tasks) > 0 ? ($done_tasks / count($project_tasks)) * 100 : 0;
	
	// Get assigned team members
	$assigned_members = get_post_meta($project->ID, '_assigned_members', true);
	if (!is_array($assigned_members)) {
		$assigned_members = [];
	}
	
	$running_projects_data[] = [
		'project' => $project,
		'progress' => $progress,
		'done_tasks' => $done_tasks,
		'total_tasks' => count($project_tasks),
		'assigned_members' => $assigned_members,
		'last_modified' => $project->post_modified,
	];
}

// Daily tasks (tasks due today or recently completed)
$today = date('Y-m-d');
$daily_tasks = get_posts([
	'post_type' => 'task',
	'posts_per_page' => 10,
	'meta_query' => [
		'relation' => 'OR',
		['key' => '_due_date', 'value' => $today, 'compare' => '='],
		['key' => '_due_date', 'value' => date('Y-m-d', strtotime('+1 day')), 'compare' => '='],
	],
	'orderby' => 'meta_value',
	'meta_key' => '_due_date',
	'order' => 'ASC'
]);

// Projects summary for table
$projects_summary = get_posts([
	'post_type' => 'project',
	'posts_per_page' => 10,
	'post_status' => 'publish',
	'orderby' => 'date',
	'order' => 'DESC'
]);

$projects_table_data = [];
foreach ($projects_summary as $project) {
	$project_tasks = get_posts([
		'post_type' => 'task',
		'posts_per_page' => -1,
		'meta_query' => [
			['key' => '_project_id', 'value' => $project->ID, 'compare' => '=']
		]
	]);
	
	$done_tasks = 0;
	foreach ($project_tasks as $task) {
		$status_terms = wp_get_post_terms($task->ID, 'task_status');
		if (!empty($status_terms) && $status_terms[0]->slug === 'done') {
			$done_tasks++;
		}
	}
	
	$progress = count($project_tasks) > 0 ? ($done_tasks / count($project_tasks)) * 100 : 0;
	
	$status_terms = wp_get_post_terms($project->ID, 'project_status');
	$status_slug = !empty($status_terms) ? $status_terms[0]->slug : 'pending';
	$status_name = !empty($status_terms) ? $status_terms[0]->name : __('Pending', 'puzzlingcrm');
	
	$assigned_members = get_post_meta($project->ID, '_assigned_members', true);
	if (!is_array($assigned_members)) {
		$assigned_members = [];
	}
	
	$due_date = get_post_meta($project->ID, '_due_date', true);
	
	$projects_table_data[] = [
		'project' => $project,
		'progress' => $progress,
		'done_tasks' => $done_tasks,
		'total_tasks' => count($project_tasks),
		'status_slug' => $status_slug,
		'status_name' => $status_name,
		'assigned_members' => $assigned_members,
		'due_date' => $due_date,
	];
}

// Monthly goals data
$current_month_projects = count(get_posts([
	'post_type' => 'project',
	'posts_per_page' => -1,
	'date_query' => [
		[
			'after' => date('Y-m-01'),
			'inclusive' => true
		]
	]
]));

$monthly_goals = [
	'new_projects' => $current_month_projects,
	'completed' => $stats['completed_projects'],
	'pending' => $stats['pending_projects'],
];

// Revenue data for last 6 months
$revenue_data = [];
$month_labels = [];
if (isset($contracts) && is_array($contracts)) {
	for ($i = 5; $i >= 0; $i--) {
		$month_start = date('Y-m-01', strtotime("-$i months"));
		$month_end = date('Y-m-t', strtotime("-$i months"));
		
		$month_revenue = 0;
		foreach ($contracts as $contract) {
			if (isset($contract->ID)) {
				$installments = get_post_meta($contract->ID, '_installments', true);
				if (is_array($installments)) {
					foreach ($installments as $inst) {
						if (($inst['status'] ?? 'pending') === 'paid' && isset($inst['due_date'])) {
							$due_date = strtotime($inst['due_date']);
							if ($due_date >= strtotime($month_start) && $due_date <= strtotime($month_end)) {
								$month_revenue += (int)($inst['amount'] ?? 0);
							}
						}
					}
				}
			}
		}
		
		$revenue_data[] = $month_revenue;
		
		// Month labels based on language
		// Get language preference
		$is_persian_month = false;
		if (is_user_logged_in()) {
			$user_lang = get_user_meta(get_current_user_id(), 'pzl_language', true);
			if ($user_lang === 'fa') {
				$is_persian_month = true;
			} elseif ($user_lang === 'en') {
				$is_persian_month = false;
			}
		}
		if ($is_persian_month === false && !isset($user_lang)) {
			$cookie_lang = isset($_COOKIE['pzl_language']) ? sanitize_text_field($_COOKIE['pzl_language']) : '';
			if ($cookie_lang === 'fa') {
				$is_persian_month = true;
			} elseif ($cookie_lang === 'en') {
				$is_persian_month = false;
			}
		}
		if ($is_persian_month === false && !isset($user_lang) && empty($cookie_lang)) {
			$locale = get_locale();
			$is_persian_month = (strpos($locale, 'fa') !== false || strpos($locale, 'fa_IR') !== false);
		}
		
		if ($is_persian_month) {
			$month_labels[] = pzl_format_date(strtotime($month_start), 'F');
		} else {
			// English: use Gregorian month names
			$month_labels[] = date_i18n('F', strtotime($month_start));
		}
	}
} else {
	// Default empty data
	// Get language preference for month labels
	$is_persian_month = false;
	if (is_user_logged_in()) {
		$user_lang = get_user_meta(get_current_user_id(), 'pzl_language', true);
		if ($user_lang === 'fa') {
			$is_persian_month = true;
		} elseif ($user_lang === 'en') {
			$is_persian_month = false;
		}
	}
	if ($is_persian_month === false && !isset($user_lang)) {
		$cookie_lang = isset($_COOKIE['pzl_language']) ? sanitize_text_field($_COOKIE['pzl_language']) : '';
		if ($cookie_lang === 'fa') {
			$is_persian_month = true;
		} elseif ($cookie_lang === 'en') {
			$is_persian_month = false;
		}
	}
	if ($is_persian_month === false && !isset($user_lang) && empty($cookie_lang)) {
		$locale = get_locale();
		$is_persian_month = (strpos($locale, 'fa') !== false || strpos($locale, 'fa_IR') !== false);
	}
	
	for ($i = 5; $i >= 0; $i--) {
		$revenue_data[] = 0;
		if ($is_persian_month) {
			$month_labels[] = pzl_format_date(strtotime("-$i months"), 'F');
		} else {
			$month_labels[] = date_i18n('F', strtotime("-$i months"));
		}
	}
}
?>

<!-- Start::page-header -->
<div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2 mb-4">
	<div>
		<nav>
			<ol class="breadcrumb mb-1">
				<li class="breadcrumb-item">
					<a href="javascript:void(0);">
						<?php esc_html_e('Dashboard', 'puzzlingcrm'); ?>
					</a>
				</li>
				<li class="breadcrumb-item active" aria-current="page">
					<?php esc_html_e('Projects', 'puzzlingcrm'); ?>
				</li>
			</ol>
		</nav>
		<h1 class="page-title fw-medium fs-18 mb-0">
			<?php esc_html_e('Projects', 'puzzlingcrm'); ?>
		</h1>
	</div>
	<div class="btn-list">
		<button class="btn btn-white btn-wave">
			<i class="ri-filter-3-line align-middle me-1 lh-1"></i>
			<?php esc_html_e('Filter', 'puzzlingcrm'); ?>
		</button>
		<button class="btn btn-primary btn-wave me-0">
			<i class="ri-share-forward-line me-1"></i>
			<?php esc_html_e('Share', 'puzzlingcrm'); ?>
		</button>
	</div>
</div>
<!-- End::page-header -->

<!-- Start::row-1 -->
<div class="row">
	<!-- Banner and Team Table -->
	<div class="col-xxl-5 col-xl-12">
		<!-- Banner -->
		<div class="card custom-card main-dashboard-banner project-dashboard-banner overflow-hidden mb-4">
			<div class="card-body p-4">
				<div class="row justify-content-between">
					<div class="col-xxl-8 col-xl-5 col-lg-5 col-md-5 col-sm-5">
						<h4 class="mb-1 fw-medium text-fixed-white">
							<?php esc_html_e('Project Management', 'puzzlingcrm'); ?>
						</h4>
						<p class="mb-3 text-fixed-white op-7">
							<?php esc_html_e('Manage projects easily with our one-click solution and simplify your workflow.', 'puzzlingcrm'); ?>
						</p>
						<a href="<?php echo esc_url(add_query_arg('view', 'projects')); ?>" class="btn btn-sm btn-primary1">
							<?php esc_html_e('Manage Now', 'puzzlingcrm'); ?>
							<i class="ti ti-arrow-narrow-left align-middle"></i>
						</a>
					</div>
					<div class="col-xxl-4 col-xl-7 col-lg-7 col-md-7 col-sm-7 d-sm-block d-none text-end my-auto">
						<img src="<?php echo esc_url(PUZZLINGCRM_PLUGIN_URL . 'assets/images/media/media-85.png'); ?>" alt="" class="img-fluid">
					</div>
				</div>
			</div>
		</div>
		
		<!-- Team Table -->
		<div class="card custom-card overflow-hidden">
			<div class="card-header justify-content-between">
				<div class="card-title">
					<?php esc_html_e('Team', 'puzzlingcrm'); ?>
				</div>
				<a href="<?php echo esc_url(add_query_arg('view', 'staff')); ?>" class="btn btn-sm btn-light">
					<?php esc_html_e('View All', 'puzzlingcrm'); ?>
				</a>
			</div>
			
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="table text-nowrap mb-0">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e('Name', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Tasks', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Status', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Progress', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Actions', 'puzzlingcrm'); ?></th>
							</tr>
						</thead>
						<tbody class="top-selling">
							<?php 
							$displayed_members = array_slice($team_member_stats, 0, 6);
							foreach ($displayed_members as $index => $member_data):
								$member = $member_data['user'];
								$member_total = $member_data['total_tasks'];
								$member_completed = $member_data['completed_tasks'];
								$member_progress = $member_data['progress'];
								$is_online = $member_data['is_online'];
								
								// Get user role display name
								$roles = $member->roles;
								$role_display = '';
								if (in_array('system_manager', $roles) || in_array('administrator', $roles)) {
									$role_display = __('Team Lead', 'puzzlingcrm');
								} elseif (in_array('team_member', $roles)) {
									$role_display = __('Team Member', 'puzzlingcrm');
								}
							?>
							<tr>
								<td>
									<div class="d-flex">
										<span class="avatar avatar-sm avatar-rounded">
											<?php echo get_avatar($member->ID, 32, '', '', ['class' => '']); ?>
										</span>
										<div class="flex-1 ms-2">
											<span class="d-block fw-semibold"><?php echo esc_html($member->display_name); ?></span>
											<a href="javascript:void(0);" class="text-muted fs-12"><?php echo esc_html($role_display); ?></a>
										</div>
									</div>
								</td>
								<td>
									<span class="fw-medium"><?php echo pzl_format_number($member_total); ?></span>
								</td>
								<td>
									<span class="badge <?php echo $is_online ? 'bg-success-transparent' : 'bg-danger-transparent'; ?>">
										<?php echo $is_online ? esc_html__('Online', 'puzzlingcrm') : esc_html__('Offline', 'puzzlingcrm'); ?>
									</span>
								</td>
								<td>
									<span class="">
										<?php echo pzl_format_number($member_completed); ?>/ 
										<span class="text-muted"><?php echo pzl_format_number($member_total); ?></span>
									</span>
								</td>
								<td>
									<div class="btn-list">
										<a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php esc_attr_e('Assign', 'puzzlingcrm'); ?>" class="btn btn-icon btn-sm rounded-pill mb-0 btn-primary-light">
											<i class="ti ti-user-plus align-middle"></i>
										</a>
										<a href="mailto:<?php echo esc_attr($member->user_email); ?>" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php esc_attr_e('Email', 'puzzlingcrm'); ?>" class="btn btn-icon btn-sm rounded-pill mb-0 btn-info-light">
											<i class="ti ti-at align-middle"></i>
										</a>
										<a href="<?php echo esc_url(add_query_arg(['view' => 'staff', 'user_id' => $member->ID])); ?>" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php esc_attr_e('View', 'puzzlingcrm'); ?>" class="btn btn-icon btn-sm rounded-pill mb-0 btn-primary2-light">
											<i class="ti ti-eye align-middle"></i>
										</a>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	
	<!-- KPI Cards and Project Statistics -->
	<div class="col-xxl-7 col-xl-12">
		<!-- KPI Cards Row -->
		<div class="row mb-4">
			<!-- New Projects Card -->
			<div class="col-xxl-3 col-md-6 col-sm-6 mb-3 mb-md-0">
				<div class="card custom-card overflow-hidden">
					<div class="card-body">
						<div class="mb-3 d-flex align-items-start justify-content-between">
							<span class="avatar avatar-sm bg-primary svg-white">
								<i class="ri-pages-line fs-16"></i>
							</span>
							<span class="badge <?php echo $stats['new_projects_growth'] >= 0 ? 'bg-success-transparent' : 'bg-danger-transparent'; ?>">
								<?php echo $stats['new_projects_growth'] >= 0 ? '+' : ''; ?>
								<?php echo pzl_format_percentage(abs($stats['new_projects_growth'])); ?>
							</span>
						</div>
						<div class="d-flex align-items-end justify-content-between flex-wrap">
							<div class="flex-shrink-0">
								<div class="text-muted mb-1"><?php esc_html_e('New Projects', 'puzzlingcrm'); ?></div>
								<h4 class="mb-0 fs-20 fw-medium"><?php echo pzl_format_number($stats['new_projects_this_month']); ?></h4>
							</div>
							<div id="Projects-2" class="flex-shrink-0 text-end ms-auto"></div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Completed Projects Card -->
			<div class="col-xxl-3 col-md-6 col-sm-6 mb-3 mb-md-0">
				<div class="card custom-card overflow-hidden">
					<div class="card-body">
						<div class="mb-3 d-flex align-items-start justify-content-between">
							<span class="avatar avatar-sm bg-primary1 svg-white">
								<i class="ri-check-double-line fs-16"></i>
							</span>
							<span class="badge bg-success-transparent">+7.20%</span>
						</div>
						<div class="d-flex align-items-end justify-content-between flex-wrap">
							<div class="flex-shrink-0">
								<div class="text-muted mb-1"><?php esc_html_e('Completed', 'puzzlingcrm'); ?></div>
								<h4 class="mb-0 fs-20 fw-medium"><?php echo pzl_format_number($stats['completed_projects']); ?></h4>
							</div>
							<div id="Projects-1" class="flex-shrink-0 text-end ms-auto"></div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- In Progress Projects Card -->
			<div class="col-xxl-3 col-md-6 col-sm-6 mb-3 mb-md-0">
				<div class="card custom-card overflow-hidden">
					<div class="card-body">
						<div class="mb-3 d-flex align-items-start justify-content-between">
							<span class="avatar avatar-sm bg-primary2 svg-white">
								<i class="ri-loop-left-fill fs-16"></i>
							</span>
							<span class="badge bg-danger-transparent">-5.20%</span>
						</div>
						<div class="d-flex align-items-end justify-content-between flex-wrap">
							<div class="flex-shrink-0">
								<div class="text-muted mb-1"><?php esc_html_e('In Progress', 'puzzlingcrm'); ?></div>
								<h4 class="mb-0 fs-20 fw-medium"><?php echo pzl_format_number($stats['in_progress_projects']); ?></h4>
							</div>
							<div id="Projects-3" class="flex-shrink-0 text-end ms-auto"></div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Pending Projects Card -->
			<div class="col-xxl-3 col-md-6 col-sm-6 mb-3 mb-md-0">
				<div class="card custom-card overflow-hidden">
					<div class="card-body">
						<div class="mb-3 d-flex align-items-start justify-content-between">
							<span class="avatar avatar-sm bg-primary3 svg-white">
								<i class="ri-time-line fs-16"></i>
							</span>
							<span class="badge bg-success-transparent">+5.20%</span>
						</div>
						<div class="d-flex align-items-end justify-content-between flex-wrap">
							<div class="flex-shrink-0">
								<div class="text-muted mb-1"><?php esc_html_e('Pending', 'puzzlingcrm'); ?></div>
								<h4 class="mb-0 fs-20 fw-medium"><?php echo pzl_format_number($stats['pending_projects']); ?></h4>
							</div>
							<div id="Projects-4" class="flex-shrink-0 text-end ms-auto"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Project Statistics Chart -->
		<div class="card custom-card">
			<div class="card-header justify-content-between">
				<div class="card-title"><?php esc_html_e('Project Statistics', 'puzzlingcrm'); ?></div>
				<div class="dropdown">
					<a aria-label="anchor" href="javascript:void(0);" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
						<?php esc_html_e('Last Week', 'puzzlingcrm'); ?>
						<i class="ri-arrow-down-s-line align-middle ms-1 d-inline-block"></i>
					</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="javascript:void(0);"><?php esc_html_e('Today', 'puzzlingcrm'); ?></a></li>
						<li><a class="dropdown-item" href="javascript:void(0);"><?php esc_html_e('Last Week', 'puzzlingcrm'); ?></a></li>
						<li><a class="dropdown-item" href="javascript:void(0);"><?php esc_html_e('Last Month', 'puzzlingcrm'); ?></a></li>
						<li><a class="dropdown-item" href="javascript:void(0);"><?php esc_html_e('Last Year', 'puzzlingcrm'); ?></a></li>
					</ul>
				</div>
			</div>
			<div class="card-body">
				<div class="d-flex gap-5 align-items-center p-3 justify-content-around bg-light mx-2 flex-wrap flex-xl-nowrap mb-3">
					<div class="d-flex gap-3 align-items-center flex-wrap">
						<div class="avatar avatar-lg flex-shrink-0 bg-primary-transparent avatar-rounded svg-primary shadow-sm border border-primary border-opacity-25">
							<i class="ri-money-dollar-circle-line fs-24 text-primary"></i>
						</div>
						<div>
							<span class="mb-1 d-block"><?php esc_html_e('Total Revenue', 'puzzlingcrm'); ?></span>
							<div class="d-flex align-items-end gap-2">
								<h4 class="mb-0"><?php echo pzl_format_currency($stats['total_revenue']); ?></h4>
								<div class="fs-13">
									<span class="op-7"><?php esc_html_e('Increased', 'puzzlingcrm'); ?></span>
									<span class="badge bg-success align-middle op-9">
										<?php echo pzl_format_percentage(abs($stats['revenue_growth'])); ?>
										<i class="ti ti-trending-up"></i>
									</span>
								</div>
							</div>
						</div>
					</div>
					<div class="d-flex gap-3 align-items-center flex-wrap">
						<div class="avatar avatar-lg flex-shrink-0 bg-primary1-transparent avatar-rounded svg-primary1 shadow-sm border border-primary1 border-opacity-25">
							<i class="ri-folder-2-line fs-24 text-primary1"></i>
						</div>
						<div>
							<span class="mb-1 d-block"><?php esc_html_e('Total Projects', 'puzzlingcrm'); ?></span>
							<div class="d-flex align-items-end gap-2">
								<h4 class="mb-0"><?php echo pzl_format_number($stats['total_projects']); ?></h4>
								<div class="fs-13">
									<span class="op-7"><?php esc_html_e('Increased', 'puzzlingcrm'); ?></span>
									<span class="badge bg-danger align-middle op-9">
										1.6%<i class="ti ti-trending-down"></i>
									</span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div style="height: 300px; position: relative;">
					<canvas id="project-statistics"></canvas>
				</div>
			</div>
		</div>
	</div>
</div>
<!--End::row-1 -->

<!-- Start:: row-2 -->
<div class="row">
	<!-- Running Projects List -->
	<div class="col-xxl-4 col-xl-12 mb-4 mb-xxl-0">
		<div class="card custom-card">
			<div class="card-header justify-content-between">
				<div class="card-title">
					<?php esc_html_e('Running Projects List', 'puzzlingcrm'); ?>
				</div>
				<a href="<?php echo esc_url(add_query_arg('view', 'projects')); ?>" class="btn btn-sm btn-primary-light">
					<?php esc_html_e('View All', 'puzzlingcrm'); ?>
				</a>
			</div>
			<div class="card-body">
				<?php if (empty($running_projects_data)): ?>
					<div class="text-center p-4">
						<p class="text-muted"><?php esc_html_e('No running projects', 'puzzlingcrm'); ?></p>
					</div>
				<?php else: ?>
					<?php foreach ($running_projects_data as $index => $project_data): 
						$project = $project_data['project'];
						$progress = $project_data['progress'];
						$assigned_members = $project_data['assigned_members'];
						$last_modified = $project_data['last_modified'];
					?>
					<div class="p-3 <?php echo $index < count($running_projects_data) - 1 ? 'pb-2 border-bottom' : ''; ?>">
						<div class="d-flex align-items-start gap-3 mb-3">
							<div class="flex-grow-1">
								<p class="fw-medium mb-1 fs-14">
									<?php echo esc_html($project->post_title); ?>
									<a href="javascript:void(0);" class="text-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php esc_attr_e('Get Information', 'puzzlingcrm'); ?>">
										<i class="ri-information-2-line fs-13 op-7 lh-1 align-middle"></i>
									</a>
								</p>
								<p class="text-muted mb-1 fw-normal fs-12">
									<?php echo esc_html(wp_trim_words($project->post_content, 15)); ?>
								</p>
								<div>
									<?php esc_html_e('Status:', 'puzzlingcrm'); ?>
									<span class="text-<?php echo $progress >= 75 ? 'success' : ($progress >= 50 ? 'warning' : 'info'); ?> fw-normal fs-12">
										<?php echo pzl_format_percentage($progress, 0); ?>
										<?php esc_html_e('Completed', 'puzzlingcrm'); ?>
									</span>
								</div>
							</div>
							<div class="flex-shrink-0 text-end">
								<p class="mb-3 fs-11 text-muted">
									<i class="ri-time-line text-muted fs-11 align-middle lh-1 me-1 d-inline-block"></i>
									<?php echo pzl_human_time_diff($last_modified); ?>
								</p>
								<div class="avatar-list-stacked">
									<?php 
									$display_members = array_slice($assigned_members, 0, 4);
									foreach ($display_members as $member_id):
										$member_user = get_userdata($member_id);
										if ($member_user):
									?>
									<span class="avatar avatar-sm avatar-rounded">
										<?php echo get_avatar($member_id, 24, '', '', ['class' => '']); ?>
									</span>
									<?php 
										endif;
									endforeach;
									if (count($assigned_members) > 4):
									?>
									<a class="avatar avatar-sm bg-primary border border-2 avatar-rounded text-fixed-white" href="javascript:void(0);">
										<?php echo '+' . (count($assigned_members) - 4); ?>
									</a>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<div>
							<div class="progress progress-lg rounded-pill p-1 ms-auto bg-primary-transparent" role="progressbar" aria-valuenow="<?php echo esc_attr($progress); ?>" aria-valuemin="0" aria-valuemax="100">
								<div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill bg-primary" style="width: <?php echo esc_attr($progress); ?>%"></div>
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
	
	<!-- Monthly Goals Chart -->
	<div class="col-xxl-3 col-lg-6 col-xl-6 mb-4 mb-xxl-0">
		<div class="card custom-card">
			<div class="card-header justify-content-between">
				<div class="card-title"><?php esc_html_e('Monthly Goals', 'puzzlingcrm'); ?></div>
				<a href="<?php echo esc_url(add_query_arg('view', 'reports')); ?>" class="btn btn-sm btn-light">
					<?php esc_html_e('View All', 'puzzlingcrm'); ?>
				</a>
			</div>
			<div class="card-body">
				<div style="height: 200px; position: relative;">
					<canvas id="monthly-target"></canvas>
				</div>
				<div class="d-flex gap-3 align-items-center justify-content-between text-center p-3 bg-light mt-3">
					<div>
						<span class="mb-1 d-block">
							<i class="ri-circle-fill fs-8 align-middle lh-1 text-primary"></i>
							<?php esc_html_e('New Projects', 'puzzlingcrm'); ?>
						</span>
						<h6 class="mb-1"><?php echo pzl_format_number($monthly_goals['new_projects']); ?></h6>
						<span class="text-success fw-medium">
							<i class="ri-arrow-up-s-fill"></i> 3.5%
						</span>
					</div>
					<div>
						<span class="mb-1 d-block">
							<i class="ri-circle-fill fs-8 align-middle lh-1 text-primary1"></i>
							<?php esc_html_e('Completed', 'puzzlingcrm'); ?>
						</span>
						<h6 class="mb-1"><?php echo pzl_format_number($monthly_goals['completed']); ?></h6>
						<span class="text-danger fw-medium">
							<i class="ri-arrow-down-s-fill"></i> 1.5%
						</span>
					</div>
					<div>
						<span class="mb-1 d-block">
							<i class="ri-circle-fill fs-8 align-middle lh-1 text-primary2"></i>
							<?php esc_html_e('Pending', 'puzzlingcrm'); ?>
						</span>
						<h6 class="mb-1"><?php echo pzl_format_number($monthly_goals['pending']); ?></h6>
						<span class="text-success fw-medium">
							<i class="ri-arrow-up-s-fill"></i> 0.1%
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Daily Tasks Timeline -->
	<div class="col-xxl-5 col-lg-6 col-xl-6">
		<div class="card custom-card">
			<div class="card-header justify-content-between">
				<div class="card-title">
					<?php esc_html_e('Daily Tasks', 'puzzlingcrm'); ?>
				</div>
				<div class="dropdown">
					<a href="javascript:void(0);" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
						<?php esc_html_e('View All', 'puzzlingcrm'); ?>
						<i class="ri-arrow-down-s-line align-middle ms-1 d-inline-block"></i>
					</a>
					<ul class="dropdown-menu" role="menu">
						<li><a class="dropdown-item" href="javascript:void(0);"><?php esc_html_e('Download', 'puzzlingcrm'); ?></a></li>
						<li><a class="dropdown-item" href="javascript:void(0);"><?php esc_html_e('Import', 'puzzlingcrm'); ?></a></li>
						<li><a class="dropdown-item" href="javascript:void(0);"><?php esc_html_e('Export', 'puzzlingcrm'); ?></a></li>
					</ul>
				</div>
			</div>
			
			<div class="card-body">
				<?php if (empty($daily_tasks)): ?>
					<div class="text-center p-4">
						<p class="text-muted"><?php esc_html_e('No tasks scheduled for today', 'puzzlingcrm'); ?></p>
					</div>
				<?php else: ?>
					<ul class="list-group list-group-flush list-unstyled">
						<?php foreach ($daily_tasks as $index => $task): 
							$due_date = get_post_meta($task->ID, '_due_date', true);
							$due_time = get_post_meta($task->ID, '_due_time', true);
							$project_id = get_post_meta($task->ID, '_project_id', true);
							$assigned_to = get_post_meta($task->ID, '_assigned_to', true);
							
							$task_time = '';
							if ($due_time) {
								$task_time = pzl_format_date(strtotime($due_time), 'H:i');
							} elseif ($due_date) {
								$task_time = pzl_format_date(strtotime($due_date), 'H:i');
							}
							
							$colors = ['primary', 'primary1', 'primary2', 'primary3'];
							$color = $colors[$index % count($colors)];
						?>
						<li class="list-group-item border-bottom-0 d-flex gap-3 p-0 align-items-start <?php echo $index < count($daily_tasks) - 1 ? 'mb-1' : 'mb-0'; ?>">
							<div class="flex-shrink-0 daily-tasks-time">
								<span class="text-muted ms-auto fs-11 flex-shrink-0 flex-fill">
									<?php echo $task_time ?: pzl_format_date(strtotime($task->post_date), 'H:i'); ?>
								</span>
							</div>
							<div class="card border border-<?php echo esc_attr($color); ?> border-opacity-25 shadow-none custom-card mb-0 bg-<?php echo esc_attr($color); ?>-transparent">
								<div class="card-body">
									<p class="fw-medium mb-2 lh-1 d-flex align-items-center gap-2 justify-content-between">
										<?php echo esc_html($task->post_title); ?>
										<a aria-label="anchor" href="<?php echo esc_url(add_query_arg(['view' => 'tasks', 'task_id' => $task->ID])); ?>" class="float-end fs-16 text-<?php echo esc_attr($color); ?>" data-bs-title="<?php esc_attr_e('View Details', 'puzzlingcrm'); ?>" data-bs-placement="top" data-bs-toggle="tooltip">
											<i class="ri-add-circle-fill"></i>
										</a>
									</p>
									<div class="d-flex flex-wrap gap-2 align-items-center">
										<?php if ($project_id): 
											$project = get_post($project_id);
											if ($project):
										?>
										<span class="badge bg-primary-transparent"><?php echo esc_html($project->post_title); ?></span>
										<?php endif; endif; ?>
										<?php if ($assigned_to):
											$assigned_user = get_userdata($assigned_to);
											if ($assigned_user):
										?>
										<div class="avatar-list-stacked ms-auto">
											<span class="avatar avatar-xs avatar-rounded">
												<?php echo get_avatar($assigned_to, 20, '', '', ['class' => '']); ?>
											</span>
										</div>
										<?php endif; endif; ?>
									</div>
								</div>
							</div>
						</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<!-- End:: row-2 -->

<!-- Start:: row-3 -->
<div class="row">
	<!-- Projects Summary Table -->
	<div class="col-xxl-9 col-xl-12 mb-4 mb-xxl-0">
		<div class="card custom-card">
			<div class="card-header justify-content-between">
				<div class="card-title">
					<?php esc_html_e('Projects Summary', 'puzzlingcrm'); ?>
				</div>
				<div class="d-flex flex-wrap">
					<div class="me-3 my-1">
						<input class="form-control form-control-sm" type="text" id="projects-search" placeholder="<?php esc_attr_e('Search here...', 'puzzlingcrm'); ?>" aria-label="<?php esc_attr_e('Search', 'puzzlingcrm'); ?>">
					</div>
					<div class="dropdown my-1">
						<a href="javascript:void(0);" class="btn btn-primary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
							<?php esc_html_e('Sort By', 'puzzlingcrm'); ?>
							<i class="ri-arrow-down-s-line align-middle ms-1 d-inline-block"></i>
						</a>
						<ul class="dropdown-menu" role="menu">
							<li><a class="dropdown-item" href="javascript:void(0);"><?php esc_html_e('New', 'puzzlingcrm'); ?></a></li>
							<li><a class="dropdown-item" href="javascript:void(0);"><?php esc_html_e('Popular', 'puzzlingcrm'); ?></a></li>
							<li><a class="dropdown-item" href="javascript:void(0);"><?php esc_html_e('Related', 'puzzlingcrm'); ?></a></li>
						</ul>
					</div>
				</div>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-hover text-nowrap table-bordered" id="projects-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e('#', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Project Title', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Tasks', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Progress', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Assigned Team', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Status', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Due Date', 'puzzlingcrm'); ?></th>
								<th scope="col"><?php esc_html_e('Actions', 'puzzlingcrm'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($projects_table_data as $index => $project_data): 
								$project = $project_data['project'];
								$progress = $project_data['progress'];
								$done_tasks = $project_data['done_tasks'];
								$total_tasks = $project_data['total_tasks'];
								$status_slug = $project_data['status_slug'];
								$status_name = $project_data['status_name'];
								$assigned_members = $project_data['assigned_members'];
								$due_date = $project_data['due_date'];
								
								// Status badge class
								$status_class = 'bg-primary-transparent';
								if ($status_slug === 'completed' || $status_slug === 'done') {
									$status_class = 'bg-success-transparent';
								} elseif ($status_slug === 'pending') {
									$status_class = 'bg-warning-transparent';
								}
							?>
							<tr>
								<td><?php echo pzl_format_number($index + 1); ?></td>
								<td>
									<span class="fw-medium"><?php echo esc_html($project->post_title); ?></span>
								</td>
								<td>
									<?php echo pzl_format_number($done_tasks); ?>
									<span class="op-7">/<?php echo pzl_format_number($total_tasks); ?></span>
								</td>
								<td>
									<div class="d-flex align-items-center">
										<div class="progress progress-sm w-100" role="progressbar" aria-valuenow="<?php echo esc_attr($progress); ?>" aria-valuemin="0" aria-valuemax="100">
											<div class="progress-bar bg-primary" style="width: <?php echo esc_attr($progress); ?>%"></div>
										</div>
										<div class="ms-2"><?php echo pzl_format_percentage($progress, 0); ?></div>
									</div>
								</td>
								<td>
									<div class="avatar-list-stacked">
										<?php 
										$display_members = array_slice($assigned_members, 0, 4);
										foreach ($display_members as $member_id):
											$member_user = get_userdata($member_id);
											if ($member_user):
										?>
										<span class="avatar avatar-xs avatar-rounded">
											<?php echo get_avatar($member_id, 20, '', '', ['class' => '']); ?>
										</span>
										<?php 
											endif;
										endforeach;
										if (count($assigned_members) > 4):
										?>
										<a class="avatar avatar-xs bg-light text-default border border-2 avatar-rounded" href="javascript:void(0);">
											+<?php echo count($assigned_members) - 4; ?>
										</a>
										<?php endif; ?>
									</div>
								</td>
								<td>
									<span class="badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_name); ?></span>
								</td>
								<td>
									<?php if ($due_date): ?>
										<?php echo pzl_format_date($due_date, 'Y/m/d'); ?>
									<?php else: ?>
										<span class="text-muted">-</span>
									<?php endif; ?>
								</td>
								<td>
									<div class="btn-list">
										<a aria-label="anchor" href="<?php echo esc_url(add_query_arg(['view' => 'projects', 'project_id' => $project->ID])); ?>" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php esc_attr_e('View', 'puzzlingcrm'); ?>" class="btn btn-icon rounded-pill btn-sm btn-primary-light">
											<i class="ti ti-eye"></i>
										</a>
										<a aria-label="anchor" href="<?php echo esc_url(add_query_arg(['view' => 'projects', 'action' => 'edit', 'project_id' => $project->ID])); ?>" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php esc_attr_e('Edit', 'puzzlingcrm'); ?>" class="btn btn-icon rounded-pill btn-sm btn-secondary-light">
											<i class="ti ti-pencil"></i>
										</a>
										<a aria-label="anchor" href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php esc_attr_e('Delete', 'puzzlingcrm'); ?>" class="btn btn-icon rounded-pill btn-sm btn-danger-light" onclick="if(confirm('<?php esc_attr_e('Are you sure?', 'puzzlingcrm'); ?>')) { window.location.href='<?php echo esc_url(add_query_arg(['action' => 'delete', 'project_id' => $project->ID])); ?>'; }">
											<i class="ti ti-trash"></i>
										</a>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="card-footer">
				<div class="d-flex align-items-center">
					<div>
						<?php 
						printf(
							esc_html__('Showing %d items', 'puzzlingcrm'),
							count($projects_table_data)
						);
						?>
						<i class="bi bi-arrow-left ms-2 fw-medium align-middle"></i>
					</div>
					<div class="ms-auto">
						<nav aria-label="<?php esc_attr_e('Page navigation', 'puzzlingcrm'); ?>" class="pagination-style-4">
							<ul class="pagination mb-0">
								<li class="page-item disabled">
									<a class="page-link" href="javascript:void(0);">
										<?php esc_html_e('Previous', 'puzzlingcrm'); ?>
									</a>
								</li>
								<li class="page-item active"><a class="page-link" href="javascript:void(0);">1</a></li>
								<li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
								<li class="page-item">
									<a class="page-link text-primary" href="javascript:void(0);">
										<?php esc_html_e('Next', 'puzzlingcrm'); ?>
									</a>
								</li>
							</ul>
						</nav>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Tasks Summary Widget -->
	<div class="col-xxl-3 col-xl-12">
		<div class="card custom-card overflow-hidden">
			<div class="card-header justify-content-between">
				<div class="card-title">
					<?php esc_html_e('Tasks Summary', 'puzzlingcrm'); ?>
				</div>
				<a href="<?php echo esc_url(add_query_arg('view', 'tasks')); ?>" class="btn btn-sm btn-light">
					<?php esc_html_e('View All', 'puzzlingcrm'); ?>
				</a>
			</div>
			<div class="card-body">
				<div class="d-flex gap-3 align-items-center justify-content-between p-3 bg-light mb-4">
					<div>
						<h6 class="mb-1"><?php esc_html_e('Task Completion Rate', 'puzzlingcrm'); ?></h6>
						<p class="mb-0 text-muted"><?php esc_html_e('Within scheduled time', 'puzzlingcrm'); ?></p>
					</div>
					<div>
						<h5 class="mb-0">
							<?php echo pzl_format_percentage($stats['completion_rate'], 0); ?>
							<span class="badge bg-success text-fixed-white fw-medium fs-8 ms-2">
								<i class="ri-arrow-up-s-fill"></i> 1.5%
							</span>
						</h5>
					</div>
				</div>
				<div style="height: 200px; position: relative;">
					<canvas id="tasks-report"></canvas>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- End:: row-3 -->

<style>
/* Responsive Styles */
@media (max-width: 1400px) {
	.col-xxl-5, .col-xxl-7, .col-xxl-4, .col-xxl-3, .col-xxl-9 {
		flex: 0 0 100%;
		max-width: 100%;
	}
}

@media (max-width: 768px) {
	.page-header-breadcrumb .btn-list {
		width: 100%;
		margin-top: 1rem;
	}
	
	.page-header-breadcrumb .btn-list .btn {
		flex: 1;
	}
	
	.table-responsive {
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
	}
	
	.card-header .d-flex {
		flex-direction: column;
		gap: 0.5rem;
	}
	
	.card-header .d-flex > div {
		width: 100%;
	}
}

/* Dark Mode Support */
[data-theme-mode="dark"] .card.custom-card {
	background-color: var(--default-body-bg-color, #1a1a1c);
	border-color: rgba(255, 255, 255, 0.1);
}

[data-theme-mode="dark"] .card.custom-card .card-body {
	color: var(--text-primary, #e9ecef);
}

[data-theme-mode="dark"] .text-muted {
	color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme-mode="dark"] .bg-light {
	background-color: rgba(255, 255, 255, 0.05) !important;
}

[data-theme-mode="dark"] .border-bottom {
	border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme-mode="dark"] .table {
	color: var(--text-primary, #e9ecef);
}

[data-theme-mode="dark"] .table thead th {
	border-color: rgba(255, 255, 255, 0.1);
}

[data-theme-mode="dark"] .table tbody tr {
	border-color: rgba(255, 255, 255, 0.1);
}

[data-theme-mode="dark"] .progress {
	background-color: rgba(255, 255, 255, 0.1);
}

[data-theme-mode="dark"] .dropdown-menu {
	background-color: var(--default-body-bg-color, #1a1a1c);
	border-color: rgba(255, 255, 255, 0.1);
}

[data-theme-mode="dark"] .dropdown-item {
	color: var(--text-primary, #e9ecef);
}

[data-theme-mode="dark"] .dropdown-item:hover {
	background-color: rgba(255, 255, 255, 0.1);
}
</style>

<script>
jQuery(document).ready(function($) {
	// Check if Chart.js is loaded
	if (typeof Chart === 'undefined') {
		console.warn('Chart.js is not loaded');
		return;
	}
	
	// Get theme mode
	const isDarkMode = document.documentElement.getAttribute('data-theme-mode') === 'dark';
	const textColor = isDarkMode ? 'rgba(255, 255, 255, 0.8)' : 'rgba(0, 0, 0, 0.8)';
	const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
	
	// Project Statistics Chart (ApexCharts style with Chart.js)
	const projectStatsCtx = document.getElementById('project-statistics');
	if (projectStatsCtx && projectStatsCtx.getContext) {
		const ctx = projectStatsCtx.getContext('2d');
		new Chart(ctx, {
			type: 'line',
			data: {
				labels: <?php echo json_encode($month_labels); ?>,
				datasets: [{
					label: '<?php echo esc_js(__('Revenue', 'puzzlingcrm')); ?>',
					data: <?php echo json_encode($revenue_data); ?>,
					borderColor: isDarkMode ? '#845adf' : '#845adf',
					backgroundColor: isDarkMode ? 'rgba(132, 90, 223, 0.1)' : 'rgba(132, 90, 223, 0.1)',
					tension: 0.4,
					fill: true,
					borderWidth: 3
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				aspectRatio: 2.5,
				plugins: {
					legend: {
						display: true,
						position: 'top',
						labels: {
							color: textColor
						}
					},
					tooltip: {
						backgroundColor: isDarkMode ? 'rgba(26, 26, 28, 0.9)' : 'rgba(255, 255, 255, 0.9)',
						titleColor: textColor,
						bodyColor: textColor,
						borderColor: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
						borderWidth: 1
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						grid: {
							color: gridColor
						},
						ticks: {
							color: textColor
						}
					},
					x: {
						grid: {
							color: gridColor,
							display: false
						},
						ticks: {
							color: textColor
						}
					}
				}
			}
		});
	}
	
	// Monthly Goals Chart
	const monthlyTargetCtx = document.getElementById('monthly-target');
	if (monthlyTargetCtx && monthlyTargetCtx.getContext) {
		const ctx = monthlyTargetCtx.getContext('2d');
		new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: [
					'<?php echo esc_js(__('New Projects', 'puzzlingcrm')); ?>',
					'<?php echo esc_js(__('Completed', 'puzzlingcrm')); ?>',
					'<?php echo esc_js(__('Pending', 'puzzlingcrm')); ?>'
				],
				datasets: [{
					data: [
						<?php echo $monthly_goals['new_projects']; ?>,
						<?php echo $monthly_goals['completed']; ?>,
						<?php echo $monthly_goals['pending']; ?>
					],
					backgroundColor: [
						isDarkMode ? 'rgba(132, 90, 223, 0.8)' : '#845adf',
						isDarkMode ? 'rgba(40, 167, 69, 0.8)' : '#28a745',
						isDarkMode ? 'rgba(255, 193, 7, 0.8)' : '#ffc107'
					],
					borderWidth: 0
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				aspectRatio: 1,
				plugins: {
					legend: {
						position: 'bottom',
						labels: {
							color: textColor,
							padding: 15
						}
					}
				}
			}
		});
	}
	
	// Tasks Report Chart
	const tasksReportCtx = document.getElementById('tasks-report');
	if (tasksReportCtx && tasksReportCtx.getContext) {
		const ctx = tasksReportCtx.getContext('2d');
		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: [
					'<?php echo esc_js(__('Completed', 'puzzlingcrm')); ?>',
					'<?php echo esc_js(__('In Progress', 'puzzlingcrm')); ?>',
					'<?php echo esc_js(__('Overdue', 'puzzlingcrm')); ?>'
				],
				datasets: [{
					label: '<?php echo esc_js(__('Tasks', 'puzzlingcrm')); ?>',
					data: [
						<?php echo $stats['completed_tasks']; ?>,
						<?php echo $stats['pending_tasks'] - $stats['overdue_tasks']; ?>,
						<?php echo $stats['overdue_tasks']; ?>
					],
					backgroundColor: [
						isDarkMode ? 'rgba(40, 167, 69, 0.8)' : '#28a745',
						isDarkMode ? 'rgba(132, 90, 223, 0.8)' : '#845adf',
						isDarkMode ? 'rgba(220, 53, 69, 0.8)' : '#dc3545'
					]
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				aspectRatio: 1.5,
				plugins: {
					legend: {
						display: false
					},
					tooltip: {
						backgroundColor: isDarkMode ? 'rgba(26, 26, 28, 0.9)' : 'rgba(255, 255, 255, 0.9)',
						titleColor: textColor,
						bodyColor: textColor
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						grid: {
							color: gridColor
						},
						ticks: {
							color: textColor
						}
					},
					x: {
						grid: {
							color: gridColor,
							display: false
						},
						ticks: {
							color: textColor
						}
					}
				}
			}
		});
	}
	
	// Projects table search
	$('#projects-search').on('keyup', function() {
		const value = $(this).val().toLowerCase();
		$('#projects-table tbody tr').filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
		});
	});
	
	// Initialize tooltips
	if (typeof bootstrap !== 'undefined') {
		const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
		tooltipTriggerList.map(function (tooltipTriggerEl) {
			return new bootstrap.Tooltip(tooltipTriggerEl);
		});
	}
});
</script>
