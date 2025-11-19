<?php
/**
 * Header Data Provider
 * 
 * Provides data for the header component
 *
 * @package PuzzlingCRM
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Header Data Provider class
 */
class PuzzlingCRM_Header_Data_Provider {

	/**
	 * Get current user data
	 *
	 * @return array User data array.
	 */
	public static function get_user_data() {
		if ( ! is_user_logged_in() ) {
			return array(
				'name'   => __( 'Guest', 'puzzlingcrm' ),
				'email'  => '',
				'avatar' => '',
				'role'   => 'guest',
			);
		}

		$current_user = wp_get_current_user();
		$user_role    = self::get_user_role_display( $current_user );

		return array(
			'id'     => $current_user->ID,
			'name'   => $current_user->display_name,
			'email'  => $current_user->user_email,
			'avatar' => get_avatar_url( $current_user->ID, array( 'size' => 32 ) ),
			'role'   => $user_role,
		);
	}

	/**
	 * Get user role display name
	 *
	 * @param WP_User $user User object.
	 * @return string Role display name.
	 */
	private static function get_user_role_display( $user ) {
		$roles = (array) $user->roles;

		if ( in_array( 'administrator', $roles, true ) || in_array( 'system_manager', $roles, true ) ) {
			return __( 'System Manager', 'puzzlingcrm' );
		}

		if ( in_array( 'finance_manager', $roles, true ) ) {
			return __( 'Finance Manager', 'puzzlingcrm' );
		}

		if ( in_array( 'team_member', $roles, true ) ) {
			return __( 'Team Member', 'puzzlingcrm' );
		}

		if ( in_array( 'customer', $roles, true ) ) {
			return __( 'Customer', 'puzzlingcrm' );
		}

		return __( 'User', 'puzzlingcrm' );
	}

	/**
	 * Get notifications for current user
	 *
	 * @param int $limit Number of notifications to retrieve.
	 * @return array Array of notifications.
	 */
	public static function get_notifications( $limit = 5 ) {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		// TODO: Implement actual notification system
		// For now, return empty array
		return array();
	}

	/**
	 * Get unread notifications count
	 *
	 * @return int Count of unread notifications.
	 */
	public static function get_unread_notifications_count() {
		if ( ! is_user_logged_in() ) {
			return 0;
		}

		// TODO: Implement actual notification count
		// For now, return 0
		return 0;
	}

	/**
	 * Get search configuration
	 *
	 * @return array Search configuration.
	 */
	public static function get_search_config() {
		return array(
			'enabled'     => true,
			'placeholder' => __( 'Search...', 'puzzlingcrm' ),
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'pzl_search_nonce' ),
		);
	}

	/**
	 * Get language options
	 *
	 * @return array Language options.
	 */
	public static function get_language_options() {
		$current_locale = get_locale();

		return array(
			'current' => $current_locale,
			'options' => array(
				'fa_IR' => array(
					'name'      => __( 'Persian', 'puzzlingcrm' ),
					'direction' => 'rtl',
				),
				'en_US' => array(
					'name'      => __( 'English', 'puzzlingcrm' ),
					'direction' => 'ltr',
				),
			),
		);
	}

	/**
	 * Get logo URL
	 *
	 * @return string Logo URL.
	 */
	public static function get_logo_url() {
		$custom_logo_id = get_theme_mod( 'custom_logo' );

		if ( $custom_logo_id ) {
			$logo = wp_get_attachment_image_src( $custom_logo_id, 'full' );
			if ( $logo ) {
				return $logo[0];
			}
		}

		// Default logo
		return PUZZLINGCRM_PLUGIN_URL . 'assets/images/logo.png';
	}

	/**
	 * Get profile menu items
	 *
	 * @return array Profile menu items.
	 */
	public static function get_profile_menu_items() {
		$dashboard_url = home_url( '/dashboard' );

		$items = array(
			array(
				'id'    => 'profile',
				'title' => __( 'My Profile', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/profile',
				'icon'  => 'ri-user-3-line',
			),
			array(
				'id'    => 'settings',
				'title' => __( 'Settings', 'puzzlingcrm' ),
				'url'   => $dashboard_url . '/settings',
				'icon'  => 'ri-settings-3-line',
			),
		);

		// Add logout
		$items[] = array(
			'id'    => 'logout',
			'title' => __( 'Logout', 'puzzlingcrm' ),
			'url'   => wp_logout_url( home_url() ),
			'icon'  => 'ri-logout-box-line',
		);

		/**
		 * Filter profile menu items
		 *
		 * @param array $items Profile menu items.
		 */
		return apply_filters( 'puzzlingcrm_header_profile_menu_items', $items );
	}
}

