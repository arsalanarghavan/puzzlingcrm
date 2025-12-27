<?php
/**
 * Page Wrapper Component
 * 
 * Wraps all dashboard page content with consistent header and styling
 *
 * @package PuzzlingCRM
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get helper functions
require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/helpers/page-wrapper-helper.php';

// Get current page and user role
$current_page = get_query_var( 'dashboard_page', '' );
$current_user = wp_get_current_user();
$user_role = '';

if ( $current_user ) {
	$roles = (array) $current_user->roles;
	if ( in_array( 'administrator', $roles, true ) || in_array( 'system_manager', $roles, true ) ) {
		$user_role = 'system_manager';
	} elseif ( in_array( 'finance_manager', $roles, true ) ) {
		$user_role = 'finance_manager';
	} elseif ( in_array( 'team_member', $roles, true ) ) {
		$user_role = 'team_member';
	} elseif ( in_array( 'customer', $roles, true ) ) {
		$user_role = 'customer';
	}
}

// Get breadcrumb and page title
$breadcrumb_items = puzzlingcrm_get_breadcrumb( $current_page );
$page_title = puzzlingcrm_get_page_title( $current_page, $user_role );

?>
<!-- Start::pzl-page-wrapper -->
<div class="pzl-page-wrapper">
	
	<!-- Start::pzl-page-header -->
	<div class="pzl-page-header">
		
		<!-- Breadcrumb (Right side in RTL) -->
		<div class="pzl-page-breadcrumb">
			<nav aria-label="breadcrumb">
				<ol class="breadcrumb mb-0">
					<?php foreach ( $breadcrumb_items as $index => $item ) : ?>
						<?php if ( $index === count( $breadcrumb_items ) - 1 ) : ?>
							<li class="breadcrumb-item active" aria-current="page">
								<?php echo esc_html( $item['title'] ); ?>
							</li>
						<?php else : ?>
							<li class="breadcrumb-item">
								<a href="<?php echo esc_url( $item['url'] ); ?>">
									<?php echo esc_html( $item['title'] ); ?>
								</a>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ol>
			</nav>
		</div>
		<!-- End::pzl-page-breadcrumb -->
		
		<!-- Page Title (Center) -->
		<div class="pzl-page-title">
			<h1 class="pzl-page-title-text"><?php echo esc_html( $page_title ); ?></h1>
		</div>
		<!-- End::pzl-page-title -->
		
		<!-- Page Actions (Left side in RTL) -->
		<div class="pzl-page-actions">
			<!-- Filter Button -->
			<button type="button" class="btn btn-sm btn-outline-primary pzl-page-action-btn" id="pzl-filter-btn" data-bs-toggle="modal" data-bs-target="#pzl-filter-modal" title="<?php esc_attr_e( 'فیلتر', 'puzzlingcrm' ); ?>">
				<i class="ri-filter-3-line"></i>
				<span class="d-none d-md-inline"><?php esc_html_e( 'فیلتر', 'puzzlingcrm' ); ?></span>
			</button>
			
			<!-- Share Button -->
			<button type="button" class="btn btn-sm btn-outline-secondary pzl-page-action-btn" id="pzl-share-btn" data-bs-toggle="dropdown" aria-expanded="false" title="<?php esc_attr_e( 'اشتراک‌گذاری', 'puzzlingcrm' ); ?>">
				<i class="ri-share-line"></i>
				<span class="d-none d-md-inline"><?php esc_html_e( 'اشتراک', 'puzzlingcrm' ); ?></span>
			</button>
			
			<!-- Share Dropdown Menu -->
			<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="pzl-share-btn">
				<li>
					<a class="dropdown-item" href="#" id="pzl-share-link" data-action="copy-link">
						<i class="ri-link me-2"></i>
						<?php esc_html_e( 'کپی لینک', 'puzzlingcrm' ); ?>
					</a>
				</li>
				<li>
					<a class="dropdown-item" href="#" id="pzl-share-pdf" data-action="export-pdf">
						<i class="ri-file-pdf-line me-2"></i>
						<?php esc_html_e( 'خروجی PDF', 'puzzlingcrm' ); ?>
					</a>
				</li>
				<li>
					<a class="dropdown-item" href="#" id="pzl-share-print" data-action="print">
						<i class="ri-printer-line me-2"></i>
						<?php esc_html_e( 'چاپ', 'puzzlingcrm' ); ?>
					</a>
				</li>
			</ul>
		</div>
		<!-- End::pzl-page-actions -->
		
	</div>
	<!-- End::pzl-page-header -->
	
	<!-- Start::pzl-page-content -->
	<div class="pzl-page-content">
		<?php
		/**
		 * Content will be inserted here by dashboard-wrapper.php
		 * This is where the page partials will be included
		 */
		?>
	</div>
	<!-- End::pzl-page-content -->
	
</div>
<!-- End::pzl-page-wrapper -->

<!-- Filter Modal -->
<div class="modal fade" id="pzl-filter-modal" tabindex="-1" aria-labelledby="pzl-filter-modal-label" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="pzl-filter-modal-label"><?php esc_html_e( 'فیلترها', 'puzzlingcrm' ); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'بستن', 'puzzlingcrm' ); ?>"></button>
			</div>
			<div class="modal-body">
				<p class="text-muted"><?php esc_html_e( 'فیلترهای صفحه در اینجا نمایش داده می‌شوند.', 'puzzlingcrm' ); ?></p>
				<!-- Filter content will be added dynamically by each page -->
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'بستن', 'puzzlingcrm' ); ?></button>
				<button type="button" class="btn btn-primary" id="pzl-apply-filters"><?php esc_html_e( 'اعمال فیلتر', 'puzzlingcrm' ); ?></button>
			</div>
		</div>
	</div>
</div>
<!-- End Filter Modal -->

