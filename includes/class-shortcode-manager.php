<?php
class PuzzlingCRM_Shortcode_Manager {

    public function __construct() {
        add_action( 'init', [ $this, 'register_shortcodes' ] );
    }

    public function register_shortcodes() {
        add_shortcode( 'puzzling_dashboard', [ 'PuzzlingCRM_Frontend_Dashboard', 'render_dashboard' ] );
    }
}