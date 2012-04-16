<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;

/**
 * AdminUserController
 *
 * @package EncountersBundle
 */
class AdminUserController extends ApplicationController {

    /**
     * Index action
     *
     * @param int $user_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($user_id) {
        /**
         * Поисковые предпочтения
         * Очереди (приоритетная, текущая, поиска, контактов, хитлиста) со ссылками на анкеты
         * Батарейка
         * Счетчики
         * Энергия + уровень
         * Нотификации
         * Настройки платформы
         * Переменные
         *
         * @author shpizel
         */
        return new Response($user_id);
    }
}