<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;
use Mamba\EncountersBundle\Helpers\Photoline;
use PDO;

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
        PHOTOLINE_ICEBREAKER_TIMEOUT = 5,

        SQL = "
            SELECT
                u.user_id as user_id
            FROM
                `User` u
            INNER JOIN
                `Energy` e
            ON
                u.user_id = e.user_id AND
                u.region_id = :region_id AND
                e.energy = 0 AND
                u.orientation = 1
        "
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

            if (!($photolineKeys = $this->getMemcache()->get('photoline-keys'))) {
                $photolineKeys = $nodeConnection->keys(str_replace("%d", "*", Photoline::REDIS_PHOTOLINE_KEY));
                $this->getMemcache()->set('photoline-keys', $photolineKeys, 3600);
            }

            if ($photolineKeys) {
                $this->log(count($photolineKeys) . " photoline keys was found", 48);

                shuffle($photolineKeys);
                foreach ($photolineKeys as $photolineKey) {
                    list(, $regionId) = explode("_", $photolineKey);

                    if ($this->getMemcache()->add("photoline-icebreaker-parser-{$regionId}", 1, 5*60)) {
                        if (!$Redis->lLen($this->getUsersCacheKey($regionId))) {
                            //список пустой, нужно его заполнить
                            $this->log("Empty cache at regionId = {$regionId}, regenerating..", 64);

                            $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL);
                            $stmt->bindParam('region_id', $regionId);

                            if ($result = $stmt->execute()) {
                                $this->log("SQL query OK", 64);

                                $counter = 0;
                                $users = array();
                                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $users[] = (int) $item['user_id'];
                                }

                                $users = array_chunk($users, 100);
                                foreach ($users as $chunk) {
                                    if ($data = $this->getMamba()->Anketa()->getInfo($chunk)) {
                                        foreach ($data as $dataChunk) {
                                            if ($dataChunk['info']['is_app_user']) {
                                                $Redis->lPush($this->getUsersCacheKey($regionId), (int) $dataChunk['info']['oid']);
                                                $counter++;
                                            }
                                        }
                                    }
                                }

                                $this->log("{$counter} users added");
                            } else {
                                $this->log("SQL query failed", 16);
                            }
                        }

                        $this->getMemcache()->delete("photoline-icebreaker-parser-{$regionId}");
                    } else {
                        $this->log("Locked");
                    }

                    $lastmod = 0;
                    if ($regionId = intval($regionId)) {
                        $this->log("Checking {$photolineKey}..");
//                        if ($items = $nodeConnection->zRange($photolineKey, 0, 0)) {
//                            $item = array_shift($items);
//                            $item = json_decode($item, true);
//
//                            $lastmod = $item['microtime'];
//                        }
                        $lastmod = (int) $this->getMemcache()->get("photoline_{$regionId}_updated");

                        $this->log("Last modified at " . date("Y-m-d H:i:s", (int) $lastmod));
                        if (time() - $lastmod > self::PHOTOLINE_ICEBREAKER_TIMEOUT) {

                            if ($userId = (int) $Redis->lPop($this->getUsersCacheKey($regionId))) {
                                $this->getPhotolineObject()->add($regionId, $userId, null, true);
                                $this->log($userId . " was added to {$regionId} photoline", 64);
                            } else {
                                $this->log("Users was not found :(");
                            }
                        } else {
                            $this->log("Photoline was updated at least than " . self::PHOTOLINE_ICEBREAKER_TIMEOUT . "s", 16);
                        }
                    }
                }
            } else {
                $this->log("No photoline keys was found", 16);
            }
        }
    }

    private function getUsersCacheKey($regionId) {
        return "photoline-icebreaker-{$regionId}";
    }
}