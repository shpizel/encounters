<?php
namespace Core\LeveldbBundle\Command;

use Core\ScriptBundle\Script;

/**
 * LeveldbSlaveRestartCommand
 *
 * @package EncountersBundle
 */
class LeveldbSlaveRestartCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Leveldb slave restart script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "leveldb:slave:restart",

        /**
         * Leveldb image name
         *
         * @var str
         */
        LEVELDB_IMAGE_NAME = 'leveldb.json'
    ;

    private

        /**
         * Команды для запуска слейва
         *
         * @var array
         */
        $startCommands = array(
            'prod' => '/home/shpizel/leveldb-daemon/release/leveldb.json --db=/home/shpizel/leveldb.json --log_file=/home/shpizel/leveldb.log --daemonize=1 --threads_tcp=3 --threads_udp=3 --memory=8096 --buffer=128 --replication.host=memory1 --replication.id=memory1 --port=22510',
        )
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        if (!$this->pidof(self::LEVELDB_IMAGE_NAME)) {
            $env = $this->getCurrentEnvironment();
            if (isset($this->startCommands[$env])) {
                $cmd = $this->startCommands[$env];
                if (`{$cmd}`) {
                    $this->log('Leveldb started', 48);
                } else {
                    $this->log('Leveldb could not be started', 16);
                }
            } else {
                $this->log("Enviroment is not supported by slave", 32);
            }
        } else {
            $this->log("Leveldb is running", 64);
        }
    }

    /**
     * Gets pids by process image name
     *
     * @param $imageName
     */
    private function pidof($imageName) {
        if (($ret = `pidof {$imageName}`) && preg_match_all("!(\d+)!", $ret, $result)) {
            return array_pop($result);
        }
    }

    /**
     * Возвращает имя окружения в котором запущен скрипт
     *
     * @return string
     */
    private function getCurrentEnvironment() {
        return $this->getContainer()->get('kernel')->getEnvironment();
    }
}