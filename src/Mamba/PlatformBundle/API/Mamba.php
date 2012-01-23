<?php

namespace Mamba\PlatformBundle\API;

final class Mamba {

    const

        /**
         * Шлюз платформы
         *
         * @var string
         */
        PLATFORM_GATEWAY_ADDRESS = 'http://api.aplatform.ru',

        /**
         * Ключ для хранения user_id в сессии
         *
         * @var str
         */
        SESSION_USER_ID_KEY = 'mamba_user_id',

        /**
         * Ключ для хранения хеша пользовательских настроек платформы
         *
         * @var str
         */
        REDIS_HASH_USER_PLATFORM_PARAMS_KEY = "user_%d_platform_params",

        /**
         * Ключ для хранения хеша кеша запросов к платформе
         *
         * @var str
         */
        REDIS_HASH_USER_API_CACHE_KEY = "user_%d_api_cache"
    ;

    public static

        $mambaRequiredGetParams = array(

            /* ID приложения */
            "app_id",

            /* ID анкеты webUser */
            "oid",

            /* Аунтефикационный ключ для проверки */
            "auth_key",

            /* ID сесии */
            "sid",

            /* Ссылка на текущего партнера */
            "partner_url",
        )
    ;

    protected

        /**
         * Настройки доступа
         *
         * @var array
         */
        $options = array(

            /**
             * Формат общения со шлюзом платформы
             *
        
             * @var string
             */
            'format' => 'json',

            /**
             * Будет ли использоваться цифровая подпись
             *
             * @var int
             */
            'secure' => 1,
        ),

        /**
         * Instances of Objects
         *
         * @var array
         */
        $Instances = array(),

        /**
         * Правила кеширования методов в секундах протухания (0 значит не кешируем)
         *
         * @var array
         */
        $cacheExpireRules = array(
            'anketa.getInfo' => 100,
        ),

        /**
         * Объект готов к работе
         *
         * @var bool
         */
        $ready = false
    ;

    protected static

        /**
         * Инстанс для удаленного выполнения процедур
         *
         * @var Mamba
         */
        $Instance,

        /**
         * Инстанс Session
         *
         * @var object
         */
        $Session,

        /**
         * Инстанс Memcache
         *
         * @var Memcache
         */
        $Memcache,

        /**
         * Инстанс Redis
         *
         * @var Redis
         */
        $Redis
    ;

    /**
     * Конструктор
     *
     * @param $secretKey
     * @param $privateKey
     */
    public function __construct($secretKey, $privateKey, $Session, $Memcache, $Redis) {
        $this->setOptions(array(
            'secret_key'  => $secretKey,
            'private_key' => $privateKey,
        ));

        self::$Instance = $this;
        self::$Session  = $Session;
        self::$Memcache = $Memcache;
        self::$Redis    = $Redis;
    }

    /**
     * Возвращает user_id WebUser'a из сессии
     *
     * @return int
     */
    public function getWebUserId() {
        return self::getSession()->get(self::SESSION_USER_ID_KEY);
    }

    /**
     * Возвращает параметр настройки коннектора
     *
     * @param string $optionName
     * @throws MambaException
     * @return mixed|null
     */
    public function getOption($optionName) {
        if (isset($this->options[$optionName])) {
            return $this->options[$optionName];
        }

        throw new MambaException("Option $optionName not found");
    }

    /**
     * Возвращает параметры настройки коннектора
     *
     * @param array $options = array($optionName1, .., $optionNameN)
     * @throws MambaException
     * @return array|null
     */
    public function getOptions($options) {
        $result = array();
        foreach ($options as $optionName) {
            if ($optionValue = $this->getOption($optionName)) {
                $result[$optionName] = $optionValue;
            }
        }

        return $result;
    }

    /**
     * Устанавливает параметр настройки коннектора
     *
     * @param string $optionName
     * @param scalar $optionValue
     * @return null
     */
    public function setOption($optionName, $optionValue) {
        $this->options[$optionName] = $optionValue;
    }

    /**
     * Устанавливает параметры настройки коннектора
     *
     * @param array $options = array($optionName=>$optionValue)
     * @return null
     */
    public function setOptions(array $options) {
        foreach ($options as $optionName => $optionValue) {
            $this->setOption($optionName, $optionValue);
        }
    }

    /**
     * Выполняет запрос к шлюзу платформы и возращает ответ
     *
     * @param string $method
     * @param array $params
     * @throws MambaException
     * @return array
     */
    public function execute($method, array $params = array()) {
        if (!$this->ready) {
            if ($webUserId = $this->getWebUserId()) {
                if ($platformSettings = self::getRedis()->hGetAll(sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $webUserId))) {
                    $this->setOptions($platformSettings);
                    $this->ready = true;
                } else {
                    throw new MambaException("Could not get mamba platform settings for user_id=" . $webUserId);
                }
            } else {
                throw new MambaException("Could not get mamba user id from session");
            }
        }

        if (strpos($method, "\\") !== false) {
            $method = explode("\\", $method);
            $method = array_pop($method);
        }

        /**
         * Попробуем взять результат из кеша
         *
         * @author shpizel
         */
        if ($cached = self::$Redis->hGet(
            sprintf(Mamba::REDIS_HASH_USER_API_CACHE_KEY, $this->getWebUserId()),
            "api://$method/?".http_build_query($params)
        )) {
            return $cached;
        }

        $lastMambaQuery = self::getRedis()->hGet(sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $this->getWebUserId()), 'last_query_time');
        if ($lastMambaQuery && (time() - (int)$lastMambaQuery >= 4*60*60)) {
            throw new MambaException("Sid expired");
        }

        if ($staticParams = $this->getOptions(array('app_id', 'format', 'secure', 'sid'))) {
            $dynamicParams = array(
                'method' => $method,
            );

            $resultParams = array_merge($staticParams, $dynamicParams, $params);

            $resultParams['sig'] = ($this->getOption('secure'))
                ? $this->getServerToServerSignature($resultParams)
                : $this->getClientToServerSignature($resultParams);

            $httpQuery = self::PLATFORM_GATEWAY_ADDRESS . "?" . http_build_query($resultParams);

            self::getRedis()->hSet(sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $this->getWebUserId()), 'last_query_time', time());

            if ($platformResponse = @file_get_contents($httpQuery)) {
                $JSON = @json_decode($platformResponse, true);

                if ($JSON['status'] === 0 && !$JSON['message']) {
                    self::$Redis->hSet(
                        sprintf(Mamba::REDIS_HASH_USER_API_CACHE_KEY, $this->getWebUserId()),
                        "api://$method/?".http_build_query($params),
                        $JSON['data']
                    );

                    return $JSON['data'];
                } else {
                    throw new MambaException($JSON["status"], $JSON['message']);
                }
            }

            throw new MambaException("Could not fetch platform url: $httpQuery");
        }
    }

    /**
     * Выполняет запрос к шлюзу платформы и возращает ответ
     *
     * @static
     * @param string $method
     * @param array $params
     * @throws MambaException
     * @return array
     */
    public static function remoteExecute($method, array $params = array()) {
        return self::$Instance->execute($method, $params);
    }

    /**
     * Возвращает server2server подпись запроса
     *
     * @param array $params
     * @return string
     */
    private function getServerToServerSignature(array $params) {
        ksort($params);
        $signature = '';
        foreach ($params as $key => $value) {
            $signature .= "$key=$value";
        }
        return md5($signature . $this->getOption('secret_key'));
    }

    /**
     * Возвращает client2server подпись запроса
     *
     * @param array $params
     * @return string
     */
    private function getClientToServerSignature(array $params) {
        ksort($params);
        $signature = '';
        foreach ($params as $key => $value) {
            $signature .= "$key=$value";
        }
        return md5($this->getOption('oid') . $signature . $this->getOption('private_key'));
    }

    /**
     * Getter for Anketa object
     *
     * @return Anketa
     */
    public function Anketa() {
        return $this->getSingletonHelperObject(__FUNCTION__);
    }

    /**
     * Getter for Photos object
     *
     * @return Photos
     */
    public function Photos() {
        return $this->getSingletonHelperObject(__FUNCTION__);
    }

    /**
     * Getter for Diary object
     *
     * @return Diary
     */
    public function Diary() {
        return $this->getSingletonHelperObject(__FUNCTION__);
    }

    /**
     * Getter for Search object
     *
     * @return Search
     */
    public function Search() {
        return $this->getSingletonHelperObject(__FUNCTION__);
    }

    /**
     * Getter for Achievement object
     *
     * @return Achievement
     */
    public function Achievement() {
        return $this->getSingletonHelperObject(__FUNCTION__);
    }

    /**
     * Getter for Contacts object
     *
     * @return Contacts
     */
    public function Contacts() {
        return $this->getSingletonHelperObject(__FUNCTION__);
    }

    /**
     * Getter for Notify object
     *
     * @return Notify
     */
    public function Notify() {
        return $this->getSingletonHelperObject(__FUNCTION__);
    }

    /**
     * Getter for Geo object
     *
     * @return Geo
     */
    public function Geo() {
        return $this->getSingletonHelperObject(__FUNCTION__);
    }

    /**
     * Getter for singleton objects
     *
     * @param string $className
     * @return Object
     */
    private function getSingletonHelperObject($className) {
        $className = __NAMESPACE__ . "\\" . $className;

        return
            isset($this->Instances[$className])
                ? $this->Instances[$className]
                : $this->Instances[$className] = new $className;
        ;
    }

    /**
     * Возвращает валидность auth_key
     *
     * @param array $params
     * @return bool
     */
    public function checkAuthKey($params) {
        if (isset($params['auth_key'])) {
            $authKey = $params['auth_key'];
            unset($params['auth_key']);

            if ($this->getServerToServerSignature($params) == $authKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mamba instance getter for remote executions
     *
     * @static
     * @return Mamba
     */
    public static function getInstance() {
        return self::$Instance;
    }

    /**
     * Session getter
     *
     * @static
     * @return Session
     */
    public static function getSession() {
        return self::$Session;
    }

    /**
     * Memcache getter
     *
     * @static
     * @return Memcache
     */
    public static function getMemcache() {
        return self::$Memcache;
    }

    /**
     * Redis getter
     *
     * @static
     * @return Redis
     */
    public static function getRedis() {
        return self::$Redis;
    }
}

/**
 * MambaAppPlatformException
 *
 * @package Mamba
 */
class MambaException extends \Exception {

}