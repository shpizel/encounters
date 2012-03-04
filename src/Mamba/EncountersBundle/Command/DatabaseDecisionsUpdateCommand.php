<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;

use Mamba\EncountersBundle\Entity\Decisions;

/**
 * DatabaseDecisionsUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseDecisionsUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Sync decisions with database",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:decisions:update",

        /**
         * SQL-запрос для добавления ответа в базу
         *
         * @var str
         */
        SQL_UPDATE_DECISION = "
            INSERT INTO
                Encounters.Decisions
            SET
                `web_user_id` = :web_user_id,
                `current_user_id` = :current_user_id,
                `decision` = :decision,
                `changed` = :changed
            ON DUPLICATE KEY UPDATE
                `decision` = :decision,
                `changed` = :changed
        "
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearman()->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_DECISIONS_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->processDecisions($job);
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
     * Обновляет базу данных
     *
     * @param $job
     */
    public function processDecisions($job) {
        list($webUserId, $currentUserId, $decision) = array_values(unserialize($job->workload()));

        $time = time();

        $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_UPDATE_DECISION);
        $stmt->bindParam('web_user_id', $webUserId);
        $stmt->bindParam('current_user_id', $currentUserId);
        $stmt->bindParam('decision', $decision);
        $stmt->bindParam('changed', $time);

        $this->log(var_export($stmt->execute(), true));
    }
}