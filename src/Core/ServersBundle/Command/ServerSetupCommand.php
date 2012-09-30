<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Component\Console\Input\InputOption;

use Core\ScriptBundle\Script;

/**
 * ServerSetupCommand
 *
 * @package EncountersBundle
 */
class ServerSetupCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Setups server software",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "server:setup"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $this->log('ssh-copy-id');
    }
}