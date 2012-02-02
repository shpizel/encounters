<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * QueueUpdateCronScript
 *
 * @package EncountersBundle
 */
abstract class QueueUpdateCronScript extends CronScript {

    /**
     * Возвращает поисковые предпочтения для указанного юзера
     *
     * @return mixed
     */
    protected function getSearchPreferences($mambaUserId) {
        return
            $this->getContainer()->get('redis')
                ->hGetAll(sprintf(EncountersBundle::REDIS_HASH_USER_SEARCH_PREFERENCES_KEY, $mambaUserId))
        ;
    }

    /**
     *
     *
     * @param string $queue
     * @param int $webUserId
     * @param int $currentUserId
     */
    protected function putQueue($queue, $webUserId, $currentUserId) {

    }
}