<?php
class CSM_Melipayamak_Handler {
    private $api_key;
    private $api_secret;
    private $api_url = 'https://rest.melipayamak.com/v1/send/pattern';

    public function __construct( $api_key, $api_secret ) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }
    
    // Note: Melipayamak API might have a different structure. This is a generic example.
    // Please check their documentation for the correct API endpoint and parameters.
    public function send_pattern_sms( $recipient, $pattern_code, array $params ) {
        $body = [
            'pattern_code' => $pattern_code,
            'originator'   => 'YOUR_SENDER_NUMBER', // شماره فرستنده شما
            'recipient'    => $recipient,
            'values'       => $params,
        ];
        
        // Example of how their API might work. Adjust based on documentation.
        $args = [
            'method' => 'POST',
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'AccessKey ' . $this->api_key // Or your specific auth method
            ],
            'body' => json_encode($body)
        ];

        $response = wp_remote_post( $this->api_url, $args );

        if ( is_wp_error( $response ) ) {
            // Handle error
            return false;
        }

        return true;
    }
}