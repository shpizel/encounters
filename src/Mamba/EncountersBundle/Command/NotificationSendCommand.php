<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\CronScript;
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
        PERSONAL_MESSAGE = "%s, я отметил%s тебя в приложении «Выбиратор»!",

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

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_NOTIFICATIONS_SEND_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->sendNotifications($job);
            } catch (\Exception $e) {
                $class->log($e->getCode() . ": " . $e->getMessage(), 16);
                return;
            }
        });

        $this->log("Iterations: {$this->iterations}", 64);
        while ($worker->work() && --$this->iterations && !$this->getMemcache()->get("cron:stop")) {
            $this->log("Iterations: {$this->iterations}", 64);

            if ($worker->returnCode() != GEARMAN_SUCCESS) {
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
                if ($result = $Mamba->Contacts()->sendMessage($currentUserId, $message)) {
                    if (isset($result['sended']) && $result['sended']) {
                        $this->log('SUCCESS', 64);
                        $this->getStatsObject()->incr('contacts');
                    } else {
                        $this->log('FAILED', 16);
                    }
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

            if ($this->getMemcache()->add("notify_" . $currentUserId, time(), 3*3600)) {
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
        if ($visitors = $this->getCountersObject()->get($userId, 'visited')) {
            return sprintf(self::ACHIEVEMENT_MESSAGE, $this->getCountersObject()->get($userId, 'visited'));
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
            return sprintf(self::NOTIFY_MESSAGE, $name);
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
            $name = $anketas[0]['info']['name'];
            $name = explode(" ", $name);
            $name = array_shift($name);

            return sprintf(self::PERSONAL_MESSAGE, $name, $anketas[1]['info']['gender'] == 'F' ? 'а': '');
        }
    }
}