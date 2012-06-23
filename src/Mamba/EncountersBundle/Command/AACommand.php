<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\Script;
use Mamba\EncountersBundle\Helpers\Declensions;
use PDO;

/**
 * AACommand
 *
 * @package EncountersBundle
 */
class AACommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "AA script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "AA"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        while (true) {
            $appPage = `curl -s "http://mamba.ru/app_platform/?action=view&app_id=355&from=catalog" -b "prtmmbsid=5048503c3fe160fc2b613da68a9f9b9d; partner_lang_id=3; redirectUrlAfterLogin=%2F%3Flang_id%3D3; staff_s=91751c402045e5e3ec52c475d18983e1; unauth_lang=2; corpmmbsid=ebe111833228552cdfb17e46e8886568; UID=560015854; SECRET=lyIMR1; LEVEL=Low; promo_photoline=1340434608; mmbsid=97e536f949a8982b8919783ff61aa6d4; LOGIN=shpizel; bar=AShwjUz54RmYnfClOdlMYVR1tZ2tQBwItRQlBXk4PZAd8bl43BVVWCUZ7ZWhILRISTmgSKQ0LJnwPGQotVBwcVFJzAWZ%2BUSs%3D; __utma=36878524.232663550.1337362084.1340434574.1340442258.190; __utmb=36878524.24.10.1340442258; __utmc=36878524; __utmz=36878524.1340304764.181.7.utmcsr=google|utmccn=(organic)|utmcmd=organic|utmctr=(not%20provided); promo_app=1340444485"`;
            if (preg_match("!src=\"(http://meet.*?)\"!is", $appPage, $result)) {
                $appUrl = array_pop($result);
                //$this->log($appUrl);

                $start = microtime(true);
                $uniqId = uniqid("c", true);

                $appContent = `curl -is -c "/tmp/$uniqId.cookie" "$appUrl"`;
                $finish = microtime(true);
                $timeout = $finish - $start;

                $this->log(round($timeout, 2), $timeout < 1 ? 48 : 16);

                $appContent = `curl -is -c "/tmp/$uniqId.cookie" -b "/tmp/$uniqId.cookie" "http://meetwithme.ru/search"`;
                if (preg_match("!\"oid\":(\d+)!is", $appContent, $result)) {
                    if (array_pop($result) != 560015854) {
                        $this->log("!!! WARNING !!!", 16);
                        $this->log("!!! ERROR !!!", 16);
                        $this->log("!!! WARNING !!!", 16);
                    } else {
                        $this->log("OK", 64);
                    }
                } else {
                    $this->log("ppc", 16);
                }



                //echo $appContent;
            }
        }
    }
}