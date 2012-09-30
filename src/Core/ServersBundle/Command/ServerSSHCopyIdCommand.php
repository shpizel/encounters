<?php
namespace Core\ServersBundle\Command;

use Core\ScriptBundle\Script;

/**
 * ServerSSHCopyIdCommand
 *
 * @package EncountersBundle
 */
class ServerSSHCopyIdCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "SSH copy id script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "server:ssh-copy-id"
    ;

    private static

        /**
         * Servers
         *
         * @var array
         */
        $servers = array(

            'www' => array(
                'www1',
                'www2',

            ),

            'memory' => array(
                'memory1',
                'memory2',

            ),

            'storage' => array(
                'storage1',

            ),

            'script' => array(
                'script1',
                'script2',

            ),
        )
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $hostname = $this->input->getOption('hostname');

        $found = false;
        foreach (self::$servers as $group) {
            if (in_array($hostname, $group)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new \RuntimeException("Invalid hostname");
        }

        $commands = array();

        foreach (self::$servers as $group) {
            foreach ($group as $server) {
                if ($server != $hostname) {
                    $commands[] = "ssh-copy-id -i .ssh/id_rsa.pub $server";
                }
            }
        }

        $this->log(implode("; ", $commands), 64);
    }
}