<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * ContactsSendMessageCommand
 *
 * @package EncountersBundle
 */
class ContactsSendMessageCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Contacts message sender",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:contacts:sendmessage",

        /**
         * Сообщение в личку
         *
         * @var str
         */
        PERSONAL_MESSAGE = "%s отметил%s вас в приложении «Выбиратор», перейдите по ссылке и узнайте как! В «Выбираторе» очень удобно смотреть и оценивать пользователей, плюс — ваши фотографии тоже очень быстро получат множество просмотров и оценок ;)"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_CONTACTS_SEND_MESSAGE_FUNCTION_NAME, function($job) use($class) {
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

        $lockCacheKey = "personal_{$currentUserId}_spam";
        $lockCacheKeyExpire = 7*24*3600;

        if ($message = $this->getPersonalMessage($webUserId, $currentUserId)) {
            $this->log("<comment>Personal spam message</comment>: $message");
            if (($appUser = $Mamba->Anketa()->isAppUser($currentUserId)) && (!$appUser[0]['is_app_user'])) {
                if (!$this->getMemcache()->get($lockCacheKey)) {
                    if ($result = $Mamba->Contacts()->sendMessage($currentUserId, $message)) {
                        if (isset($result['sended']) && $result['sended']) {
                            $this->log('SUCCESS', 64);
                            $this->getStatsObject()->incr('contacts');
                            $this->getMemcache()->add($lockCacheKey, time(), $lockCacheKeyExpire);
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
        if ($webUser = $this->getMamba()->Anketa()->getInfo($webUserId)) {
            $webUser = array_shift($webUser);

            return sprintf(
                self::PERSONAL_MESSAGE,
                $webUser['info']['name'],
                $webUser['info']['gender'] == 'F' ? 'а': ''
            );
        }
    }
}