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
        PHOTOLINE_ICEBREAKER_TIMEOUT = 180,

        SQL = "
            SELECT
                u.user_id as user_id
            FROM
                `User` u
            INNER JOIN
                Energy e
            ON
                u.user_id = e.user_id AND
                u.region_id = :region_id AND
                e.energy = 0 AND
                u.orientation = 1
            ORDER BY
                rand()
            LIMIT
                10
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
            if ($photolineKeys = $nodeConnection->keys(str_replace("%d", "*", Photoline::REDIS_PHOTOLINE_KEY))) {
                $this->log(count($photolineKeys) . " photoline keys was found", 48);

                shuffle($photolineKeys);
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
                                $this->log("Fetching user for icebreak", 64);

                                $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL);
                                $stmt->bindParam('region_id', $regionId);

                                if ($result = $stmt->execute()) {
                                    $this->log("SQL query OK", 64);

                                    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $userId = (int) $item['user_id'];

                                        if ($mambaUserInfo = $this->getMamba()->Anketa()->getInfo($userId)) {
                                            $this->getPhotolineObject()->add($regionId, $userId);

                                            $this->log($userId . " was added to {$regionId} photoline", 64);
                                            break;
                                        }
                                    }
                                } else {
                                    $this->log("SQL query failed", 16);
                                }
                            } else {
                                $this->log("Photoline was updated at least than " . self::PHOTOLINE_ICEBREAKER_TIMEOUT . "s", 16);
                            }
                        } else {
                            /**
                             * Нужно найти пользователей которых неплохо бы открутить в мордоленте
                             *
                             * @author shpizel
                             */
                            $this->log("Fetching user for icebreak", 64);

                            $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL);
                            $stmt->bindParam('region_id', $regionId);

                            if ($result = $stmt->execute()) {
                                $this->log("SQL query OK", 64);

                                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $userId = (int) $item['user_id'];

                                    if ($mambaUserInfo = $this->getMamba()->Anketa()->getInfo($userId)) {
                                        $this->getPhotolineObject()->add($regionId, $userId);

                                        $this->log($userId . " was added to {$regionId} photoline", 64);
                                        break;
                                    }
                                }
                            } else {
                                $this->log("SQL query failed", 16);
                            }
                        }
                    }
                }
            } else {
                $this->log("No photoline keys was found", 16);
            }
        }
    }
}