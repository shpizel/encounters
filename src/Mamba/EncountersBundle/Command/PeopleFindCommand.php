<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Script\CronScript;

/**
 * PeopleFindCommand
 *
 * @package EncountersBundle
 */
class PeopleFindCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "People finder",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "people:find"
    ;

    protected function configure() {
        parent::configure();

        $this->addOption('whoami', null, InputOption::VALUE_OPTIONAL, 'Who am i', null);
    }

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Mamba = $this->getMamba();


        $offset = -10;
        $ids = array();

        do {
            $Mamba->multi();

            foreach (range(1, $threads = 16) as $i) {
                $Mamba->Search()->get(
                    $whoAmI         = 'M',
                    $lookingFor     = 'F',
                    $ageFrom        = 22,
                    $ageTo          = 26,
                    $onlyWithPhoto  = true,
                    $onlyReal       = true,
                    $onlyWithWebCam = false,
                    $countryId      = 3159,
                    $regionId       = 4312,
                    $cityId         = 4400,
                    $metroId        = null,
                    $offset         = $offset + 10,
                    $blocks         = array(),
                    $idsOnly        = !false
                );
            }

            if ($results = $Mamba->exec()) {
                foreach ($results as $chunk) {
                    if ($chunk['users']) {
                        foreach ($chunk['users'] as $userId) {
                            if (is_int($userId)) {
                                $ids[] = $userId;
                            }
                        }
                    }
                }

                $this->log(count($ids), -1);
            }
        }
        while (end($results)['users']);
        $ids = array_unique($ids);
        echo "\n";

        $ids = array_chunk($ids, 100);

        $Mamba->multi();
        foreach ($ids as $block) {
            $Mamba->Anketa()->getInfo($block);
        }

        $list = [];

        if ($result = $Mamba->exec()) {
            foreach ($result as $chunk) {
                foreach ($chunk as $anketa) {
                    $list[] = $anketa;
                }
            }
        }

        $flagged = [];

        foreach ($list as $anketa) {
            if ($familiarity = $anketa['familiarity']) {
                if (isset($familiarity['lookfor']) && preg_match("!с парнем в возрасте\s+?(\d+)-(\d+)\s+?лет!is", $familiarity['lookfor'], $age)) {
                    if (28 >= $age[1] && 28 <= $age[2]) {
                        //ok

                        if ($age[2] - $age[1] > 10) {
                            //ищу всех - иду нахуй
                            continue;
                        }
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }

                if (isset($familiarity['targets']) && in_array('Отношения', $familiarity['targets'])) {
                    //ok
                } else {
                    continue;
                }

                if (isset($familiarity['children'])) {
                    if (!preg_match("!Нет!s", $familiarity['children'])) {
                        continue;//с детями нах
                    }
                }

                if (isset($familiarity['marital'])) {
                    if (!preg_match("!Нет!s", $familiarity['marital'])) {
                        continue;//с мужьями нах
                    }
                }
            } else {
                continue;
            }

            if ($type = $anketa['type']) {
                if (isset($type['height']) && $type['height'] > 164) {
                    //ok
                } else {
                    continue;//без роста и коротышки нах
                }

                if (isset($type['weight']) && $type['weight'] < 65) {
                    //ok
                } else {
                    continue;//без веса и жырные нах
                }

                if (isset($type['smoke'])) {
                    if (preg_match("!Не курю!s", $type['smoke'])) {

                    } else {
                        continue;
                    }
                }

                if (isset($type['drink'])) {
                    if (preg_match("!Люблю!s", $type['drink'])) {
                        continue;//телка алкашка зло
                    }
                }
            }

            if (!preg_match("!^mb!", $anketa['info']['login'])) {
                continue;//с не mb-логинами шлюхи зарегавшиеся до Царя Гороха бля
            }

            $flagged[] = $anketa;
        }

        foreach ($flagged as $anketa) {
            echo "http://mamba.ru/" . $anketa['info']['login'] . PHP_EOL;
        }
    }
}