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
        $css = <<< EOL
.layer-energy .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-battery .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-level-achievement .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-level .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-repeatable-yes .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-repeatable-no .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-repeatable-maybe .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-no .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-yes .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-maybe .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-not-see-yet .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-account .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-photoline-purchase .info-battery {
    background-color: #fff1d2;
    border-radius: 5px 5px 5px 5px;
    display: block;
    margin: 20px auto 5px;
    padding: 10px;
    position: relative;
    text-align: center;
    font-size: 15px;
    line-height: 20px;
    width: 96%;
    padding-top: 12px;
    padding-bottom: 12px;
}

.layer-no .info-battery {
    text-align: left;
    margin-bottom: 17px;
}
.layer-yes .info-battery {
    text-align: left;
    margin-bottom: 17px;
}
.layer-maybe .info-battery {
    text-align: left;
    margin-bottom: 17px;
}
.layer-not-see-yet .info-battery {
    text-align: left;
    margin-bottom: 17px;
}

.layer-no .info {
    padding: 8px !important;
}

.layer-yes .info {
    padding: 8px !important;
}

.layer-maybe .info {
    padding: 8px !important;
}

.layer-not-see-yet .info {
    padding: 8px !important;
}

.layer-energy .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-battery .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-level-achievement .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-level .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-repeatable-yes .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-repeatable-no .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-repeatable-maybe .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-yes .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-maybe .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-not-see-yet .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-account .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-photoline-purchase .info-battery .arrow {
    background: none;
    position: absolute;
    left: 49%;
    top: -18px;
    margin-left: -9px;
    border: 9px solid transparent;
    border-bottom: 9px solid #fff1d2;
    height: 0;
    width: 0;
}

.layer-user-info div.info {
    font-size: 13px;
    padding: 20px;
    padding-bottom: 30px;
}

.layer-maybe div.info {
    font-size: 13px;
    padding: 20px;
    padding-bottom: 30px;
}

.layer-yes div.info {
    font-size: 13px;
    padding: 20px;
    padding-bottom: 30px;
}

.layer-no div.info {
    font-size: 13px;
    padding: 20px;
    padding-bottom: 30px;
}

.layer-not-see-yet div.info {
    font-size: 13px;
    padding: 20px;
    padding-bottom: 30px;
}

.layer-user-info div.info div.face {
    float: left;
    margin-right: 15px;
}

.layer-maybe div.info div.face {
    float: left;
    margin-right: 15px;
}

.layer-yes div.info div.face {
    float: left;
    margin-right: 15px;
}

.layer-no div.info div.face {
    float: left;
    margin-right: 15px;
}

.layer-not-see-yet div.info div.face {
    float: left;
    margin-right: 15px;
}

.layer-user-info div.info div.face img {
    box-shadow: 0 0 2px #666;
    padding: 4px;
    width: 110px;
    height: 150px;
}

.layer-maybe div.info div.face img {
    box-shadow: 0 0 2px #666;
    padding: 4px;
    width: 110px;
    height: 150px;
}

.layer-yes div.info div.face img {
    box-shadow: 0 0 2px #666;
    padding: 4px;
    width: 110px;
    height: 150px;
}

.layer-not-see-yet div.info div.face img {
    box-shadow: 0 0 2px #666;
    padding: 4px;
    width: 110px;
    height: 150px;
}

.layer-user-info div.info div.name {
    font-size: 24px;
    margin-bottom: 3px;
}

.layer-maybe div.info div.name {
    font-size: 24px;
    margin-bottom: 3px;
}

.layer-yes div.info div.name {
    font-size: 24px;
    margin-bottom: 3px;
}

.layer-not-see-yet div.info div.name {
    font-size: 24px;
    margin-bottom: 3px;
}

.layer-user-info div.info div.location {
    color: #666;
}

.layer-maybe div.info div.location {
    color: #666;
}

.layer-yes div.info div.location {
    color: #666;
}
EOL;

        $css = trim($css);

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