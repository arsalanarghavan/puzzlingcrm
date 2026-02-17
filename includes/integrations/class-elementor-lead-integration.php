<?php
/**
 * PuzzlingCRM Elementor Pro Form Integration
 *
 * Registers the "ارسال به CRM" form action. Users add it via:
 * Form widget → Actions After Submit → Add Item → ارسال به CRM (PuzzlingCRM)
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PuzzlingCRM_Elementor_Lead_Integration {

    public function __construct() {
        add_action( 'elementor_pro/forms/actions/register', [ $this, 'register_form_action' ] );
    }

    /**
     * Registers the PuzzlingCRM form action.
     */
    public function register_form_action( $form_actions_registrar ) {
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/integrations/class-elementor-form-action.php';
        $form_actions_registrar->register( new PuzzlingCRM_Elementor_Form_Action() );
    }
}
