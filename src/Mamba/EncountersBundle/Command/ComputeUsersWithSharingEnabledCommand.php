<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\Script;
use Mamba\EncountersBundle\Helpers\SearchPreferences;

/**
 * ComputeUsersWithSharingEnabledCommand
 *
 * @package EncountersBundle
 */
class ComputeUsersWithSharingEnabledCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Compute users with sharing enabled script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "compute:sharing:enabled"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Redis = $this->getRedis();

        foreach ($Redis->getNodes() as $node) {
            $nodeConnection = $Redis->getNodeConnection($node);
            $keys = $nodeConnection->keys(
                sprintf(
                    str_replace("%d", "%s", SearchPreferences::REDIS_USER_SEARCH_PREFERENCES_KEY),
                    "*"
                )
            );

            $users = array_map(function($item) {
                return (int) substr($item, strlen(str_replace("%d", "", SearchPreferences::REDIS_USER_SEARCH_PREFERENCES_KEY)));
            }, $keys);

            foreach ($users as $userId) {
                $appUsers[] = $userId;
            }
        }

        $result = 0;

        $Variables = $this->getVariablesObject();
        foreach ($appUsers as $userId) {
            if ($sharingEnabled = $Variables->get($userId, 'sharing_enabled')) {
                if (1 == (int) $sharingEnabled) {
                    $result++;
                }
            }
        }

        $this->log($result);
    }
}