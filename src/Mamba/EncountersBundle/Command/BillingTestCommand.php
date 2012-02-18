<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BillingTestCommand
 *
 * @package EncountersBundle
 */
class BillingTestCommand extends ContainerAwareCommand {

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
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        $this->setName('BillingTest');
    }

    /**
     * Экзекутор
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->billingGatewayURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->dataArray));
//        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $result = curl_exec($ch);

        $output->write($result);
    }
}