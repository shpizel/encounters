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

    private function sendMessage($userId, $message, $cookies) {
        $cmd = "curl -v 'http://mamba.ru/my/message.phtml?oid={$userId}' -b '{$cookies}'";
        exec($cmd, $ret);
        $ret = implode(PHP_EOL, $ret);

        /** нужно получить s_post */
        if (preg_match("!name='s_post'\s*value='([^']+)!is", $ret, $sPost)) {
            $sPost = array_pop($sPost);

            if (strpos($ret, 'id="iceBreak"') !== false) {
                $cmd = "curl -v 'http://mamba.ru/my/message.phtml' -d '" . http_build_query(array(
                    'send' => 1,
                    'uid'  => $userId,
                    'action' => 'post',
                    'message' => $message,
                    's_post' => $sPost,
                )) . "' -b '{$cookies}'";

                exec($cmd, $ret);

                $this->log($userId . " {$message}");
                return true;
            } else {
                $this->log($userId . " already spammed");
            }
        }

        return false;
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
        } while (end($results)['users']);

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

        $ids = array();
        foreach ($flagged as $anketa) {
            $ids[] = $anketa['info']['oid'];
        }


        $this->log("Found " . count($ids) . " profiles");

        $messages = array(
            //'Кажется, я знаю твой маленький секрет :)',
            //'Завтра встречаемся, во сколько свободна?',
            //'Привет! У тебя очаровательная улыбка! Познакомимся?',
            //'Привет! Ты мне очень понравилась! Познакомимся?',
            'Мёд это такой предмет — если он есть, то его сразу нет. Меня, кстати, Игорем зовут) Было бы очень приятно и здорово познакомиться!',
            //'Какая легкость и грация — я под впечатлением..',
        );

        foreach ($ids as $id) {
            $this->sendMessage(
                $id,
                $messages[array_rand($messages)],
                "real_promo_995454500=show; prtmmbsid=1fd26dfc637dc3f96994d45c2a450550; redirectUrlAfterLogin=%2F; real_promo_1036866134=show; real_promo_1043865432=show; real_promo_=show; real_promo_1045879346=show; real_promo_1055804186=show; real_promo_1058812233=show; real_promo_1060208927=show; real_promo_1062005571=show; link_id=9884; real_promo_1078493976=show; bar=AShwjUz54RmYnfClOdlMYSwk4aypMQ0QLHycLan1EAEg%2FNGsFQjJ%2BLmZnLWkNVwFCXktnXjE4RxE%2BKTEXbiF1P2xObQpBP0VMED0Bex4xPyRCGQpzXlo%3D; real_promo_1092888222=show; unauth_lang=2; registered_once=1; real_promo_1101851589=show; common_friends_ts=eyJmYWNlYm9vayI6MTM2NDM5NzI5Nn0%3D; staff_s=e37a0e2d146db6d87362f0c19dbce180; from=landing; social_last_provider=facebook; UID=560015854; SECRET=w6990QZqHCCkJReP; LEVEL=Low; promo_app=1364479928; mmbsid=bc51df56a73cd5540ac23581d52e671f; LOGIN=shpizel; __utma=36878524.1722773282.1346329680.1364484382.1364540395.807; __utmb=36878524.18.10.1364540395; __utmc=36878524; __utmz=36878524.1364477978.805.55.utmcsr=yandex|utmccn=(organic)|utmcmd=organic; _ym_visorc=w; force_web=1; stat=www.mamba.ru|cr|mamba2:/my/message.phtml|118|174|246|0vу"
            ) && sleep(rand(4, 12));
        }
    }
}