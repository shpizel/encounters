<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Helpers\Declensions;

/**
 * AchievementSetCommand
 *
 * @package EncountersBundle
 */
class AchievementSetCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Achievement setter",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:achievement:set"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_ACHIEVEMENT_SET_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->sendNotifications($job);
            } catch (\Exception $e) {
                $class->log($e->getCode() . ": " . $e->getMessage(), 16);
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
     * Обновляет нотификации
     *
     * @param $job
     */
    public function sendNotifications($job) {
        $Mamba = $this->getMamba();
        $Redis = $this->getRedis();

        list($webUserId, $currentUserId, $decision) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>current_user_id</info> = {$currentUserId}, <info>web_user_id</info> = {$webUserId}, <info>decision</info> = {$decision}");

        if ($webUserId = (int) $webUserId) {
            $Mamba->set('oid', $webUserId);

            if (!$Mamba->getReady()) {
                throw new \Core\ScriptBundle\CronScriptException("Mamba is not ready!");
            }
        } else {
            throw new \Core\ScriptBundle\CronScriptException("Invalid workload");
        }

        if ($message = $this->getAchievement($webUserId)) {
            $this->log("<comment>Achievement spam message</comment>: $message");
            if ($result = $Mamba->Achievement()->set($message)) {
                if (isset($result['update']) && $result['update']) {
                    $this->log('Achievement was set successfully', 64);
                    $this->getStatsObject()->incr('achievement');
                } else {
                    throw new \Core\ScriptBundle\CronScriptException("Failed to set achievement");
                }
            } else {
                throw new \Core\ScriptBundle\CronScriptException("Failed to set achievement");
            }
        } else {
            throw new \Core\ScriptBundle\CronScriptException("Could not get achievement message");
        }
    }

    /**
     * Генерирует и возвращает ачивку
     *
     * @param int $userId
     * @return str
     */
    private function getAchievement($userId) {
        //Меня оценили %d человек, я сам%s — %d и у меня %d взаимных симпатий!

        $visitors = $this->getCountersObject()->get($userId, 'visitors');
        $mychoice = $this->getCountersObject()->get($userId, 'mychoice');
        $mutual   = $this->getCountersObject()->get($userId, 'mutual');

        if ($visitors) {
            $message = "Меня оценили $visitors " . Declensions::get($visitors, "человек", "человека", "человек");
            if ($mychoice && ($response = $this->getMamba()->Anketa()->getInfo($userId))) {

                if ($response[0]['info']['gender'] == 'F') {
                    $message.= ", я сама — $mychoice";
                } else {
                    $message.= ", я сам — $mychoice";
                }

                if ($mutual) {
                    $message.= " и у меня $mutual " . Declensions::get($mutual, "взаимная симпатия", "взаимные симпатии", "взаимных симпатий");
                }
            }

            $message.= "!";

            return $message;
        }
    }
}