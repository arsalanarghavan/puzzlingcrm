<?php
class CSM_Zarinpal_Handler {
    private $merchant_id;
    private $api_url = 'https://api.zarinpal.com/pg/v4/payment/request.json';

    public function __construct( $merchant_id ) {
        $this->merchant_id = $merchant_id;
    }

    public function create_payment_link( $amount, $description, $callback_url ) {
        $data = [
            'merchant_id'  => $this->merchant_id,
            'amount'       => $amount * 10, // Zarinpal uses Rials
            'description'  => $description,
            'callback_url' => $callback_url,
        ];

        $response = wp_remote_post( $this->api_url, [
            'method'      => 'POST',
            'headers'     => ['Content-Type' => 'application/json'],
            'body'        => json_encode( $data ),
            'timeout'     => 45,
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( !empty($body['data']['authority']) ) {
            return 'https://www.zarinpal.com/pg/StartPay/' . $body['data']['authority'];
        }

        return false;
    }
}