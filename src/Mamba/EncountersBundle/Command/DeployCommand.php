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
                'www2',
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

        foreach (self::$servers as $servers) {
            foreach ($servers as $server) {
                $commands[] = array(
                    'description' => "Stopping cron daemon at $server server",
                    'command'     => array(
                        ($server != 'www1') ? "ssh $server 'sudo /etc/init.d/cron stop'" : 'sudo /etc/init.d/cron stop',
                    ),
                );
            }
        }

        $this->log("Stopping all cron scripts..", 48);
        if ($this->getMemcache()->set('cron:stop', time())) {
            $this->log("SUCCESS", 64);
        } else {
            throw new \Exception("Operation failed");
        }

        /** Останавливаем веб-сервер */
        $commands[] = array(
            'description' => "Stopping nginx and php5-fpm",
            'command'     => array(
                'sudo /etc/init.d/nginx stop',
                'sudo /etc/init.d/php5-fpm stop',
            ),
        );

        /** www1 */
        $commands[] = array(
            'description' => "Sync code base and saving parameters.ini",
            'command'     => array(
                'cp /home/shpizel/encounters/app/config/parameters.ini /tmp/',
                'cd /home/shpizel/encounters/;git stash;git pull',
                'cp /tmp/parameters.ini /home/shpizel/encounters/app/config/',
            ),
        );

        $commands[] = array(
            'description' => "Preparing project",
            'command'     => array(
                'cd /home/shpizel/encounters/;rm -fr app/cache/*;rm -fr app/logs/*',
                'cd /home/shpizel/encounters/;/usr/bin/php /home/shpizel/encounters/app/console assets:install web/',
                '/usr/bin/php /home/shpizel/encounters/app/console assetic:dump --env=prod --no-debug',
                '/usr/bin/php /home/shpizel/encounters/app/console cache:warmup --env=prod --no-debug',
                'cd /home/shpizel/encounters/;sudo chmod -R 777 app/cache;sudo chmod -R 777 app/logs',
            ),
        );

        /** rsync на другие тачки */
        foreach (self::$servers as $servers) {
            foreach ($servers as $server) {
                if ($server != 'www1') {
                    $commands[] = array(
                        'description' => "Copying code to $server server",
                        'command'     => array(
                            "rsync -vrlgoD --delete /home/shpizel/encounters/ shpizel@{$server}:/home/shpizel/encounters",
                        ),
                    );

                    $commands[] = array(
                        'description' => "Preparing project on $server server",
                        'command'     => array(
                            "ssh $server 'cd /home/shpizel/encounters/;rm -fr app/cache/*;rm -fr app/logs/*'",
                            "ssh $server 'cd /home/shpizel/encounters/;/usr/bin/php /home/shpizel/encounters/app/console assets:install web/'",
                            "ssh $server '/usr/bin/php /home/shpizel/encounters/app/console assetic:dump --env=prod --no-debug'",
                            "ssh $server '/usr/bin/php /home/shpizel/encounters/app/console cache:warmup --env=prod --no-debug'",
                            "ssh $server 'cd /home/shpizel/encounters/;sudo chmod -R 777 app/cache;sudo chmod -R 777 app/logs'",
                        ),
                    );
                }
            }
        }

        /**
         * Нужно
         *
         * @author shpizel
         */

        /** Стартуем веб-сервер */
        $commands[] = array(
            'description' => "Starting nginx and php5-fpm",
            'command'     => array(
                'sudo /etc/init.d/nginx start',
                'sudo /etc/init.d/php5-fpm start',
            ),
        );

        /** Сначала нужно запустить все крон-скрипты */
        foreach (self::$servers as $servers) {
            foreach ($servers as $server) {
                $commands[] = array(
                    'description' => "Starting cron daemon at $server server",
                    'command' => array(
                        ($server != 'www1') ? "ssh $server 'sudo /etc/init.d/cron start'" : 'sudo /etc/init.d/cron start',
                    ),
                );
            }
        }

        $hostname = $this->getCurrentHostName();
        foreach ($commands as $item) {
            $this->log($item['description'] . "..", 48);
            foreach ($item['command'] as $command) {
                $this->log("<comment>Executing</comment>: $command");
                system($command, $code);

                if (!$code) {
                    $this->log("SUCCESS", 64);
                } else {
                    throw new \Exception("Operation failed");
                }
            }
        }

        $this->log("Completed, check project in browser..", 64);
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