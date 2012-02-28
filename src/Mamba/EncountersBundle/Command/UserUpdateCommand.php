<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;

use Mamba\EncountersBundle\Entity\User;

/**
 * UserUpdateCommand
 *
 * @package EncountersBundle
 */
class UserUpdateCommand extends CronScript {

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
        SCRIPT_NAME = "cron:database:user:update"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearman()->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_USER_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateUser($job);
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
     * Обновление таблицы энергий
     *
     * @param $job
     */
    public function updateUser($job) {
        list($userId, $gender, $age, $countryId, $regionId, $cityId) = array_values(unserialize($job->workload()));
        if ($User = $this->getEntityManager()->getRepository('EncountersBundle:User')->find($userId)) {
            $User->setGender($gender);
            $User->setAge($age);
            $User->setCountryId($countryId);
            $User->setRegionId($regionId);
            $User->setCityId($cityId);

            $this->getEntityManager()->flush();
        } else {
            $User = new User();
            $User->setUserId($userId);
            $User->setGender($gender);
            $User->setAge($age);
            $User->setCountryId($countryId);
            $User->setRegionId($regionId);
            $User->setCityId($cityId);

            $this->getEntityManager()->persist($User);
            $this->getEntityManager()->flush();
        }
    }
}