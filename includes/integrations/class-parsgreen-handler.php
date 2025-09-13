<?php
/**
 * PuzzlingCRM ParsGreen SMS Handler
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_ParsGreen_Handler {
    private $signature;
    private $from;
    private $api_url = 'http://sms.parsgreen.ir/Api/SendSMS.asmx?WSDL';

    /**
     * Constructor.
     *
     * @param string $signature The API signature from ParsGreen.
     * @param string $from      The sender number.
     */
    public function __construct( $signature, $from ) {
        $this->signature = $signature;
        $this->from = $from;
    }

    /**
     * Sends a simple SMS.
     *
     * @param string $to      The recipient's mobile number.
     * @param string $message The text message to send.
     * @return bool True on success, false on failure.
     */
    public function send_sms( $to, $message ) {
        if ( empty( $this->signature ) || empty( $this->from ) ) {
            error_log( 'PuzzlingCRM SMS Error: ParsGreen signature or sender number is not configured.' );
            return false;
        }

        // ParsGreen requires UTF-8 encoding
        $message = mb_convert_encoding($message, "UTF-8");

        $parameters = [
            'signature' => $this->signature,
            'from'      => $this->from,
            'to'        => [ $to ], // API expects an array of mobiles
            'text'      => $message,
            'isFlash'   => false,
            'udh'       => ''
        ];

        // Check if SoapClient is available on the server
        if ( ! class_exists( 'SoapClient' ) ) {
            error_log( 'PuzzlingCRM SMS Error: SoapClient class is not found. Please enable the PHP SOAP extension on your server.' );
            return false;
        }

        try {
            $client   = new SoapClient( $this->api_url, ['encoding' => 'UTF-8']);
            $response = $client->SendGroupSmsSimple( $parameters );

            // According to ParsGreen docs, a positive number indicates success (it's the message ID)
            if ( isset($response->SendGroupSmsSimpleResult) && $response->SendGroupSmsSimpleResult > 0 ) {
                return true;
            }
            
            error_log( 'PuzzlingCRM SMS Error: ParsGreen API returned an error code: ' . ($response->SendGroupSmsSimpleResult ?? 'UNKNOWN') );
            return false;
        } catch ( SoapFault $ex ) {
            error_log( 'PuzzlingCRM SMS Error (SoapFault): ' . $ex->faultstring );
            return false;
        }
    }
}