<?php
/**
 * Name: Easypay API 
 * Author: Stoyan Kostadinov <https://skostadinov.com>
 */

class EasyPay_API {

    /**
     * Settings
     */
    public $client_id = '__YOUR_CLIENT_ID__';
    public $secret_key = '__YOUR_SECRET_KEY__';
    public $submit_url = 'https://epay.bg/ezp/reg_bill.cgi';


    /**
     * Generate new code of payment
     * 
     * @param string $invoice Order id or someting releatable
     * @param string $amount  The amount of the payment
     * @param string $expires Expire date /01.01.2019 01:01/
     * @param string $desc    Description
     * 
     * @author Stoyan Kostadinov <https://skostadinov.com>
     * @return string
     */
    public function new_payment( $invoice, $amount, $expires, $desc ) {

        //Generate request information
        $form_data  = "\nMIN={$this->client_id}";
        $form_data .= "\nINVOICE={$invoice}";
        $form_data .= "\nAMOUNT={$amount}";
        $form_data .= "\nEXP_TIME={$expires}";
        $form_data .= "\nENCODING=utf-8";
        $form_data .= "\nDESCR={$desc}";
        
        $FORM_ENCODED = base64_encode( $form_data );
        $FORM_CHECKSUM = $this->hmac( 'sha1', $FORM_ENCODED, $this->secret_key );

        //Post request
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded",
                'method'  => 'POST',
                'timeout'     => 30,
                'redirection' => 2,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
                'sslverify'   => TRUE,
                'stream'      => FALSE,
                'content' => http_build_query( array(
                    'ENCODED' => $FORM_ENCODED,
                    'CHECKSUM' => $FORM_CHECKSUM,
                ) ),
            )
        );

        $context = stream_context_create( $options );
        $result = file_get_contents( $this->submit_url, false, $context );

        return $result;
    }

    /**
     * Generate hmac
     * 
     * @author EasyPay API Documentation
     * @return string
     */
    public function hmac( $algo, $data, $passwd ) {
        /* md5 and sha1 only */
        $algo = strtolower( $algo );
        $p = array( 
            'md5' => 'H32', 
            'sha1' => 'H40' 
        );

        if (strlen($passwd) > 64) {
            $passwd = pack($p[$algo], $algo($passwd));
        }

        if (strlen($passwd) < 64) {
            $passwd = str_pad($passwd, 64, chr(0));
        }

        $ipad = substr($passwd, 0, 64) ^ str_repeat(chr(0x36), 64);
        $opad = substr($passwd, 0, 64) ^ str_repeat(chr(0x5C), 64);

        return ( $algo( $opad . pack( $p[$algo], $algo( $ipad . $data ) ) ) );
    }

}