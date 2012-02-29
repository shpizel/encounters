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
        $result = $this->getContainer()->get('doctrine')
            ->getEntityManager()
            ->createQuery("SELECT d FROM EncountersBundle:Decisions d group by d.webUserId")
            ->getResult()
        ;

        $ids = array();
        foreach ($result as $item) {
            $ids[] = $item->getWebUserId();
        }
        $this->log(count($ids));

        unset($result);
        $_ids = array_chunk($ids, 100);
        foreach ($_ids as $k=>$chunk) {
            if ($result = $this->getMamba()->Anketa()->getInfo($chunk)) {
                foreach ($result as $user) {
                    if ($searchPreferences = $this->getSearchPreferencesObject()->get($webUserId = $user['info']['oid'])) {
                        $this->getGearman()->getClient()->doHighBackground(
                            EncountersBundle::GEARMAN_DATABASE_USER_UPDATE_FUNCTION_NAME,
                            serialize(
                                array(
                                    'user_id'    => $webUserId,
                                    'gender'     => $user['info']['gender'],
                                    'age'        => $user['info']['age'],
                                    'country_id' => $searchPreferences['geo']['country_id'],
                                    'region_id'  => $searchPreferences['geo']['region_id'],
                                    'city_id'    => $searchPreferences['geo']['city_id'],
                                )
                            )
                        );
                    }
                }
            }

            unset($result);
            $this->log(($k+1)*100);
        }
    }
}