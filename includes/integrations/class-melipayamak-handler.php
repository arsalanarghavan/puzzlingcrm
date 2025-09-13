<?php
/**
 * PuzzlingCRM Melipayamak SMS Handler
 * Implements the SMS Service Interface.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure the interface is loaded before implementing it
if ( ! interface_exists('PuzzlingCRM_SMS_Service_Interface') ) {
    require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/class-sms-service-interface.php';
}

class CSM_Melipayamak_Handler implements PuzzlingCRM_SMS_Service_Interface {
    private $api_key;
    private $api_secret;
    private $sender_number;
    private $api_url = 'https://rest.melipayamak.com/v1/send/pattern';

    public function __construct( $api_key, $api_secret, $sender_number ) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->sender_number = $sender_number;
    }
    
    /**
     * Sends an SMS message using a pattern.
     *
     * @param string $to The recipient's mobile number.
     * @param string $message This will be the pattern code for Melipayamak.
     * @param array $params The parameters to be replaced in the pattern (e.g., ['amount' => '10000']).
     * @return bool True on success, false on failure.
     */
    public function send_sms( $to, $message, $params = [] ) {
        $pattern_code = $message; // For this provider, 'message' is the pattern code.

        if (empty($this->sender_number) || empty($this->api_key) || empty($pattern_code)) {
            error_log('PuzzlingCRM SMS Error (Melipayamak): API Key, Sender number, or Pattern Code is not configured.');
            return false;
        }

        // Melipayamak expects values to be a string, so we need to format the params
        $values = [];
        foreach ($params as $key => $value) {
            $values[$key] = (string)$value;
        }

        $body = [
            'pattern_code' => $pattern_code,
            'originator'   => $this->sender_number,
            'recipient'    => $to,
            'values'       => $values,
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
            error_log('PuzzlingCRM SMS Error (Melipayamak): ' . $response->get_error_message());
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