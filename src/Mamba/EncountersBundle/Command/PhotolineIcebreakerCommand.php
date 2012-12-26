<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;
use Mamba\EncountersBundle\Helpers\Photoline;

/**
 * PhotolineIcebreakerCommand
 *
 * @package EncountersBundle
 */
class PhotolineIcebreakerCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Photoline icebreaker",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:photoline:icebreaker",

        /**
         * Таймаут для включения айсбрекера
         *
         * @var int
         */
        PHOTOLINE_ICEBREAKER_TIMEOUT = 180
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
                    list(, $regionId) = explode("_", $photolineKey);

                    if ($regionId = intval($regionId)) {
                        $this->log("Checking {$photolineKey}..");
                        if ($items = $nodeConnection->zRange($photolineKey, 0, 0)) {
                            $item = array_shift($items);
                            $item = json_decode($item, true);

                            $lastmod = $item['microtime'];
                            $this->log("Last modified at " . date("Y-m-d H:i:s", (int) $lastmod));

                            if (time() - $lastmod > self::PHOTOLINE_ICEBREAKER_TIMEOUT) {

                                /**
                                 * Нужно найти пользователей которых неплохо бы открутить в мордоленте
                                 *
                                 * @author shpizel
                                 */
                                $this->log("Our client", 64);


                            } else {
                                $this->log("Photoline was updated at least than " . self::PHOTOLINE_ICEBREAKER_TIMEOUT . "s", 16);
                            }
                        } else {
                            $this->log("No items was found");
                        }
                    }
                }
            } else {
                $this->log("No photoline keys was found", 16);
            }


        }
    }
}