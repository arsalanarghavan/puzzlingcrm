<?php
class CSM_Zarinpal_Handler {
    private $merchant_id;
    private $request_api_url = 'https://api.zarinpal.com/pg/v4/payment/request.json';
    private $verify_api_url = 'https://api.zarinpal.com/pg/v4/payment/verify.json';

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

        $response = wp_remote_post( $this->request_api_url, [
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

    /**
     * Verifies a payment with Zarinpal.
     *
     * @param int $amount The amount of the transaction (in Tomans).
     * @param string $authority The authority code from Zarinpal.
     * @return array|false An array with payment details on success, false on failure.
     */
    public function verify_payment( $amount, $authority ) {
        $data = [
            'merchant_id' => $this->merchant_id,
            'amount'      => $amount * 10, // Zarinpal uses Rials
            'authority'   => $authority,
        ];

        $response = wp_remote_post( $this->verify_api_url, [
            'method'      => 'POST',
            'headers'     => ['Content-Type' => 'application/json'],
            'body'        => json_encode($data),
            'timeout'     => 45,
        ]);

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        // According to Zarinpal docs, code 100 or 101 means success
        if ( isset($body['data']['code']) && ($body['data']['code'] == 100 || $body['data']['code'] == 101) ) {
            return [
                'status' => 'success',
                'ref_id' => $body['data']['ref_id'],
            ];
        }

        return false;
    }
}