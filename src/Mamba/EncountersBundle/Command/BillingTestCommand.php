<?php
namespace Mamba\EncountersBundle\Command;

use Core\ScriptBundle\Script;

/**
 * BillingTestCommand
 *
 * @package EncountersBundle
 */
class BillingTestCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Send test query to billing gateway url",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "billing:test"
    ;

    protected

        /**
         * Test array
         *
         * @var array
         */
        $dataArray = array(
            'app_id'           => '355',
            'oid'              => '742241457',
            'operation_id'     => '256637377',
            'amount'           => '1.00000',
            'amount_developer' => '0.331848',
            'validation_id'    => '137141976',
            'time'             => '1329502081',
            'sig'              => 'cea843107882d094fa84ae818a647b3b',
        ),

        /**
         * Billing gateway
         *
         * @var string
         */
        $billingGatewayURL = "http://meetwithme.ru/gateway/billing"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->billingGatewayURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->dataArray));
        /*curl_setopt($ch, CURLOPT_VERBOSE, true);*/
        $result = curl_exec($ch);

        if (!$result) {
            $this->log("OK", 64);
        } else {
            $this->log($result, 16);
        }
    }
}