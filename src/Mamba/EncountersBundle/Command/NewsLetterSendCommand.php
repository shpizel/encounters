<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script;
use Mamba\EncountersBundle\Helpers\SearchPreferences;

/**
 * NewsLetterSendCommand
 *
 * @package EncountersBundle
 */
class NewsLetterSendCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Newsletter sender",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "script:newsletter:send",

        /**
         * Сообщение от менеджера приложений
         *
         * @var str
         */
        NEWSLETTER_MESSAGE = "Уважаемые друзья!

Мы хотим сделать «Выбиратор» еще удобнее и полезнее, поэтому мы внимательно изучаем все ваши отзывы и комментарии. Сегодня мы выкладываем первые изменения:

1. Повышена стабильность работы приложения

2. Оптимизирован алгоритм подбора людей — мы научились точнее подбирать партнеров по вашим интересам

3. Улучшены некоторые части интерфейса

А еще в качестве благодарности мы зарядили вам батарейку до 100%!

С уважением,
Команда «Выбиратора»"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Mamba = $this->getMamba();
        $Redis = $this->getRedis();

        foreach ($Redis->hKeys(SearchPreferences::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY) as $userId) {
            $this->getBatteryObject()->set($userId, 5);
            //$this->log(var_export($Mamba->Notify()->sendMessage(560015854, self::NEWSLETTER_MESSAGE), true));
        }
    }
}