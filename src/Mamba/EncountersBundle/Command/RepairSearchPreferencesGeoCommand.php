<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Script\CronScript;
use Mamba\EncountersBundle\EncountersBundle;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * RepairSearchPreferencesGeoCommand
 *
 * @package EncountersBundle
 */
class RepairSearchPreferencesGeoCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Repair geo on users search preferences",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:repair:geo"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        foreach ($this->getRedis()->hKeys("users_search_preferences") as $userId) {
            if ($apiResponse = $this->getMamba()->Anketa()->getInfo($userId)) {
                $anketa = $apiResponse[0];

                if ($searchPreferences = $this->getSearchPreferencesHelper()->get($userId)) {
                    $searchPreferences['geo']['country_id'] = isset($anketa['location']['country_id']) ? $anketa['location']['country_id'] : null;
                    $searchPreferences['geo']['region_id'] = isset($anketa['location']['region_id']) ? $anketa['location']['region_id'] : null;
                    $searchPreferences['geo']['city_id'] = isset($anketa['location']['city_id']) ? $anketa['location']['city_id'] : null;
                    $searchPreferences['changed'] = time();

                    $this->getSearchPreferencesHelper()->set($userId, $searchPreferences);

                    $this->getGearmanClient()->doHighBackground(
                        EncountersBundle::GEARMAN_DATABASE_USERS_UPDATE_FUNCTION_NAME,
                        serialize(
                            array(
                                'users' => [$userId],
                                'time'  => time(),
                            )
                        )
                    );
                }
            }
        }
    }
}