<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\QueueUpdateCronScript;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * NotificationSendCommand
 *
 * @package EncountersBundle
 */
class NotificationSendCommand extends QueueUpdateCronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Notifications sender"
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
        while ($worker->work() && --$this->iterations) {
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
        $Mamba->Contacts()->sendMessage($currentUserId, $this->getPersonalMessage());

        /**
         * Нужно проспамить в нотификацию, если возможно
         *
         * @author shpizel
         */
        $Mamba->Notify()->sendMessage($currentUserId, $this->getNotifyMessage());

        /**
         * Нужно проспамить на стену достижений
         *
         * @author
         */
        $Mamba->Achievement()->set($this->getAchievementText($webUserId));
    }

    /**
     * Генерирует и возвращает ачивку
     *
     * @param int $userId
     * @return str
     */
    private function getAchievement($userId) {

    }

    /**
     * Генерирует и возвращает сообщение от менеджера сообщений
     *
     * @return str
     */
    private function getNotifyMessage() {

    }

    /**
     * Генерирует и возвращает персональное сообщение
     *
     * @return str
     */
    private function getPersonalMessage() {

    }
}