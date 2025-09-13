<?php
class CSM_Cron_Handler {
    
    public function __construct() {
        // Schedule the event if it's not already scheduled
        if ( ! wp_next_scheduled( 'csm_daily_reminder_hook' ) ) {
            wp_schedule_event( time(), 'daily', 'csm_daily_reminder_hook' );
        }
        
        add_action( 'csm_daily_reminder_hook', [ $this, 'send_payment_reminders' ] );
    }

    public function send_payment_reminders() {
        // 1. Get all pending payment orders created by our plugin
        $args = [
            'post_type'   => 'shop_order',
            'post_status' => 'wc-pending',
            'meta_key'    => '_csm_due_date', // A custom meta key we set when creating the order
            'posts_per_page' => -1,
        ];
        $orders = get_posts( $args );

        $today = new DateTime('now');
        $melipayamak = new CSM_Melipayamak_Handler('API_KEY', 'API_SECRET');

        foreach ( $orders as $order_post ) {
            $order = wc_get_order( $order_post->ID );
            $due_date_str = get_post_meta( $order->get_id(), '_csm_due_date', true );
            if ( empty($due_date_str) ) continue;

            $due_date = new DateTime($due_date_str);
            $interval = $today->diff($due_date)->days;
            $customer_phone = $order->get_billing_phone();

            if ($interval == 3) {
                // Send "3 days left" SMS with its specific pattern
                // $melipayamak->send_pattern_sms($customer_phone, 'pattern_code_3_days', [...]);
            } elseif ($interval == 1) {
                // Send "1 day left" SMS
                // $melipayamak->send_pattern_sms($customer_phone, 'pattern_code_1_day', [...]);
            } elseif ($interval == 0) {
                // Send "due today" SMS
                // $melipayamak->send_pattern_sms($customer_phone, 'pattern_code_today', [...]);
            }
        }
    }
}
// new CSM_Cron_Handler();