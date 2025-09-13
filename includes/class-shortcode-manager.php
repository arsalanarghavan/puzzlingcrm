<?php
class PuzzlingCRM_Shortcode_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'register_shortcodes' ] );
    }

    public function register_shortcodes() {
        // --- Wrapper & General ---
        add_shortcode( 'puzzling_dashboard', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_dashboard_wrapper' ] );

        // --- System Manager & Admin Shortcodes ---
        add_shortcode( 'puzzling_customers', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_customers' ] );
        add_shortcode( 'puzzling_staff', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_staff' ] );
        add_shortcode( 'puzzling_projects', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_projects' ] );
        add_shortcode( 'puzzling_contracts', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_contracts' ] );
        add_shortcode( 'puzzling_tasks', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_tasks' ] );
        add_shortcode( 'puzzling_subscriptions', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_subscriptions' ] );
        add_shortcode( 'puzzling_appointments', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_appointments' ] );
        add_shortcode( 'puzzling_reports', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_reports' ] );
        add_shortcode( 'puzzling_settings', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_settings' ] );
        add_shortcode( 'puzzling_logs', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_logs' ] );
        add_shortcode( 'puzzling_tickets_manager', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_tickets' ] );
        add_shortcode( 'puzzling_pro_invoices', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_pro_invoices' ] );


        // --- Team Member Shortcodes ---
        add_shortcode( 'puzzling_team_tasks', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_team_tasks' ] );

        // --- Client Shortcodes ---
        add_shortcode( 'puzzling_client_dashboard', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_dashboard' ] );
        add_shortcode( 'puzzling_client_projects', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_projects' ] );
        add_shortcode( 'puzzling_client_invoices', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_invoices' ] );
        add_shortcode( 'puzzling_client_tickets', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_tickets' ] );
        add_shortcode( 'puzzling_client_appointments', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_appointments' ] );
        add_shortcode( 'puzzling_client_contracts', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_contracts' ] );
        add_shortcode( 'puzzling_client_pro_invoices', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_client_pro_invoices' ] );
    }
}