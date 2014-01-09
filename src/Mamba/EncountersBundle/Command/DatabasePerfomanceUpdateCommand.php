<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;
use PDO;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabasePerfomanceUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabasePerfomanceUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Update perfomance",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:perfomance:update"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_PERFOMANCE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            return $class->updateUserTrafficSources($job);
        });

        $this->insertStatement = $this->getEntityManager()->getConnection()->prepare("
            INSERT INTO
                `Encounters`.`Perfomance`
            SET
                `route` = :route,
                `generation_time` = :generation_time,
                `requested_time` = FROM_UNIXTIME(:requested_time),

                `mysql_requests_count` = :mysql_requests_count,
                `mysql_timeout` = :mysql_timeout,
                `mysql_requests` = :mysql_requests,

                `redis_requests_count` = :redis_requests_count,
                `redis_timeout` = :redis_timeout,
                `redis_requests` = :redis_requests,

                `leveldb_requests_count` = :leveldb_requests_count,
                `leveldb_timeout` = :leveldb_timeout,
                `leveldb_requests` = :leveldb_requests,

                `mamba_requests_count` = :mamba_requests_count,
                `mamba_timeout` = :mamba_timeout,
                `mamba_requests` = :mamba_requests
        ");

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
     * Обновление таблицы Lastaccess
     *
     * @param $job
     */
    public function updateUserTrafficSources($job) {
        $dataArray = unserialize($job->workload());

        $this->log("Got task for <info>{$dataArray['route']}</info> at " . date("d-M-Y H:i:s", $dataArray['time']));

        list(
            $route,
            $generationTime,

            $mysqlRequestsCount,
            $mysqlTimeout,
            $mysqlResuests,

            $redisRequestsCount,
            $redisTimeout,
            $redisResuests,

            $leveldbRequestsCount,
            $leveldbTimeout,
            $leveldbResuests,

            $mambaRequestsCount,
            $mambaTimeout,
            $mambaResuests,

            $requestedTime
        ) = [
            $dataArray['route'],
            $dataArray['generation_time'],

            $dataArray['mysql_requests_count'],
            $dataArray['mysql_timeout'],
            json_encode($dataArray['mysql_requests'], JSON_PRETTY_PRINT),

            $dataArray['redis_requests_count'],
            $dataArray['redis_timeout'],
            json_encode($dataArray['redis_requests'], JSON_PRETTY_PRINT),

            $dataArray['leveldb_requests_count'],
            $dataArray['leveldb_timeout'],
            json_encode($dataArray['leveldb_requests'], JSON_PRETTY_PRINT),

            $dataArray['mamba_requests_count'],
            $dataArray['mamba_timeout'],
            json_encode($dataArray['mamba_requests'], JSON_PRETTY_PRINT),

            $dataArray['time']
        ];

        $this->insertStatement->bindValue('route', $route, PDO::PARAM_STR);
        $this->insertStatement->bindValue('generation_time', $generationTime, PDO::PARAM_INT);
        $this->insertStatement->bindValue('requested_time', $requestedTime, PDO::PARAM_INT);

        $this->insertStatement->bindValue('mysql_requests_count', $mysqlRequestsCount, PDO::PARAM_INT);
        $this->insertStatement->bindValue('mysql_timeout', $mysqlTimeout, PDO::PARAM_INT);
        $this->insertStatement->bindValue('mysql_requests', $mysqlResuests, PDO::PARAM_LOB);

        $this->insertStatement->bindValue('redis_requests_count', $redisRequestsCount, PDO::PARAM_INT);
        $this->insertStatement->bindValue('redis_timeout', $redisTimeout, PDO::PARAM_INT);
        $this->insertStatement->bindValue('redis_requests', $redisResuests, PDO::PARAM_LOB);

        $this->insertStatement->bindValue('leveldb_requests_count', $leveldbRequestsCount, PDO::PARAM_INT);
        $this->insertStatement->bindValue('leveldb_timeout', $leveldbTimeout, PDO::PARAM_INT);
        $this->insertStatement->bindValue('leveldb_requests', $leveldbResuests, PDO::PARAM_LOB);

        $this->insertStatement->bindValue('mamba_requests_count', $mambaRequestsCount, PDO::PARAM_INT);
        $this->insertStatement->bindValue('mamba_timeout', $mambaTimeout, PDO::PARAM_INT);
        $this->insertStatement->bindValue('mamba_requests', $mambaResuests, PDO::PARAM_LOB);

        if (!($result = $this->insertStatement->execute())) {
            throw new \Core\ScriptBundle\CronScriptException('Unable to store data to Perfomance');
        }
    }
}