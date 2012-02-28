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
 * DatabaseCleanCommand
 *
 * @package EncountersBundle
 */
class DatabaseCleanCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Database cleaner",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:clean",

        WEB_USER_SQL = "
            SELECT DISTINCT
                web_user_id
            FROM
              Encounters.Decisions
        ",

        CURRENT_USER_SQL = "
            SELECT DISTINCT
                current_user_id
            FROM
              Encounters.Decisions
        "
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Mamba = $this->getMamba();

        $id = 0;
        do {
            $this->log("Processing offset $id..", 64);
            $result = $this->getContainer()->get('doctrine')
                ->getEntityManager()
                ->createQuery("SELECT d FROM EncountersBundle:Decisions d WHERE d.id >= $id AND d.id < " . ($id = $id + 10000))
                ->getResult()
            ;

            $ids = array();
            foreach ($result as $item) {
                $webUserId = $item->getWebUserId();
                $currentUserId = $item->getCurrentUserId();

                $ids[] = $webUserId;
                $ids[] = $currentUserId;
            }

            $ids = array_unique($ids);
            $chunks = array_chunk($ids, 100);

            $Mamba->multi();
            foreach ($chunks as $chunk) {
                $Mamba->Anketa()->getInfo($chunk, array());
            }
            $result = $Mamba->exec();

            $existingIds = array();
            foreach ($result as $dataArray) {
                foreach ($dataArray as $item) {
                    $existingIds[] = $item['info']['oid'];
                }
            }
            $existingIds = array_unique($existingIds);

            if ($notExists = array_values(array_diff($ids, $existingIds))) {
                $this->log(count($notExists) . "/" . count($ids) . " does not exists right now!", 16);
                $sql = "DELETE FROM Encounters.Decisions WHERE web_user_id IN (" . implode(", ", $notExists) . ") OR current_user_id IN (" . implode(", ", $notExists) . ")";
                $this->getContainer()->get('doctrine')->getConnection()->prepare($sql)->execute();
            } else {
                $this->log("OK", 64);
            }
        } while ($result);
    }
}