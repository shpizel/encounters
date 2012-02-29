<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * DatabaseImportCommand
 *
 * @package EncountersBundle
 */
class DatabaseImportCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Database import",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:import"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $id = 0;
        do {
            $this->log("Processing offset $id..", 64);
            $result = $this->getContainer()->get('doctrine')
                ->getEntityManager()
                ->createQuery("SELECT DISTINCT d FROM EncountersBundle:Decisions d WHERE d.id >= $id AND d.id < " . ($id = $id + 1000))
                ->getResult()
            ;

            foreach ($result as $item) {
                $webUserId = $item->getWebUserId();
                $this->getEnergyObject()->get($webUserId);

                if ($searchPreferences = $this->getSearchPreferencesObject()->get($webUserId)) {
                    if ($webUserAnketa = $this->getMamba()->Anketa()->getInfo($webUserId)) {
                        $this->getGearman()->getClient()->doHighBackground(
                            EncountersBundle::GEARMAN_DATABASE_USER_UPDATE_FUNCTION_NAME,
                            serialize(
                                array(
                                    'user_id'    => $webUserId,
                                    'gender'     => $webUserAnketa[0]['info']['gender'],
                                    'age'        => $webUserAnketa[0]['info']['age'],
                                    'country_id' => $searchPreferences['geo']['country_id'],
                                    'region_id'  => $searchPreferences['geo']['region_id'],
                                    'city_id'    => $searchPreferences['geo']['city_id'],
                                )
                            )
                        );
                    }
                }
            }
        } while ($result);
    }
}