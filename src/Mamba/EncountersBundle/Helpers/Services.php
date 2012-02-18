<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;

/**
 * Services
 *
 * @package EncountersBundle
 */
class Services extends Helper {

    const

        /**
         * Ключ для хранения услуг
         *
         * @var str
         */
        REDIS_SET_USER_SERVICES_KEY = "services_by_%d"
    ;

    protected

        /**
         * Services
         *
         * @var array
         */
        $services = array(
            1 => 'Покупка полного заряда батарейки',
            2 => 'Стать №1 для ...',
            3 => 'Покупка 100 дополнительных показов',
        )
    ;

    /**
     * Возвращает последнюю заказанную услугу (с удалением из списка)
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        if (!is_int($userId)) {
            throw new NotificationsException("Invalid user id: \n" . var_export($userId, true));
        }

        if (false !== $result = $this->Redis->sPop(sprintf(self::REDIS_SET_USER_SERVICES_KEY, $userId))) {
            return json_decode($result, true);
        }
    }

    /**
     * Добавляет услугу в список
     *
     * @param int $userId
     * @param mixed $service
     */
    public function add($userId, $service) {
        if (!is_int($userId)) {
            throw new ServicesException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->sAdd(sprintf(self::REDIS_SET_USER_SERVICES_KEY, $userId), json_encode($service));
    }
}

/**
 * ServicesException
 *
 * @package EncountersBundle
 */
class ServicesException extends \Exception {

}