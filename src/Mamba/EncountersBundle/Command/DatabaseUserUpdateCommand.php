<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\CronScript;
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
                `user_id`    = :user_id,
                `gender`     = :gender,
                `age`        = :age,
                `country_id` = :country_id,
                `region_id`  = :region_id,
                `city_id`    = :city_id
            ON DUPLICATE KEY UPDATE
                `gender`     = :gender,
                `age`        = :age,
                `country_id` = :country_id,
                `region_id`  = :region_id,
                `city_id`    = :city_id
        "
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
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_USER_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateUser($job);
            } catch (\Exception $e) {
                $class->log($e->getCode() . ": " . $e->getMessage(), 16);
                throw $e;
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
     * Обновление таблицы энергий
     *
     * @param $job
     */
    public function updateUser($job) {
        list($userId, $gender, $age, $countryId, $regionId, $cityId) = array_values(unserialize($job->workload()));

        $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_USER_UPDATE);
        $stmt->bindValue('user_id', $userId);
        $stmt->bindValue('gender', $gender);
        $stmt->bindValue('age', $age);
        $stmt->bindValue('country_id', $countryId);
        $stmt->bindValue('region_id', $regionId);
        $stmt->bindValue('city_id', $cityId);

        $result = $stmt->execute();
        if (!$result) {
            throw new CronScriptException('Unable to store data to DB.');
        }
    }
}