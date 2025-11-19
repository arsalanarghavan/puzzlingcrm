<?php
/**
 * Header Component
 * 
 * Renders the dashboard header with logo, search, notifications, and profile menu
 *
 * @package PuzzlingCRM
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Header Component class
 */
class PuzzlingCRM_Header_Component extends PuzzlingCRM_Abstract_Component {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'header' );
	}

	/**
	 * Render the header component
	 */
	public function render() {
		if ( ! $this->is_dashboard_page() ) {
			return;
		}

		// Set component data before rendering
		$this->set_data( $this->get_data() );

		$this->load_template( 'header.php' );
	}

	/**
	 * Enqueue header assets
	 */
	public function enqueue_assets() {
		if ( ! $this->is_dashboard_page() ) {
			return;
		}

		// Note: CSS is loaded by main template, we only load JS here
		
		// Enqueue JS (in footer for better performance, but ensure jQuery and Bootstrap are loaded)
		wp_enqueue_script(
			'pzl-header',
			PUZZLINGCRM_PLUGIN_URL . 'assets/js/components/header.js',
			array( 'jquery', 'pzl-bootstrap-js' ),
			PUZZLINGCRM_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'pzl-header',
			'pzlHeader',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'pzl_header_nonce' ),
				'i18n'     => array(
					'search'        => __( 'Search...', 'puzzlingcrm' ),
					'noResults'     => __( 'No results found', 'puzzlingcrm' ),
					'loading'       => __( 'Loading...', 'puzzlingcrm' ),
					'notifications' => __( 'Notifications', 'puzzlingcrm' ),
					'viewAll'       => __( 'View All', 'puzzlingcrm' ),
				),
			)
		);
	}

	/**
	 * Get header data
	 *
	 * @return array Header data.
	 */
	public function get_data() {
		return array(
			'user'             => PuzzlingCRM_Header_Data_Provider::get_user_data(),
			'notifications'    => PuzzlingCRM_Header_Data_Provider::get_notifications(),
			'unread_count'     => PuzzlingCRM_Header_Data_Provider::get_unread_notifications_count(),
			'search_config'    => PuzzlingCRM_Header_Data_Provider::get_search_config(),
			'language_options' => PuzzlingCRM_Header_Data_Provider::get_language_options(),
			'logo_url'         => PuzzlingCRM_Header_Data_Provider::get_logo_url(),
			'profile_menu'     => PuzzlingCRM_Header_Data_Provider::get_profile_menu_items(),
		);
	}
}

