<?php
/**
 * Visitor Statistics dashboard (system_manager only).
 * Data loaded via AJAX puzzlingcrm_get_visitor_stats.
 *
 * @package PuzzlingCRM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'manage_options' ) ) {
	echo '<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>' . esc_html__( 'شما دسترسی لازم برای مشاهده این بخش را ندارید.', 'puzzlingcrm' ) . '</div>';
	return;
}

$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : date( 'Y-m-d', strtotime( '-30 days' ) );
$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : date( 'Y-m-d' );
$base_url  = remove_query_arg( array( 'date_from', 'date_to' ) );
?>
<div class="pzl-dashboard-section pzl-visitor-statistics">
	<h3><i class="ri-line-chart-line me-2"></i><?php esc_html_e( 'آمار بازدیدکنندگان', 'puzzlingcrm' ); ?></h3>

	<div class="card custom-card mb-4">
		<div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
			<div class="card-title mb-0"><?php esc_html_e( 'بازه تاریخ', 'puzzlingcrm' ); ?></div>
			<form method="get" class="d-flex gap-2 flex-wrap align-items-center pzl-visitor-date-form">
				<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" class="form-control form-control-sm" style="max-width:150px" />
				<span class="text-muted">تا</span>
				<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" class="form-control form-control-sm" style="max-width:150px" />
				<button type="submit" class="btn btn-sm btn-primary"><?php esc_html_e( 'اعمال', 'puzzlingcrm' ); ?></button>
			</form>
		</div>
	</div>

	<!-- Summary cards -->
	<div class="row mb-4" id="pzl-visitor-summary-cards">
		<div class="col-md-3 col-6 mb-3">
			<div class="card custom-card border border-primary">
				<div class="card-body">
					<p class="text-muted mb-0 small"><?php esc_html_e( 'کل بازدیدها', 'puzzlingcrm' ); ?></p>
					<h4 class="fw-semibold mt-1 text-primary" id="pzl-stat-total">–</h4>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 mb-3">
			<div class="card custom-card border border-success">
				<div class="card-body">
					<p class="text-muted mb-0 small"><?php esc_html_e( 'بازدید یکتا', 'puzzlingcrm' ); ?></p>
					<h4 class="fw-semibold mt-1 text-success" id="pzl-stat-unique">–</h4>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 mb-3">
			<div class="card custom-card border border-info">
				<div class="card-body">
					<p class="text-muted mb-0 small"><?php esc_html_e( 'امروز', 'puzzlingcrm' ); ?></p>
					<h4 class="fw-semibold mt-1 text-info" id="pzl-stat-today">–</h4>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 mb-3">
			<div class="card custom-card border border-warning">
				<div class="card-body">
					<p class="text-muted mb-0 small"><?php esc_html_e( 'آنلاین (۵ دقیقه گذشته)', 'puzzlingcrm' ); ?></p>
					<h4 class="fw-semibold mt-1 text-warning" id="pzl-stat-online">–</h4>
				</div>
			</div>
		</div>
	</div>

	<!-- Daily chart -->
	<div class="card custom-card mb-4">
		<div class="card-header">
			<div class="card-title"><?php esc_html_e( 'بازدید روزانه', 'puzzlingcrm' ); ?></div>
		</div>
		<div class="card-body">
			<canvas id="pzl-visitor-daily-chart" height="120"></canvas>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6 mb-4">
			<div class="card custom-card h-100">
				<div class="card-header">
					<div class="card-title"><?php esc_html_e( 'پربازدیدترین صفحات', 'puzzlingcrm' ); ?></div>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead><tr><th>صفحه</th><th class="text-end">بازدید</th></tr></thead>
							<tbody id="pzl-top-pages"></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 mb-4">
			<div class="card custom-card h-100">
				<div class="card-header">
					<div class="card-title"><?php esc_html_e( 'مرورگرها', 'puzzlingcrm' ); ?></div>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead><tr><th>مرورگر</th><th class="text-end">تعداد</th></tr></thead>
							<tbody id="pzl-browsers"></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 mb-4">
			<div class="card custom-card h-100">
				<div class="card-header">
					<div class="card-title"><?php esc_html_e( 'سیستم‌عامل', 'puzzlingcrm' ); ?></div>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead><tr><th>سیستم‌عامل</th><th class="text-end">تعداد</th></tr></thead>
							<tbody id="pzl-os"></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 mb-4">
			<div class="card custom-card h-100">
				<div class="card-header">
					<div class="card-title"><?php esc_html_e( 'نوع دستگاه', 'puzzlingcrm' ); ?></div>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead><tr><th>دستگاه</th><th class="text-end">تعداد</th></tr></thead>
							<tbody id="pzl-devices"></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 mb-4">
			<div class="card custom-card h-100">
				<div class="card-header">
					<div class="card-title"><?php esc_html_e( 'منبع (رفرر)', 'puzzlingcrm' ); ?></div>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead><tr><th>دامنه</th><th class="text-end">تعداد</th></tr></thead>
							<tbody id="pzl-referrers"></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 mb-4">
			<div class="card custom-card h-100">
				<div class="card-header">
					<div class="card-title"><?php esc_html_e( 'بازدیدکنندگان آنلاین', 'puzzlingcrm' ); ?></div>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead><tr><th>صفحه</th><th class="text-end">زمان</th></tr></thead>
							<tbody id="pzl-online"></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Recent visitors -->
	<div class="card custom-card mb-4">
		<div class="card-header">
			<div class="card-title"><?php esc_html_e( 'آخرین بازدیدها', 'puzzlingcrm' ); ?></div>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover mb-0">
					<thead><tr><th>زمان</th><th>صفحه</th><th>IP</th><th>مرورگر / دستگاه</th></tr></thead>
					<tbody id="pzl-recent"></tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
(function() {
	var ajaxUrl = typeof puzzlingcrm_ajax_obj !== 'undefined' ? puzzlingcrm_ajax_obj.ajax_url : '';
	var nonce = typeof puzzlingcrm_ajax_obj !== 'undefined' ? puzzlingcrm_ajax_obj.nonce : '';
	if (!ajaxUrl || !nonce) return;

	var dailyChart = null;

	function fillTable(selector, rows, nameKey, countKey) {
		var tbody = document.getElementById(selector);
		if (!tbody) return;
		nameKey = nameKey || 'name';
		countKey = countKey || 'count';
		if (!rows || rows.length === 0) {
			tbody.innerHTML = '<tr><td colspan="2" class="text-muted">بدون داده</td></tr>';
			return;
		}
		tbody.innerHTML = rows.map(function(r) {
			var name = (r[nameKey] || '').toString().trim() || '–';
			return '<tr><td>' + escapeHtml(name) + '</td><td class="text-end">' + parseInt(r[countKey], 10) + '</td></tr>';
		}).join('');
	}

	function escapeHtml(s) {
		var div = document.createElement('div');
		div.textContent = s;
		return div.innerHTML;
	}

	function formatDate(d) {
		if (!d) return '–';
		try {
			var dt = new Date(d.replace(/-/g, '/'));
			return dt.toLocaleDateString('fa-IR', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
		} catch (e) { return d; }
	}

	function load() {
		var dateFrom = document.querySelector('.pzl-visitor-date-form input[name="date_from"]');
		var dateTo = document.querySelector('.pzl-visitor-date-form input[name="date_to"]');
		dateFrom = dateFrom ? dateFrom.value : '';
		dateTo = dateTo ? dateTo.value : '';

		var formData = new FormData();
		formData.append('action', 'puzzlingcrm_get_visitor_stats');
		formData.append('security', nonce);
		formData.append('date_from', dateFrom);
		formData.append('date_to', dateTo);

		var xhr = new XMLHttpRequest();
		xhr.open('POST', ajaxUrl);
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.onload = function() {
			try {
				var res = JSON.parse(xhr.responseText);
				if (!res || !res.success || !res.data) return;
				var d = res.data;
				var overall = d.overall || {};
				document.getElementById('pzl-stat-total').textContent = overall.total_visits != null ? Number(overall.total_visits).toLocaleString('fa-IR') : '–';
				document.getElementById('pzl-stat-unique').textContent = overall.unique_visitors != null ? Number(overall.unique_visitors).toLocaleString('fa-IR') : '–';
				document.getElementById('pzl-stat-today').textContent = overall.today_visits != null ? Number(overall.today_visits).toLocaleString('fa-IR') : '–';
				document.getElementById('pzl-stat-online').textContent = overall.online_now != null ? Number(overall.online_now).toLocaleString('fa-IR') : '–';

				var daily = d.daily || [];
				var labels = daily.map(function(x) { return x.date; });
				var values = daily.map(function(x) { return x.visits || 0; });
				if (typeof Chart !== 'undefined') {
					if (dailyChart) dailyChart.destroy();
					var ctx = document.getElementById('pzl-visitor-daily-chart');
					if (ctx) {
						dailyChart = new Chart(ctx.getContext('2d'), {
							type: 'line',
							data: { labels: labels, datasets: [{ label: 'بازدید', data: values, borderColor: 'rgb(75, 192, 192)', fill: true, tension: 0.2 }] },
							options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }
						});
					}
				}

				fillTable('pzl-top-pages', d.top_pages, 'page_url', 'visit_count');
				fillTable('pzl-browsers', d.browsers);
				fillTable('pzl-os', d.os);
				fillTable('pzl-devices', d.devices);
				fillTable('pzl-referrers', d.referrers);

				var online = d.online || [];
				var onlineTbody = document.getElementById('pzl-online');
				if (onlineTbody) {
					if (online.length === 0) onlineTbody.innerHTML = '<tr><td colspan="2" class="text-muted">هیچ بازدید آنلاینی در ۵ دقیقه گذشته نیست</td></tr>';
					else onlineTbody.innerHTML = online.slice(0, 20).map(function(o) {
						return '<tr><td>' + escapeHtml((o.page_url || '').substring(0, 60)) + '</td><td class="text-end">' + formatDate(o.visit_date) + '</td></tr>';
					}).join('');
				}

				var recent = d.recent || [];
				var recentTbody = document.getElementById('pzl-recent');
				if (recentTbody) {
					if (recent.length === 0) recentTbody.innerHTML = '<tr><td colspan="4" class="text-muted">بدون داده</td></tr>';
					else recentTbody.innerHTML = recent.map(function(r) {
						return '<tr><td>' + formatDate(r.visit_date) + '</td><td>' + escapeHtml((r.page_url || '').substring(0, 50)) + '</td><td>' + escapeHtml(r.ip_address || '') + '</td><td>' + escapeHtml((r.browser || '') + ' / ' + (r.device_type || '')) + '</td></tr>';
					}).join('');
				}
			} catch (e) {}
		};
		xhr.send(formData);
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', load);
	else load();
})();
</script>
