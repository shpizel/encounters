<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;

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
        $worker = $this->getGearman()->getWorker();
        $worker->setTimeout(static::GEARMAN_WORKER_TIMEOUT);

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_ACHIEVEMENT_SET_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->sendNotifications($job);
            } catch (\Exception $e) {
                $class->log($e->getCode() . ": " . $e->getMessage(), 16);
                return;
            }
        });

        while
        (
            !$this->getMemcache()->get("cron:stop") &&
            ((time() - $this->started < $this->lifetime) || !$this->lifetime) &&
            ((memory_get_usage() < $this->memory) || !$this->memory) &&
            --$this->iterations &&
            (@$worker->work() || $worker->returnCode() == GEARMAN_TIMEOUT)
        ) {
            $this->log("Iterations: {$this->iterations}", 64);
            if ($worker->returnCode() == GEARMAN_TIMEOUT) {
                $this->log("Timed out", 48);
                continue;
            } elseif ($worker->returnCode() != GEARMAN_SUCCESS) {
                $this->log("Failed", 16);
                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log("Success", 64);
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
        if ($webUserId = (int) $webUserId) {
            $Mamba->set('oid', $webUserId);

            if (!$Mamba->getReady()) {
                $this->log("Mamba is not ready!", 16);
                return;
            }
        } else {
            throw new CronScriptException("Invalid workload");
        }

        if ($message = $this->getAchievement($webUserId)) {
            $this->log("Achievement spam message: $message");
            if ($result = $Mamba->Achievement()->set($message)) {
                if (isset($result['update']) && $result['update']) {
                    $this->log('SUCCESS', 64);
                    $this->getStatsObject()->incr('achievement');
                } else {
                    $this->log('FAILED', 16);
                }
            } else {
                $this->log('FAILED', 16);
            }
        } else {
            $this->log("Could not get achievement message", 16);
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
            $message = "Меня оценили $visitors человек";
            if ($mychoice && ($response = $this->getMamba()->Anketa()->getInfo($userId))) {

                if ($response[0]['info']['gender'] == 'F') {
                    $message.= ", я сама — $mychoice";
                } else {
                    $message.= ", я сам — $mychoice";
                }

                if ($mutual) {
                    $message.= " и у меня $mutual взаимных симпатий";
                }
            }

            $message.= "!";

            return $message;
        }
    }
}