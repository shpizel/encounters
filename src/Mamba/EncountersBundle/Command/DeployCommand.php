<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\Script;

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
            throw new \Core\ScriptBundle\ScriptException("Operation failed");
        }

        /** Останавливаем веб-серверы */
        foreach (self::$servers['www'] as $server) {
            $commands[] = array(
                'description' => "Stopping nginx and php5-fpm at $server server",
                'command'     => array(
                    ($server != 'www1') ? "ssh $server 'sudo /etc/init.d/nginx stop'" : 'sudo /etc/init.d/nginx stop',
                    ($server != 'www1') ? "ssh $server 'sudo /etc/init.d/php5-fpm stop'" : 'sudo /etc/init.d/php5-fpm stop',
                ),
            );
        }

        /** Нужно залить новый код на www1 */
        $commands[] = array(
            'description' => "Sync code base and saving parameters.ini",
            'command'     => array(
                'cp /home/shpizel/encounters/app/config/parameters.ini /tmp/',
                'cd /home/shpizel/encounters/',
                'git stash',
                'git pull',
                //'git stash apply', -- намеренно пропускаем, т.к. синк делаем с гитом
                'cp /tmp/parameters.ini /home/shpizel/encounters/app/config/',
            ),
        );

        /**
         * Заливаем на все остальные серверы синхронно — это быстро
         *
         * @author shpizel
         */
        foreach (self::$servers as $role => $servers) {
            foreach ($servers as $server) {
                if ($server !== 'www1') {
                    $commands[] = array(
                        'description' => "Copying code to $server server",
                        'command'     => array(
                            "rsync -vrlgoD --delete /home/shpizel/encounters/ shpizel@{$server}:/home/shpizel/encounters > /dev/null",
                        ),
                    );
                }
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
                    throw new \Core\ScriptBundle\ScriptException("Operation failed");
                }
            }
        }

        /**
         * Нужно составить для каждого сервера набор команд, которые мы затем исполним в форках
         *
         * @author shpizel
         */
        $serverCommands = array();
        foreach (self::$servers as $role => $servers) {
            foreach ($servers as $server) {
                $serverCommands[$server] = array(
                    'cd /home/shpizel/encounters/;rm -fr app/cache/*;rm -fr app/logs/*',
                    'cd /home/shpizel/encounters/;/usr/bin/php /home/shpizel/encounters/app/console assets:install web/ > /dev/null',
                    '/usr/bin/php /home/shpizel/encounters/app/console assetic:dump --env=prod --no-debug > /dev/null',
                    '/usr/bin/php /home/shpizel/encounters/app/console cache:warmup --env=prod --no-debug > /dev/null',
                    'cd /home/shpizel/encounters/;sudo chmod -R 777 app/cache;sudo chmod -R 777 app/logs',
                );

                if ($server != 'www1') {
                    foreach ($serverCommands[$server] as $key => $serverCommand) {
                        $serverCommands[$server][$key] = "ssh {$server} '{$serverCommand}'";
                    }
                }
            }
        }

        /**
         * Запускаем форки и ожидаем их завершения
         *
         * @author shpizel
         */
        $pids = array();

        foreach ($serverCommands as $server => $commandsToExecute) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                throw new \Core\ScriptBundle\ScriptException("Could not fork process");
            } else if ($pid) {
                $pids[] = $pid;
            } else {
                foreach ($commandsToExecute as $cmd) {
                    system($cmd, $code);

                    if ($code) {
                        throw new \Core\ScriptBundle\ScriptException("Operation failed");
                    } else {
                        //$this->log("$cmd completed", 64);
                    }
                }

                exit(0);
            }
        }

        $this->log("Awaiting " . count($pids) . " forks..", -1);

        while(count($pids) > 0){
            $finishedPid = pcntl_waitpid(-1, $status, WNOHANG);
            if ($status !== 0) {
                throw new \Core\ScriptBundle\ScriptException("Process {$finishedPid} returns " . pcntl_wexitstatus($status) . " code");
            }

            foreach($pids as $key => $pid) {
                if($finishedPid == $pid) {
                    unset($pids[$key]);

                    $this->log("Awaiting " . count($pids) . " forks..", -1);
                }
            }

            usleep(250);
        }

        $this->log("Project prepared OK", 64);

        $commands = array();

        /** Стартуем веб-серверы */
        foreach (self::$servers['www'] as $server) {
            $commands[] = array(
                'description' => "Starting nginx and php5-fpm at $server server",
                'command'     => array(
                    ($server != 'www1') ? "ssh $server 'sudo /etc/init.d/nginx start'" : 'sudo /etc/init.d/nginx start',
                    ($server != 'www1') ? "ssh $server 'sudo /etc/init.d/php5-fpm start'" : 'sudo /etc/init.d/php5-fpm start',
                ),
            );
        }

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

        foreach ($commands as $item) {
            $this->log($item['description'] . "..", 48);
            foreach ($item['command'] as $command) {
                $this->log("<comment>Executing</comment>: $command");
                system($command, $code);

                if (!$code) {
                    $this->log("SUCCESS", 64);
                } else {
                    throw new \Core\ScriptBundle\ScriptException("Operation failed");
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