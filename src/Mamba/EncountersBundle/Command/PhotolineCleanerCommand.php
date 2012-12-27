<?php
namespace Mamba\EncountersBundle\Command;

use Core\RedisBundle\Redis;
use Mamba\EncountersBundle\Script\CronScript;

/**
 * PhotolineCleanerCommand
 *
 * @package EncountersBundle
 */
class PhotolineCleanerCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Photoline cleaner",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:photoline:cleaner",

        PHOTOLINE_MAX_SIZE = 100
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Redis = $this->getRedis();

        foreach ($Redis->getNodes() as $node) {
            $this->log("Fetching \"" . $node . "\"..", 32);

            $nodeConnection = $Redis->getNodeConnection($node);
            if ($photolineKeys = $nodeConnection->keys(str_replace("%d", "*", Photoline::REDIS_PHOTOLINE_KEY))) {
                $this->log(count($photolineKeys) . " photoline keys was found", 48);

                foreach ($photolineKeys as $photolineKey) {
                    if ($items = $nodeConnection->zRange($photolineKey, self::PHOTOLINE_MAX_SIZE, -1)) {
                        $this->log(count($items) . " was found and will be deleted");

                        foreach ($items as $item) {
                            $nodeConnection->zRem($photolineKey, $item);
                        }

                        $this->log($nodeConnection->zCard($photolineKey), 32);
                    }
                }
            }
        }
    }
}