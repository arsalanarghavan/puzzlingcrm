<?php
class PuzzlingCRM_Shortcode_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'register_shortcodes' ] );
    }

    public function register_shortcodes() {
        // Consolidated, role-aware shortcodes
        add_shortcode( 'puzzling_dashboard', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_dashboard' ] );
        add_shortcode( 'puzzling_projects', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_projects' ] );
        add_shortcode( 'puzzling_contracts', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_contracts' ] );
        add_shortcode( 'puzzling_invoices', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_invoices' ] );
        add_shortcode( 'puzzling_pro_invoices', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_pro_invoices' ] );
        add_shortcode( 'puzzling_appointments', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_appointments' ] );
        add_shortcode( 'puzzling_tickets', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_tickets' ] );
        add_shortcode( 'puzzling_tasks', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_tasks' ] );

        // Manager-only shortcodes
        add_shortcode( 'puzzling_consultations', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_consultations' ] );
        add_shortcode( 'puzzling_customers', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_customers' ] );
        add_shortcode( 'puzzling_staff', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_staff' ] );
        add_shortcode( 'puzzling_subscriptions', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_subscriptions' ] );
        add_shortcode( 'puzzling_reports', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_reports' ] );
        add_shortcode( 'puzzling_settings', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_settings' ] );
        add_shortcode( 'puzzling_logs', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_logs' ] );

        // NEW: Add the leads shortcode, also for managers
        add_shortcode( 'puzzling_leads', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_page_leads' ] );
        
        // Login page shortcode
        add_shortcode( 'puzzling_login', [ 'PuzzlingCRM_Login_Page', 'render_login_page' ] );
    }
}