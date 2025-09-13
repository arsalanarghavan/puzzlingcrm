<?php
class PuzzlingCRM_Cron_Handler {
    
    public function __construct() {
        if ( ! wp_next_scheduled( 'puzzling_daily_reminder_hook' ) ) {
            wp_schedule_event( strtotime('today 9:00am'), 'daily', 'puzzling_daily_reminder_hook' );
        }
        
        add_action( 'puzzling_daily_reminder_hook', [ $this, 'send_payment_reminders' ] );
    }

    public function send_payment_reminders() {
        $api_key = PuzzlingCRM_Settings_Handler::get_setting('melipayamak_api_key');
        $api_secret = PuzzlingCRM_Settings_Handler::get_setting('melipayamak_api_secret');
        $pattern_3_days_left = PuzzlingCRM_Settings_Handler::get_setting('pattern_3_days');
        $pattern_1_day_left = PuzzlingCRM_Settings_Handler::get_setting('pattern_1_day');
        $pattern_due_today = PuzzlingCRM_Settings_Handler::get_setting('pattern_due_today');

        if (empty($api_key) || empty($pattern_3_days_left) || empty($pattern_1_day_left) || empty($pattern_due_today)) {
            error_log('PuzzlingCRM Cron: Melipayamak settings are incomplete. Reminders not sent.');
            return;
        }

        $melipayamak = new CSM_Melipayamak_Handler($api_key, $api_secret);
        
        $contracts = get_posts([
            'post_type' => 'contract',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        if (empty($contracts)) {
            return;
        }

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
                if (isset($installment['status']) && $installment['status'] === 'paid') {
                    continue;
                }

                try {
                    $due_date = new DateTime($installment['due_date'], new DateTimeZone('Asia/Tehran'));
                    $due_date->setTime(0, 0, 0);
                    
                    $interval = $today->diff($due_date);
                    
                    if ($interval->invert) { 
                        continue;
                    }

                    $days_left = $interval->days;
                    $pattern_to_use = null;
                    $params = ['amount' => number_format($installment['amount'])];

                    if ($days_left == 3) {
                        $pattern_to_use = $pattern_3_days_left;
                    } elseif ($days_left == 1) {
                        $pattern_to_use = $pattern_1_day_left;
                    } elseif ($days_left == 0) {
                        $pattern_to_use = $pattern_due_today;
                    }

                    if ($pattern_to_use) {
                        // **FIXED**: SMS sending is now active.
                        $melipayamak->send_pattern_sms($customer_phone, $pattern_to_use, $params);
                        error_log("PuzzlingCRM: SMS reminder sent to {$customer_phone} for contract ID {$contract_post->ID}. Pattern: {$pattern_to_use}");
                    }

                } catch (Exception $e) {
                    error_log('PuzzlingCRM Cron: Invalid date format for contract ID ' . $contract_post->ID);
                }
            }
        }
    }
}