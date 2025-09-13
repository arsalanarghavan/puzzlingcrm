<?php
class PuzzlingCRM_Shortcode_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'register_shortcodes' ] );
    }

    public function register_shortcodes() {
        // Main dashboard wrapper
        add_shortcode( 'puzzling_dashboard', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_dashboard' ] );
        
        // --- System Manager Shortcodes ---
        add_shortcode( 'puzzling_manage_projects', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_sm_manage_projects' ] ); 
        add_shortcode( 'puzzling_sm_contracts', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_sm_contracts' ] ); 
        add_shortcode( 'puzzling_settings_payment', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_settings_payment' ] );
        add_shortcode( 'puzzling_settings_sms', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_settings_sms' ] );

        // --- Finance Manager Shortcodes ---
        add_shortcode( 'puzzling_fm_dashboard', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_fm_dashboard' ] );

        // --- Team Member Shortcodes ---
        add_shortcode( 'puzzling_tm_tasks', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_tm_tasks' ] );

        // --- Client Shortcodes ---
        add_shortcode( 'puzzling_client_dashboard', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_dashboard' ] );
        add_shortcode( 'puzzling_client_projects', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_projects' ] );
        add_shortcode( 'puzzling_client_payments', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_payments' ] );
    }
}