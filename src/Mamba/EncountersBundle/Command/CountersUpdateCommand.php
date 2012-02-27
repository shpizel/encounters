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
 * CountersUpdateCommand
 *
 * @package EncountersBundle
 */
class CountersUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "User menu counters update",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:counters:update",

        MUTUAL_SQL = "
            SELECT
              d.web_user_id, count(*) as `counter`
            FROM
              Encounters.Decisions d INNER JOIN Encounters.Decisions d2 on d.web_user_id = d2.current_user_id
            WHERE
              d.current_user_id = d2.web_user_id and
              d.decision >=0 and
              d2.decision >= 0
            GROUP BY
              d.web_user_id
            ORDER BY
              `counter`
        ",

        MYCHOICE_SQL = "
            SELECT
              d.web_user_id, count(*) as `counter`
            FROM
              Encounters.Decisions d
            GROUP BY
              d.web_user_id
            ORDER BY
              `counter`
        ",

        VISITORS_SQL = "select distinct current_user_id as `web_user_id`, count(*) as `counter` from Encounters.Decisions where current_user_id in (select distinct web_user_id from Encounters.Decisions) group by current_user_id order by `counter`;"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        /**
         * Я выбрал(а)
         *
         * @author shpizel
         */
        $this->log("Processing visitors..", 64);
        $rsm = new ResultSetMapping;
        $rsm->addScalarResult('web_user_id', 'web_user_id');
        $rsm->addScalarResult('counter', 'counter');
        $result = $this->getContainer()->get('doctrine')->getEntityManager()->createNativeQuery(self::VISITORS_SQL, $rsm)->getResult();

        $i = 0;
        foreach ($result as $item) {
            $this->getCountersObject()->set((int) $item['web_user_id'], 'visitors', (int) $item['counter']);
            $this->getCountersObject()->set((int) $item['web_user_id'], 'visited', (int) $item['counter']);
        }
        $this->log("OK (" . count($result) . ")", 64);

        /**
         * Меня смотрели
         *
         * @author shpizel
         */
        $this->log("Processing my choice..", 64);
        $rsm = new ResultSetMapping;
        $rsm->addScalarResult('web_user_id', 'web_user_id');
        $rsm->addScalarResult('counter', 'counter');
        $result = $this->getContainer()->get('doctrine')->getEntityManager()->createNativeQuery(self::MYCHOICE_SQL, $rsm)->getResult();

        foreach ($result as $item) {
            $this->getCountersObject()->set((int) $item['web_user_id'], 'mychoice', (int) $item['counter']);
        }
        $this->log("OK (" . count($result) . ")", 64);

        /**
         * Взаимные
         *
         * @author shpizel
         */
        $this->log("Processing mutual..", 64);
        $rsm = new ResultSetMapping;
        $rsm->addScalarResult('web_user_id', 'web_user_id');
        $rsm->addScalarResult('counter', 'counter');
        $result = $this->getContainer()->get('doctrine')->getEntityManager()->createNativeQuery(self::MUTUAL_SQL, $rsm)->getResult();

        foreach ($result as $item) {
            $this->getCountersObject()->set((int) $item['web_user_id'], 'mutual', (int) $item['counter']);
        }
        $this->log("OK (" . count($result) . ")", 64);
    }
}