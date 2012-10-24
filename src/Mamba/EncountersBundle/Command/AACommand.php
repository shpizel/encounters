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
        $Mamba = $this->getMamba();
        $Mamba->set('oid', $webUserId = 560015854);

        if ($contactsFolders = $Mamba->Contacts()->getFolderList()) {
            foreach ($contactsFolders['folders'] as $folder) {
                if ($contactList = $Mamba->Contacts()->getFolderContactList($folder['folder_id'])) {
                    foreach ($contactList['contacts'] as $contact) {
                        $contactInfo = $contact['info'];

                        $this->getRedis()->sAdd("contacts_by_{$webUserId}", $contactInfo['oid']);
                    }
                }
            }
        }
    }
}