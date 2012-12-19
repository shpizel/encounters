<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Helpers\Declensions;

/**
 * PhotolineUpdateCommand
 *
 * @package EncountersBundle
 */
class PhotolineUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Photoline updater",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:photoline:update"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_PHOTOLINE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updatePhotoline($job);
            } catch (\Exception $e) {
                $class->log("Error: " . static::SCRIPT_NAME . ":" . $e->getCode() . " " . $e->getMessage(), 16);
                return;
            }
        });

        $iterations = $this->iterations;
        while
        (
            (!$this->getMemcache()->get("cron:stop") || (($stopCommandTimeStamp = (int) $this->getMemcache()->get("cron:stop")) && ($stopCommandTimeStamp < $this->started))) &&
            ((time() - $this->started < $this->lifetime) || !$this->lifetime) &&
            filemtime(__FILE__) < $this->started &&
            ((memory_get_usage() < $this->memory) || !$this->memory) &&
            $this->iterations-- &&
            $this->log(($iterations - $this->iterations) . " iteration:", 48) &&
            (@$worker->work() || $worker->returnCode() == GEARMAN_TIMEOUT)
        ) {
            if ($worker->returnCode() == GEARMAN_TIMEOUT) {
                $this->log("Timed out", 48);
                continue;
            } elseif ($worker->returnCode() != GEARMAN_SUCCESS) {
                $this->log("Failed", 16);
                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log("Completed", 64);
            }
        }

        $this->log("Bye", 48);
    }

    /**
     * Обновляет фотолинейку
     *
     * @param $job
     */
    public function sendNotifications($job) {
        $Mamba = $this->getMamba();
        $Redis = $this->getRedis();

        list($currentUserId, $time, $delay) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>current_user_id</info> = {$currentUserId}, <info>time</info> = {$time}, <info>delay</info> = {$delay}");

        $currentTimestamp = time();
        $photolineTimestamp = $time + $delay;

        if ($currentTimestamp < $photolineTimestamp) {
            $sleep = $photolineTimestamp - $currentTimestamp;
            $this->log("Sleeping for {$sleep}");
            sleep($sleep);
        }

        $currentUser = $this->getMamba()->Anketa()->getInfo($currentUserId);

        $this->getPhotolineObject()->add($currentUser[0]['location']['region_id'], $currentUserId);
    }
}