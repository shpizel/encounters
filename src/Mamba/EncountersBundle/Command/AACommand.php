<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script;
use Mamba\EncountersBundle\Helpers\SearchPreferences;
use PDO;

/**
 * AACommand
 *
 * @package EncountersBundle
 */
class AACommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "AA script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "AA"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Redis = $this->getRedis();

        foreach ($Redis->hKeys(SearchPreferences::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY) as $userId) {
            $sql = "SELECT
                d.current_user_id
            FROM
                Encounters.Decisions d INNER JOIN Encounters.Decisions d2 on d.web_user_id = d2.current_user_id
            WHERE
                d.web_user_id = $userId and
                d.current_user_id = d2.web_user_id and
                d.decision >=0 and
                d2.decision >= 0
            ORDER BY
              d.changed DESC";

            $stmt = $this->getDoctrine()->getEntityManager()->getConnection()->prepare($sql);
            if ($stmt->execute()) {
                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $currentUserId = (int)$item['current_user_id'];
                    $webUserId = (int) $userId;

                    $this->getPurchasedObject()->add($webUserId, $currentUserId);
                    $this->getPurchasedObject()->add($currentUserId, $webUserId);
                }
            }
        }
    }
}