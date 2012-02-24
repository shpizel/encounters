<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\QueueUpdateCronScript;
use Mamba\EncountersBundle\EncountersBundle;

use Mamba\EncountersBundle\Entity\Decisions;

/**
 * VisitorsRepairCommand
 *
 * @package EncountersBundle
 */
class VisitorsRepairCommand extends QueueUpdateCronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Visitor repair"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $i = 0;
        do {
            $result = $this->getContainer()->get('doctrine')
                ->getEntityManager()
                ->createQuery("SELECT d FROM EncountersBundle:Decisions d where id >= " . $i . " and id < " . ($i = $i+1000))
                ->getResult()
            ;

            foreach ($result as $item) {
                $webUserId = $item->getWebUserId();
                $currentUserId = $item->getCurrentUserId();
                $decision = $item->getDecision();
                $changed = $item->getChanged();

                var_dump($webUserId, $currentUserId, $decision, $changed);

                /*$this->getViewedQueueObject()->set(
                    $webUserId,
                    $currentUserId,
                    array(
                        'ts' => $changed,
                        'decision' => $decision
                    )
                );*/
            }
        } while ($result);
    }
}