<?php
/**
 * PuzzlingCRM Main AJAX Handler
 *
 * This class instantiates all other AJAX handlers.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Main_Ajax_Handler {

    public function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    private function load_dependencies() {
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-error-codes.php'; // <-- خط جدید
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-user-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-project-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-task-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-ticket-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-notification-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-workflow-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-lead-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-form-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-agile-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-consultation-ajax-handler.php';
        require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/ajax/class-sms-ajax-handler.php';
    }

    private function define_hooks() {
        new PuzzlingCRM_User_Ajax_Handler();
        new PuzzlingCRM_Project_Ajax_Handler();
        new PuzzlingCRM_Task_Ajax_Handler();
        new PuzzlingCRM_Ticket_Ajax_Handler();
        new PuzzlingCRM_Notification_Ajax_Handler();
        new PuzzlingCRM_Workflow_Ajax_Handler();
        new PuzzlingCRM_Lead_Ajax_Handler();
        new PuzzlingCRM_Form_Ajax_Handler();
        new PuzzlingCRM_Agile_Ajax_Handler();
        new PuzzlingCRM_Consultation_Ajax_Handler();
        new PuzzlingCRM_SMS_Ajax_Handler();
    }
}