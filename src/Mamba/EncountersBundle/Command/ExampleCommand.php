<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\CronScript;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * ExampleCommand
 *
 * @package EncountersBundle
 */
class ExampleCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Example script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "example",

        FULL_SQL = "
            SELECT
                u.user_id, e.energy
            FROM
                Encounters.User u
            INNER JOIN
                Encounters.Energy e
            ON
                e.user_id = u.user_id
            WHERE
                u.gender = ? AND
                (u.age = 0 OR (u.age >= ? AND u.age <= ?)) AND

                u.country_id = ? AND
                u.region_id = ? AND
                u.city_id = ?
        ",

        SHORT_SQL = "
            SELECT
                u.user_id, e.energy
            FROM
                Encounters.User u
            INNER JOIN
                Encounters.Energy e
            ON
                e.user_id = u.user_id
            WHERE
                u.gender = ? AND
                (u.age = 0 OR (u.age >= ? AND u.age <= ?))
        "
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        if ($searchPreferences = $this->getSearchPreferencesObject()->get(560015854)) {

            $rsm = new ResultSetMapping;
            $rsm->addScalarResult('user_id', 'user_id');
            $rsm->addScalarResult('energy', 'energy');
            $query = $this->getDoctrine()->getEntityManager()->createNativeQuery(self::FULL_SQL, $rsm);
            $query->setParameter(1, $searchPreferences['gender']);
            $query->setParameter(2, $searchPreferences['age_from']);
            $query->setParameter(3, $searchPreferences['age_to']);
            $query->setParameter(4, $searchPreferences['geo']['country_id']);
            $query->setParameter(5, $searchPreferences['geo']['region_id']);
            $query->setParameter(6, $searchPreferences['geo']['city_id']);
            $result = $query->getResult();

            var_dump($result);
        }
    }
}