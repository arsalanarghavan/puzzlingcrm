<?php
class CSM_Melipayamak_Handler {
    private $api_key;
    private $api_secret;
    private $sender_number;
    private $api_url = 'https://rest.melipayamak.com/v1/send/pattern';

    public function __construct( $api_key, $api_secret, $sender_number ) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->sender_number = $sender_number;
    }
    
    public function send_pattern_sms( $recipient, $pattern_code, array $params ) {
        if (empty($this->sender_number) || empty($this->api_key)) {
            error_log('PuzzlingCRM SMS Error: API Key or Sender number is not configured.');
            return false;
        }

        $body = [
            'pattern_code' => $pattern_code,
            'originator'   => $this->sender_number,
            'recipient'    => $recipient,
            'values'       => $params,
        ];
        
        $args = [
            'method' => 'POST',
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'AccessKey ' . $this->api_key
            ],
            'body' => json_encode($body),
            'timeout' => 30,
        ];

        $response = wp_remote_post( $this->api_url, $args );

        if ( is_wp_error( $response ) ) {
            error_log('PuzzlingCRM SMS Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code >= 300) {
             error_log('PuzzlingCRM SMS Error: Melipayamak API returned status ' . $response_code . ' with body: ' . wp_remote_retrieve_body($response));
             return false;
        }

        return true;
    }
}