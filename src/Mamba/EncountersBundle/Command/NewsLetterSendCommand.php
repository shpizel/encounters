<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\Script;
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
        SCRIPT_NAME = "cron:newsletter:send",

        /**
         * Сообщения от менеджера приложений
         *
         * @var str
         */
        FIRST_NEWSLETTER_MESSAGE = "Уважаемые друзья! Мы хотим сделать «Выбиратор» еще удобнее и полезнее, поэтому мы внимательно изучаем ваши отзывы и комментарии. Сегодня мы выкладываем соответствующие изменения: повышена стабильность работы приложения; оптимизирован алгоритм подбора людей — теперь приложение будет еще точнее подбирать партнеров по вашим интересам; улучшены некоторые части интерфейса.",
        SECOND_NEWSLETTER_MESSAGE = "В качестве благодарности за использование приложения мы зарядили вам батарейку до 100%! Спасибо, что вы с нами!"
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
            $this->getBatteryObject()->set((int) $userId, 5);

            try {
                $this->log(var_export($Mamba->Notify()->sendMessage((int) $userId, self::SECOND_NEWSLETTER_MESSAGE), true));
                $this->log(var_export($Mamba->Notify()->sendMessage((int) $userId, self::FIRST_NEWSLETTER_MESSAGE), true));
            } catch (\Exception $e) {
                sleep(60);
            }
        }
    }
}