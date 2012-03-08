<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\Script;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * AACommand
 *
 * @package EncountersBundle
 */
class RedisSyncCommand extends Script
{
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
        SCRIPT_NAME = "redis:sync"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process()
    {
        for ($i=0; $i < 10; ++$i) {
            $keys = $this->getRedis()->keys("user_{$i}*_viewed_queue");
            foreach ($keys as $key) {
                $webUserId = substr($key, 5, strpos($key, '_viewed_queue'));

                $uids = $this->getRedis()->hKeys($key);
                foreach ($uids as $currentUserId) {
                    $data = $this->getRedis()->hGet($key, $uid);
                    if ($data) {
                        $data = json_decode($data, true);
                        $dataArray = array(
                            'webUserId'     => (int) $webUserId,
                            'currentUserId' => (int) $currentUserId,
                            'decision'      => $data['decision'],
                            'time'          => $data['ts'],
                        );
                        $this->getGearman()->getClient()->doLowBackground(EncountersBundle::GEARMAN_DATABASE_DECISIONS_UPDATE_FUNCTION_NAME, serialize($dataArray));
                    }
                }
            }
        }
    }
}