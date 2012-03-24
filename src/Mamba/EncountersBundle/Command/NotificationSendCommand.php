<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\CronScript;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * NotificationSendCommand
 *
 * @package EncountersBundle
 */
class NotificationSendCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Notifications sender",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:notification:send",

        /**
         * Ачивка
         *
         * @var str
         */
        ACHIEVEMENT_MESSAGE = "Ничего себе! Меня оценили уже %d человек :)",

        /**
         * Сообщение в личку
         *
         * @var str
         */
        PERSONAL_MESSAGE = "%s отметил%s вас в приложении «Выбиратор», перейдите по ссылке и узнайте как! В «Выбираторе» очень удобно смотреть и оценивать пользователей, плюс — ваши фотографии тоже очень быстро получат множество просмотров и оценок ;)",

        /**
         * Сообщение от менеджера приложений
         *
         * @var str
         */
        NOTIFY_MESSAGE = "%s, у тебя есть новые оценки в приложении «Выбиратор»!"
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
        $worker->addFunction(EncountersBundle::GEARMAN_NOTIFICATIONS_SEND_FUNCTION_NAME, function($job) use($class) {
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
                $this->log("Success", 16);
                break;
            }
        }
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

        /**
         * Нужно проспамить в личку, если возможно
         *
         * @author shpizel
         */
        $this->log("Current user id: " . $currentUserId);
        if ($message = $this->getPersonalMessage($webUserId, $currentUserId)) {
            $this->log("Personal spam message: " . ($message));

            if (($appUser = $Mamba->Anketa()->isAppUser($currentUserId)) && (!$appUser[0]['is_app_user'])) {
                if ($this->getMemcache()->add("personal_" . $currentUserId . "_spam", time(), 7*24*3600)) {

                if ($result = $Mamba->Contacts()->sendMessage($currentUserId, $message)) {
                    if (isset($result['sended']) && $result['sended']) {
                        $this->log('SUCCESS', 64);
                        $this->getStatsObject()->incr('contacts');
                    } else {
                        $this->log('FAILED', 16);
                    }
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


        /**
         * Нужно проспамить в нотификацию, если возможно
         *
         * @author shpizel
         */
        $this->log("Current user id: " . $currentUserId);
        if ($message = $this->getNotifyMessage($currentUserId)) {
            $this->log("Notify spam message: " . ($message));

            if ($this->getMemcache()->add("notify_" . $currentUserId, time(), 5*3600)) {
                if ($result = $Mamba->Notify()->sendMessage($currentUserId, $message)) {
                    if (isset($result['count']) && $result['count']) {
                        $this->log('SUCCESS', 64);
                        $this->getStatsObject()->incr('notify');

                    } else {
                        $this->log('FAILED', 16);
                    }
                }
            } else {
                $this->log("Too much frequently", 16);
            }
        } else {
            $this->log("Could not get notify message", 16);
        }

        /**
         * Нужно проспамить на стену достижений
         *
         * @author
         */
        if ($message = $this->getAchievement($webUserId)) {
            $this->log("Achievement spam message: " . ($message));
            if ($result = $Mamba->Achievement()->set($message)) {
                if (isset($result['update']) && $result['update']) {
                    $this->log('SUCCESS', 64);
                    $this->getStatsObject()->incr('achievement');
                } else {
                    $this->log('FAILED', 16);
                }
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

    /**
     * Генерирует и возвращает сообщение от менеджера сообщений для карент юзера
     *
     * @param int $currentUserId
     * @return str
     */
    private function getNotifyMessage($currentUserId) {
        if ($anketa = $this->getMamba()->Anketa()->getInfo($currentUserId)) {
            $name = $anketa[0]['info']['name'];
            $name = explode(" ", $name);
            $name = array_shift($name);

            $visitorsUnread = (int) $this->getCountersObject()->get($currentUserId, 'visitors_unread');
            $mutualUnread   = (int) $this->getCountersObject()->get($currentUserId, 'mutual_unread');

            if ($visitorsUnread && !$mutualUnread) {
                if ($visitorsUnread == 1) {
                    $message = "$name, у вас появилась новая оценка в приложении «Выбиратор»!";
                } else {
                    $message = "$name, у вас $visitorsUnread новых оценок в приложении «Выбиратор»!";
                }
            } elseif ($mutualUnread && !$visitorsUnread) {
                $message = "$name, вам ответили взаимностью в приложении «Выбиратор»!";
            } elseif ($mutualUnread && $visitorsUnread) {
                $message = "$name, у вас $visitorsUnread новых оценок и $mutualUnread взаимных симпатии в приложении «Выбиратор»!";
            }

            if (isset($message)) {
                return $message;
            }
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
        if ($anketas = $this->getMamba()->Anketa()->getInfo(array($currentUserId, $webUserId))) {
            $name = $anketas[1]['info']['name'];
            $name = explode(" ", $name);
            $name = array_shift($name);

            return sprintf(self::PERSONAL_MESSAGE, $name, $anketas[1]['info']['gender'] == 'F' ? 'а': '');
        }
    }
}