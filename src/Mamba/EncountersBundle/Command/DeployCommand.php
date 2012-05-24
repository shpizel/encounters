<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\Script;

/**
 * DeployCommand
 *
 * @package EncountersBundle
 */
class DeployCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Deploy script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "deploy"
    ;

    private static

        /**
         * Серверы
         *
         * @var array
         */
        $servers = array(

            /**
             * WWW
             *
             * @var array
             */
            'www' => array(
                'www1',
            ),

            /**
             * Memory
             *
             * @var array
             */
            'memory' => array(
                'memory1',
                'memory2',
            ),

            /**
             * Storage
             *
             * @var array
             */
            'storage' => array(
                'storage1',
            ),

            /**
             * Script
             *
             * @var array
             */
            'script' => array(
                'script1',
                'script2',
            )
        )
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $commands = array();

        /** Сначала нужно остановить все крон-скрипты */
        foreach (self::$servers as $servers) {
            foreach ($servers as $server) {
                $commands[] = "ssh $server 'sudo /etc/init.d/cron stop'";
            }
        }

        /** Установим ключ в memcache, который убьет текущие процессы */
        //$this->getMemcache()->set('cron:stop', time());

        /** Останавливаем веб-сервер */
        $commands[] = 'sudo /etc/init.d/cron stop';
        $commands[] = 'sudo /etc/init.d/php5-fpm stop';

        /** www1 */
        $commands[] = 'cp /home/shpizel/encounters/app/config/parameters.ini /tmp/parameters.ini';
        $commands[] = 'cd /home/shpizel/encouners/;git stash;git stash clear;git pull;';
        $commands[] = 'cp /tmp/parameters.ini /home/shpizel/encounters/app/config/parameters.ini';
        $commands[] = 'rm -fr /home/shpizel/app/cache/*';
        $commands[] = 'rm -fr /home/shpizel/app/logs/*';
        $commands[] = '/usr/bin/php /home/shpizel/encounters/app/console assets:install web/';
        $commands[] = '/usr/bin/php /home/shpizel/encounters/app/console assetic:dump --env=prod --no-debug';
        $commands[] = '/usr/bin/php /home/shpizel/encounters/app/console cache:warmup --env=prod --no-debug';
        $commands[] = 'cd /home/shpizel/encouners/;chmod -R 777 app/cache;chmod -R 777 app/logs;';

        /** rsync на другие тачки */
        foreach (self::$servers as $servers) {
            foreach ($servers as $server) {
                if ($server != 'www1') {
                    $commands[] = "rsync -vrlgoD --delete /home/shpizel/encounters/ shpizel@{$server}:/home/shpizel/encounters";
                }
            }
        }

        /** Останавливаем веб-сервер */
        $commands[] = 'sudo /etc/init.d/cron start';
        $commands[] = 'sudo /etc/init.d/php5-fpm start';

        /** Сначала нужно запустить все крон-скрипты */
        foreach (self::$servers as $servers) {
            foreach ($servers as $server) {
                $commands[] = "ssh $server 'sudo /etc/init.d/cron stop'";
            }
        }

        print_r($commands);

    }

    /**
     * Возвращает имя окружения в котором запущен скрипт
     *
     * @return string
     */
    private function getCurrentEnvironment() {
        return $this->getContainer()->get('kernel')->getEnvironment();
    }

    /**
     * Возвращает имя машины на которой запущен скрипт
     *
     * @return string
     */
    private function getCurrentHostName() {
        return trim(`hostname`);
    }
}