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
        $this->log($this->getUserNameById(560015854));
    }

    /**
     * Возвращает имя пользователя
     *
     * @param int $userId
     * @return string|null
     */
    private function getUserNameById($userId) {
        if ($anketa = $this->getMamba()->nocache()->Anketa()->getInfo($userId)) {
            $name = $anketa[0]['info']['name'];

            return $name;
        }
    }
}