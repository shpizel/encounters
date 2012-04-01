<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\CronScript;
use Doctrine\ORM\Query\ResultSetMapping;
use PDO;

/**
 * DatabaseCleanCommand
 *
 * @package EncountersBundle
 */
class DatabaseCleanCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Database cleaner",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:clean",

        /**
         * Имя файла куда класть айдишники удалившихся узеров
         *
         * @var str
         */
        DELETED_USERS_FILENAME = "/home/shpizel/deleted.list"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Mamba = $this->getMamba();
        if ($lastId = $this->getLastId()) {
            for ($id=0;$id<$lastId;$id+=5000) {
                $ids = array();
                $sql = "SELECT DISTINCT `id`, `web_user_id` as `user_id` FROM Encounters.Decisions where `id` > $id AND `id` <= " . ($id+5000);
                $stmt = $this->getConnection()->prepare($sql);
                if ($stmt->execute()) {
                    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $ids[] = $item['user_id'];
                    }
                }

                $sql = "SELECT DISTINCT `id`, `current_user_id` as `user_id` FROM Encounters.Decisions where `id` > $id AND `id` <= " . ($id+5000);
                $stmt = $this->getConnection()->prepare($sql);
                if ($stmt->execute()) {
                    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $ids[] = $item['user_id'];
                    }
                }

                $ids = array_unique($ids);
                $chunks = array_chunk($ids, 100);
                $Mamba->multi();
                foreach ($chunks as $chunk) {
                    $Mamba->Anketa()->getInfo($chunk);
                }

                if ($result = $Mamba->exec(100)) {
                    $existingIds = array();
                    foreach ($result as $subResult) {
                        foreach ($subResult as $anketa) {
                            $existingIds[] = (int) $anketa['info']['oid'];
                        }
                    }

                    $notExistingIds = array_values(array_diff($ids, $existingIds));
                    foreach ($notExistingIds as $userId) {
                        file_put_contents(self::DELETED_USERS_FILENAME, $userId . "\n", FILE_APPEND);
                    }
                }

                $this->log(round($id*100/$lastId, 2) . "%\n");
            }

            $this->log("Bye");

            /**
             * Нужно удалить:
             * 1) из базы: by current_user_id or web_user_id
             * 2) очередь контактов
             * 3) текущая очередь
             * 4) очередь хитлиста
             * 5) приоритетная очередь
             * 6) очередь поиска
             * 7) просмотренная очередь
             *
             * 8) удалить батарейку
             * 9) удалить счетчики
             * 10) удалить энергию
             * 11) удалить нотификацию
             * 12) удалить настройки платформы
             * 13) удалить покупки
             * 14) удалить поисковые предпочтения
             * 15) удалить сервисы
             *
             * @author shpizel
             */
        }
    }

    /**
     * Connection getter
     *
     * @return object
     */
    private function getConnection() {
        return $this->getContainer()->get('doctrine')->getEntityManager()->getConnection();
    }

    /**
     * Возвращает последний id в таблице
     *
     * @return int
     */
    private function getLastId() {
        $stmt = $this->getConnection()->prepare("SELECT `id` FROM Encounters.Decisions ORDER BY `id` DESC LIMIT 1");
        if ($stmt->execute()) {
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            return $item['id'];
        }

        return 0;
    }
}