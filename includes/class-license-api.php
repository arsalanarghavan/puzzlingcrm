<?php
/**
 * License REST API
 *
 * Provides REST API endpoints for license validation and activation
 *
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) {
    exit;
}

class PuzzlingCRM_License_API {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('puzzlingcrm/v1', '/license/check', [
            'methods' => 'POST',
            'callback' => [$this, 'check_license'],
            'permission_callback' => '__return_true', // Public endpoint
            'args' => [
                'domain' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route('puzzlingcrm/v1', '/license/activate', [
            'methods' => 'POST',
            'callback' => [$this, 'activate_license'],
            'permission_callback' => '__return_true', // Public endpoint
            'args' => [
                'domain' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Check license endpoint (domain is the license)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function check_license($request) {
        $domain = $request->get_param('domain');

        if (empty($domain)) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => 'دامنه الزامی است'
            ], 400);
        }

        $validation = PuzzlingCRM_License_Manager::validate_license($domain);

        if ($validation['valid']) {
            return new WP_REST_Response([
                'status' => 'valid',
                'expiry_date' => $validation['expiry_date'],
                'remaining_days' => $validation['remaining_days'],
                'remaining_percentage' => $validation['remaining_percentage']
            ], 200);
        } else {
            return new WP_REST_Response([
                'status' => $validation['status'],
                'message' => $validation['message'],
                'expiry_date' => $validation['expiry_date'] ?? null
            ], 200);
        }
    }

    /**
     * Activate license endpoint (domain is the license)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function activate_license($request) {
        $domain = $request->get_param('domain');

        if (empty($domain)) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => 'دامنه الزامی است'
            ], 400);
        }

        // First validate the license (domain is the license)
        $validation = PuzzlingCRM_License_Manager::validate_license($domain);

        if (!$validation['valid']) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => $validation['message']
            ], 200);
        }

        // Get the license by domain to activate it
        $license = PuzzlingCRM_License_Manager::get_license_by_domain($domain);

        if ($license) {
            // Update status to active if not already
            if ($license['status'] !== 'active') {
                PuzzlingCRM_License_Manager::update_license($license['id'], ['status' => 'active']);
            }

            return new WP_REST_Response([
                'status' => 'success',
                'message' => 'لایسنس با موفقیت فعال شد',
                'expiry_date' => $license['expiry_date'],
                'remaining_days' => $validation['remaining_days'],
                'remaining_percentage' => $validation['remaining_percentage']
            ], 200);
        }

        return new WP_REST_Response([
            'status' => 'error',
            'message' => 'لایسنس یافت نشد'
        ], 404);
    }

}

