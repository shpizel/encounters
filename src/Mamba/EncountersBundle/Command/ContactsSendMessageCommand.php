<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\CronScript;
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
        $worker = $this->getGearman()->getWorker();
        $worker->setTimeout(static::GEARMAN_WORKER_TIMEOUT);

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_CONTACTS_SEND_MESSAGE_FUNCTION_NAME, function($job) use($class) {
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

        $this->log("Current user id: " . $currentUserId);

        $lockCacheKey = "personal_{$currentUserId}_spam";
        $lockCacheKeyExpire = 7*24*3600;

        if ($message = $this->getPersonalMessage($webUserId, $currentUserId)) {
            $this->log("Personal spam message: $message");
            if (($appUser = $Mamba->Anketa()->isAppUser($currentUserId)) && (!$appUser[0]['is_app_user'])) {
                if (!$this->getMemcache()->get($lockCacheKey)) {
                    if ($result = $Mamba->Contacts()->sendMessage($currentUserId, $message)) {
                        if (isset($result['sended']) && $result['sended']) {
                            $this->log('SUCCESS', 64);
                            $this->getStatsObject()->incr('contacts');
                            $this->getMemcache()->add($lockCacheKey, time(), $lockCacheKeyExpire);
                        } else {
                            $this->log('FAILED', 16);
                        }
                    } else {
                        $this->log('FAILED', 16);
                    }
                } else {
                    $this->log("Too much frequently", 16);
                }
            } else {
                $this->log("Already app user", 16);
            }
        } else {
            $this->log("Could not get personal message", 16);
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