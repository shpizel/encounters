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
        $result = $this->getContainer()->get('doctrine')
            ->getEntityManager()
            ->createQuery('SELECT d FROM EncountersBundle:Decisions d')
            ->getResult()
        ;

        foreach ($result as $item) {
            $webUserId = $item->getWebUserId();
            $currentUserId = $item->getCurrentUserId();
            $decision = $item->getDecision();
            $changed = $item->getChanged();

            $this->getViewedQueueObject()->put($webUserId, $currentUserId, array('ts'=>$changed, 'desicion'=>$decision));
        }
    }
}