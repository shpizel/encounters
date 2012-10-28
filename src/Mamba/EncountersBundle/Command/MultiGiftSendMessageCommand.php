<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * MultiGiftSendMessageCommand
 *
 * @package EncountersBundle
 */
class MultiGiftSendMessageCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Multi gift message sender",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:multigift:sendmessage",

        /**
         * Сообщение в личку
         *
         * @var str
         */
        PERSONAL_MESSAGE = "%s, заходи в приложение «Выбиратор»! Тут очень удобно смотреть и оценивать пользователей, плюс — свои собственные фотографии тоже очень быстро получают множество просмотров и оценок ;)"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_CONTACTS_MULTI_GIFT_SEND_MESSAGE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->sendNotifications($job);
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
     * Обновляет нотификации
     *
     * @param $job
     */
    public function sendNotifications($job) {
        $Mamba = $this->getMamba();
        $Redis = $this->getRedis();

        list($webUserId, $currentUserId) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>current_user_id</info> = {$currentUserId}, <info>web_user_id</info> = {$webUserId}");

        if ($webUserId = (int) $webUserId) {
            $Mamba->set('oid', $webUserId);

            if (!$Mamba->getReady()) {
                throw new \Core\ScriptBundle\CronScriptException("Mamba is not ready!");
            }
        } else {
            throw new \Core\ScriptBundle\CronScriptException("Invalid workload");
        }

        if ($message = $this->getPersonalMessage($webUserId, $currentUserId)) {
            $this->log("<comment>Personal spam message</comment>: $message");
            if (($appUser = $Mamba->Anketa()->isAppUser($currentUserId)) && (!$appUser[0]['is_app_user'])) {
                if (!$this->getVariablesObject()->get($currentUserId, 'last_message_sent')) {
                    if ($result = $Mamba->Contacts()->sendMessage($currentUserId, $message)) {
                        if (isset($result['sended']) && $result['sended']) {
                            $this->log('SUCCESS', 64);
                            $this->getStatsObject()->incr('contacts');
                            $this->getVariablesObject()->set($currentUserId, 'last_message_sent', time());
                        } else {
                            throw new \Core\ScriptBundle\CronScriptException("Failed to send message");
                        }
                    } else {
                        throw new \Core\ScriptBundle\CronScriptException("Failed to send message");
                    }
                } else {
                    throw new \Core\ScriptBundle\CronScriptException("Too much frequently");
                }
            } else {
                throw new \Core\ScriptBundle\CronScriptException("Already app user");
            }
        } else {
            throw new \Core\ScriptBundle\CronScriptException("Could not get personal message");
        }
    }

    /**
     * Генерирует и возвращает персональное сообщение
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @return str
     */
    private function getPersonalMessage($webUserId, $currentUserId) {
        if ($webUser = $this->getMamba()->Anketa()->getInfo($currentUserId)) {
            $webUser = array_shift($webUser);

            return sprintf(
                self::PERSONAL_MESSAGE,
                $webUser['info']['name']
            );
        }
    }
}