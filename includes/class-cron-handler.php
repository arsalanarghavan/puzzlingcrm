<?php
/**
 * PuzzlingCRM Cron Handler
 * Manages scheduled tasks like sending payment reminders using a refactored, extensible architecture.
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

    /**
     * Retrieves the correct SMS handler instance based on saved settings.
     *
     * @param array $settings The plugin's settings array.
     * @return PuzzlingCRM_SMS_Service_Interface|null The handler instance or null if not configured.
     */
    private function get_sms_handler( $settings ) {
        $active_service = $settings['sms_service'] ?? null;
        $handler = null;

        switch ($active_service) {
            case 'melipayamak':
                $api_key = $settings['melipayamak_api_key'] ?? '';
                $sender_number = $settings['melipayamak_sender_number'] ?? '';
                if ($api_key && $sender_number) {
                    $handler = new CSM_Melipayamak_Handler($api_key, $settings['melipayamak_api_secret'] ?? '', $sender_number);
                }
                break;
            case 'parsgreen':
                $signature = $settings['parsgreen_signature'] ?? '';
                $sender_number = $settings['parsgreen_sender_number'] ?? '';
                if ($signature && $sender_number) {
                    $handler = new PuzzlingCRM_ParsGreen_Handler($signature, $sender_number);
                }
                break;
        }

        return $handler;
    }

    public function send_payment_reminders() {
        $settings = PuzzlingCRM_Settings_Handler::get_all_settings();
        $sms_handler = $this->get_sms_handler($settings);

        if ( !$sms_handler ) {
            set_transient('puzzling_sms_not_configured', true, DAY_IN_SECONDS);
            error_log('PuzzlingCRM Cron: No active and correctly configured SMS service found.');
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
            
            // Decoupled phone number logic
            $customer_phone = get_user_meta($customer_id, 'puzzling_phone_number', true);
            if (empty($customer_phone) && class_exists('WooCommerce')) {
                $customer_phone = get_user_meta($customer_id, 'billing_phone', true);
            }

            if ( empty($installments) || ! is_array($installments) || empty($customer_phone) ) {
                continue;
            }

            foreach ($installments as $installment) {
                if (($installment['status'] ?? 'pending') !== 'paid') {
                    try {
                        $due_date = new DateTime($installment['due_date'], new DateTimeZone('Asia/Tehran'));
                        $due_date->setTime(0, 0, 0);
                        
                        $interval = $today->diff($due_date);
                        if ($interval->invert) continue; // Skip past due dates for reminders

                        $days_left = $interval->days;
                        $amount_formatted = number_format($installment['amount']);
                        
                        $message_or_pattern = '';
                        $params = [];

                        if (in_array($days_left, [0, 1, 3])) {
                            if ($settings['sms_service'] === 'melipayamak') {
                                $pattern_map = [
                                    3 => $settings['pattern_3_days'] ?? '',
                                    1 => $settings['pattern_1_day'] ?? '',
                                    0 => $settings['pattern_due_today'] ?? '',
                                ];
                                $message_or_pattern = $pattern_map[$days_left] ?? null;
                                $params = ['amount' => $amount_formatted];
                            } elseif ($settings['sms_service'] === 'parsgreen') {
                                $message_template_map = [
                                    3 => $settings['parsgreen_msg_3_days'] ?? '',
                                    1 => $settings['parsgreen_msg_1_day'] ?? '',
                                    0 => $settings['parsgreen_msg_due_today'] ?? '',
                                ];
                                $message_template = $message_template_map[$days_left] ?? null;
                                if ($message_template) {
                                     $message_or_pattern = str_replace('{amount}', $amount_formatted, $message_template);
                                }
                            }

                            if (!empty($message_or_pattern)) {
                                if ($sms_handler->send_sms($customer_phone, $message_or_pattern, $params)) {
                                    error_log("PuzzlingCRM Cron SUCCESS: {$days_left}-day reminder SMS sent to {$customer_phone} for contract ID {$contract_post->ID}.");
                                }
                            }
                        }
                    } catch (Exception $e) {
                        error_log('PuzzlingCRM Cron Error: Invalid date format for contract ID ' . $contract_post->ID);
                    }
                }
            }
        }
    }
}