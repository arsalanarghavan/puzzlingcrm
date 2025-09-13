<?php
/**
 * PuzzlingCRM Cron Handler
 * Manages scheduled tasks like sending payment reminders.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Cron_Handler {
    
    public function __construct() {
        if ( ! wp_next_scheduled( 'puzzling_daily_reminder_hook' ) ) {
            wp_schedule_event( strtotime('today 9:00am'), 'daily', 'puzzling_daily_reminder_hook' );
        }
        
        add_action( 'puzzling_daily_reminder_hook', [ $this, 'send_payment_reminders' ] );
    }

    public function send_payment_reminders() {
        $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        $active_service = $settings['sms_service'] ?? null;

        if ( empty($active_service) ) {
            // **IMPROVED: Set a transient to show an admin notice**
            set_transient('puzzling_sms_not_configured', true, DAY_IN_SECONDS);
            error_log('PuzzlingCRM Cron: No active SMS service selected.');
            return;
        }

        // If cron runs successfully, remove the notice transient
        delete_transient('puzzling_sms_not_configured');

        $contracts = get_posts(['post_type' => 'contract', 'posts_per_page' => -1, 'post_status' => 'publish']);
        if (empty($contracts)) return;

        $today = new DateTime('now', new DateTimeZone('Asia/Tehran'));
        $today->setTime(0, 0, 0);

        foreach ( $contracts as $contract_post ) {
            $installments = get_post_meta( $contract_post->ID, '_installments', true );
            $customer_id = $contract_post->post_author;
            $customer_phone = get_user_meta($customer_id, 'billing_phone', true);

            if ( empty($installments) || ! is_array($installments) || empty($customer_phone) ) {
                continue;
            }

            foreach ($installments as $installment) {
                if (($installment['status'] ?? 'pending') === 'paid') {
                    continue;
                }

                try {
                    $due_date = new DateTime($installment['due_date'], new DateTimeZone('Asia/Tehran'));
                    $due_date->setTime(0, 0, 0);
                    
                    $interval = $today->diff($due_date);
                    if ($interval->invert) continue;

                    $days_left = $interval->days;
                    $amount_formatted = number_format($installment['amount']);
                    
                    if (in_array($days_left, [0, 1, 3])) {
                         if ($active_service === 'melipayamak') {
                            $this->send_melipayamak_reminder($settings, $customer_phone, $days_left, $amount_formatted, $contract_post->ID);
                        } elseif ($active_service === 'parsgreen') {
                            $this->send_parsgreen_reminder($settings, $customer_phone, $days_left, $amount_formatted, $contract_post->ID);
                        }
                    }
                } catch (Exception $e) {
                    error_log('PuzzlingCRM Cron: Invalid date format for contract ID ' . $contract_post->ID);
                }
            }
        }
    }

    private function send_melipayamak_reminder($settings, $recipient, $days_left, $amount, $contract_id) {
        $api_key = $settings['melipayamak_api_key'] ?? '';
        $sender_number = $settings['melipayamak_sender_number'] ?? '';
        $pattern_map = [
            3 => $settings['pattern_3_days'] ?? '',
            1 => $settings['pattern_1_day'] ?? '',
            0 => $settings['pattern_due_today'] ?? '',
        ];
        $pattern_to_use = $pattern_map[$days_left] ?? null;

        if (empty($api_key) || empty($sender_number) || empty($pattern_to_use)) {
            set_transient('puzzling_sms_not_configured', true, DAY_IN_SECONDS);
            error_log("PuzzlingCRM Cron (Melipayamak): Settings are incomplete for a {$days_left}-day reminder.");
            return;
        }
        
        $melipayamak = new CSM_Melipayamak_Handler($api_key, $settings['melipayamak_api_secret'] ?? '', $sender_number);
        $params = ['amount' => $amount];
        if ($melipayamak->send_pattern_sms($recipient, $pattern_to_use, $params)) {
            error_log("PuzzlingCRM Cron (Melipayamak): SUCCESS - SMS sent to {$recipient} for contract ID {$contract_id}.");
        }
    }

    private function send_parsgreen_reminder($settings, $recipient, $days_left, $amount, $contract_id) {
        $signature = $settings['parsgreen_signature'] ?? '';
        $sender_number = $settings['parsgreen_sender_number'] ?? '';
        $message_template_map = [
            3 => $settings['parsgreen_msg_3_days'] ?? '',
            1 => $settings['parsgreen_msg_1_day'] ?? '',
            0 => $settings['parsgreen_msg_due_today'] ?? '',
        ];
        $message_template = $message_template_map[$days_left] ?? null;

        if (empty($signature) || empty($sender_number) || empty($message_template)) {
            set_transient('puzzling_sms_not_configured', true, DAY_IN_SECONDS);
            error_log("PuzzlingCRM Cron (ParsGreen): Settings are incomplete for a {$days_left}-day reminder.");
            return;
        }
        
        $message = str_replace('{amount}', $amount, $message_template);

        $parsgreen = new PuzzlingCRM_ParsGreen_Handler($signature, $sender_number);
        if ($parsgreen->send_sms($recipient, $message)) {
            error_log("PuzzlingCRM Cron (ParsGreen): SUCCESS - SMS sent to {$recipient} for contract ID {$contract_id}.");
        }
    }
}