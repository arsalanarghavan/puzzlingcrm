<?php
class PuzzlingCRM_Shortcode_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'register_shortcodes' ] );
    }

    public function register_shortcodes() {
        // Main dashboard wrapper
        add_shortcode( 'puzzling_dashboard', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_dashboard' ] );
        
        // --- System Manager Shortcodes ---
        // Renders the new contract form
        add_shortcode( 'puzzling_sm_contracts', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_sm_contracts' ] ); 
        // Renders the settings page
        add_shortcode( 'puzzling_sm_settings', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_sm_settings' ] );

        // --- Finance Manager Shortcodes ---
        // Renders the finance manager dashboard
        add_shortcode( 'puzzling_fm_dashboard', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_fm_dashboard' ] );

        // --- Team Member Shortcodes ---
        // Renders the team member's task manager
        add_shortcode( 'puzzling_tm_tasks', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_tm_tasks' ] );

        // --- Client Shortcodes ---
        // Renders the client's main dashboard view
        add_shortcode( 'puzzling_client_dashboard', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_dashboard' ] );
        // Renders the list of client's projects
        add_shortcode( 'puzzling_client_projects', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_projects' ] );
        // Renders the client's payment status table
        add_shortcode( 'puzzling_client_payments', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_payments' ] );
    }
}