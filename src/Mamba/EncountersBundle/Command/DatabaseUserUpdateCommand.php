<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseUserUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseUserUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Updates users",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:user:update",

        /**
         * SQL-запрос обновления таблицы юзеров
         *
         * @var str
         */
        SQL_USER_UPDATE = "
            INSERT INTO
                Encounters.User
            SET
                `user_id`     = :user_id,
                `gender`      = :gender,
                `orientation` = :orientation,
                `age`         = :age,
                `country_id`  = :country_id,
                `region_id`   = :region_id,
                `city_id`     = :city_id
            ON DUPLICATE KEY UPDATE
                `gender`      = :gender,
                `orientation` = :orientation,
                `age`         = :age,
                `country_id`  = :country_id,
                `region_id`   = :region_id,
                `city_id`     = :city_id
        "
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_USER_UPDATE_FUNCTION_NAME, function($job) use($class) {
            return $class->updateUser($job);
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
     * Обновление таблицы энергий
     *
     * @param $job
     */
    public function updateUser($job) {
        list($userId, $gender, $orientation, $age, $countryId, $regionId, $cityId) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>current_user_id</info> = {$userId}");

        $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_USER_UPDATE);
        $stmt->bindValue('user_id', $userId);
        $stmt->bindValue('gender', $gender);
        $stmt->bindValue('orientation', $orientation);
        $stmt->bindValue('age', $age);
        $stmt->bindValue('country_id', $countryId);
        $stmt->bindValue('region_id', $regionId);
        $stmt->bindValue('city_id', $cityId);

        $result = $stmt->execute();
        if (!$result) {
            throw new \Core\ScriptBundle\CronScriptException('Unable to store data to DB.');
        }
    }
}