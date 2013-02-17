<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

/**
 * AACommand
 *
 * @package EncountersBundle
 */
class AACommand extends CronScript {

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

        $css = trim($css = '');

        $s = array();
        if (preg_match_all("!(?P<rule>[\,.-\w\s]+)\s*?{\s*?(?P<css>[^}]+)\s*?}!is", $css, $parsed)) {
            foreach ($parsed['rule'] as $index=>$rule) {
                $rule = trim($rule);
                $css = trim($parsed['css'][$index]);
                $s[$rule] = $css;

            }
        }

        ksort($s);
        foreach ($s as $rule=>$css) {
            echo "{$rule} {\n    {$css}\n}\n\n";
        }

    }
}