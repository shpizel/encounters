<?php
namespace Mamba\PlatformBundle\API;

/**
 * Mamba
 *
 * @package PlatformBundle
 */
final class Mamba {

    const

        /**
         * Шлюз платформы
         *
         * @var string
         */
        PLATFORM_GATEWAY_ADDRESS = 'http://api.aplatform.ru',

        /**
         * Включено ли кеширование
         *
         * @var bool
         */
        CACHE_ENABLED = true,

        /**
         * Redis cache backend
         *
         * @var int
         */
        REDIS_CACHE_BACKEND = 1,

        /**
         * Memcache cache backend
         *
         * @var int
         */
        MEMCACHE_CACHE_BACKEND = 2,

        /**
         * Single mode
         *
         * @var int
         */
        SINGLE_MODE = 1,

        /**
         * Multi mode
         *
         * @var int
         */
        MULTI_MODE = 2,

        /**
         * Количество урлов загружаемых мультикурлом за раз
         *
         * @var int
         */
         MULTI_FETCH_CHUNK_SIZE = 10,

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
        REDIS_HASH_USER_PLATFORM_PARAMS_KEY = "user_%d_platform_params"
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
         * Правила кеширования методов в секундах протухания (0 — навсегда)
         *
         * @var array
         */
        $cachingOptions = array(

            /**
             * Правила кеширования платформенных методов семейства anketa.*
             *
             * @var array
             */
            'anketa' => array(

                /** Получение всех полей анкеты */
                'getInfo' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 21600,
                ),

                /** Получение интересов */
                'getInterests' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 3600,
                ),

                /** Получение объявлений из попутчиков */
                'getTravel' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 3600,
                ),

                /** Получение списка флагов любой анкеты: VIP, реал, лидер, maketop, интим за деньги */
                'getFlags' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 3600,
                ),

                /** Статус online или когда был крайний раз на сайте, если не надета шапка-невидимка */
                'isOnline' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 600,
                ),

                /** Проверка установлено ли указанное приложение у указанной анкеты */
                'isAppUser' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 3600,
                ),

                /** Получение хитлиста */
                'getHitlist' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => true,
                    'expire'  => 3600,
                ),

                /** Проверка, является ли пользователь владельцем приложения */
                'isAppOwner' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => true,
                    'expire'  => 86400,
                ),

                /** Проверка, стоит ли приложение в «Избранных» у пользователя */
                'inFavourites' => array(
                    'backend' => false,
                ),
            ),

            /**
             * Правила кеширования платформенных методов семейства achievement.*
             *
             * @var array
             */
            'achievement' => array(

                /** Обновить запись на доске достижений */
                'set' => array(
                    'backend' => false,
                ),
            ),

            /**
             * Правила кеширования платформенных методов семейства contacts.*
             *
             * @var array
             */
            'contacts' => array(

                /** Получение списка папок «моих сообщений» со счетчиками контактов */
                'getFolderList' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => true,
                    'expire'  => 600,
                ),

                /** Получение списка контактов из заданной папки */
                'getFolderContactList' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => true,
                    'expire'  => 600,
                ),

                /** Получение списка контактов по заданому лимиту */
                'getContactList' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => true,
                    'expire'  => 600,
                ),

                /** Написать сообщение в мессенджер от имени пользователя */
                'sendMessage' => array(
                    'backend' => false,
                ),
            ),

            /**
             * Правила кеширования платформенных методов семейства diary.*
             *
             * @var array
             */
            'diary' => array(

                /** Получение списка постов дневника — заголовки и ссылки на посты */
                'getPosts' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 86400,
                ),
            ),

            /**
             * Правила кеширования платформенных методов семейства geo.*
             *
             * @var array
             */
            'geo' => array(

                /** Получение списка стран */
                'getCountries' => array(
                    'backend' => self::REDIS_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => false,
                ),

                /** Получение списка регионов страны */
                'getRegions' => array(
                    'backend' => self::REDIS_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => false,
                ),

                /** Получение списка городов региона */
                'getCities' => array(
                    'backend' => self::REDIS_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => false,
                ),

                /** Получение списка станций метро города */
                'getMetro' => array(
                    'backend' => self::REDIS_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => false,
                )
            ),

            /**
             * Правила кеширования платформенных методов семейства notify.*
             *
             * @var array
             */
            'notify' => array(

                /** Отослать извещение в мессенджер от имени пользователя «Менеджер приложений» */
                'sendMessage' => array(
                    'backend' => false,
                ),
            ),

            /**
             * Правила кеширования платформенных методов семейства photos.*
             *
             * @var array
             */
            'photos' => array(

                /** Получение списка включенных альбомов */
                'getAlbums' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 3600,
                ),

                /** Получение списка фотографий для заданного включенного альбома */
                'get' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 3600,
                ),
            ),

            /**
             * Правила кеширования платформенных методов семейства photos.*
             *
             * @var array
             */
            'search' => array(

                /** Стандартный краткий поиск мамбы */
                'get' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 300,
                ),
            ),
        ),

        /**
         * Объект готов к работе
         *
         * @var bool
         */
        $ready = false,

        /**
         * Режим работы класса
         *
         * @var int
         */
        $mode = self::SINGLE_MODE,

        /**
         * Multi queue
         *
         * @var array
         */
        $multiQueue = array()
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
        $this->set(array(
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
        if (isset($this->options['oid'])) {
            return $this->options['oid'];
        } elseif ($webUserId = self::getSession()->get(self::SESSION_USER_ID_KEY)) {
            return $webUserId;
        }
    }

    /**
     * Возвращает параметры настройки коннектора
     *
     * @param mixed
     * @throws MambaException
     * @return scalar|array(key=>value)|null
     */
    public function get(/* args */) {
        $argc = func_num_args();
        if ($argc == 1) {
            if ($argument = func_get_arg(0)) {
                if (is_array($argument)) {
                    $result = array();
                    foreach ($argument as $optionName) {
                        $result[$optionName] = $this->get($optionName);
                    }

                    return $result;
                } else {
                    if (isset($this->options[$argument])) {
                        return $this->options[$argument];
                    }

                    throw new MambaException("Option $argument not found");
                }
            }
        }

        throw new MambaException("Invalid getter params");
    }

    /**
     * Устанавливает параметр настройки коннектора
     *
     * @return null
     */
    public function set(/* args */) {
        $argc = func_num_args();

        if ($argc == 2) {
            $optionName  = func_get_arg(0);
            $optionValue = func_get_arg(1);

            return $this->options[$optionName] = $optionValue;
        } elseif ($argc == 1) {
            $argument = func_get_arg(0);
            if (is_array($argument) && $argument) {
                foreach ($argument as $optionName=>$optionValue) {
                    $this->set($optionName, $optionValue);
                }
            }
            return;
        }

        throw new MambaException("Invalid setter params");
    }

    /**
     * Генерирует ключ для хранения кеша
     *
     * @return string
     */
    private function getCacheKey($method, $params) {
        list($namespace, $method) = explode(".", $method);
        if (!isset($this->cachingOptions[$namespace][$method])) {
            throw new MambaException("$namespace.$method has no caching options");
        }

        $cachingOptions = $this->cachingOptions[$namespace][$method];

        if (self::CACHE_ENABLED && $cachingOptions['backend']) {
            $signed = $cachingOptions['signed'];
            $cachingKey =  "api://" . ($signed ? ($this->getWebUserId() . "@") : '') . "$namespace.$method";
            if ($getParams = http_build_query($params)) {
                $cachingKey .= "/?" . $getParams;
            }
            return $cachingKey;
        }
    }

    /**
     * Дергает кеш в соответствии с настройками
     *
     * @return string
     */
    private function getCache($method, $params) {
        if ($cachingKey = $this->getCacheKey($method, $params)) {
            list($namespace, $method) = explode(".", $method);
            $cachingBackend = $this->cachingOptions[$namespace][$method]['backend'];
            $result = null;
            if ($cachingBackend == self::REDIS_CACHE_BACKEND) {
                $result = self::getRedis()->get($cachingKey);
            } elseif ($cachingBackend == self::MEMCACHE_CACHE_BACKEND) {
                $result = self::getMemcache()->get($cachingKey);
            } else {
                throw new MambaException("Invalid caching backend");
            }

            return $result;
        }
    }

    /**
     * Сохраняет кеш в соответствии с настройками
     *
     * @return string
     */
    private function setCache($method, $params, $data) {
        if ($cachingKey = $this->getCacheKey($method, $params)) {
            list($namespace, $method) = explode(".", $method);
            $cachingOptions = $this->cachingOptions[$namespace][$method];
            $cachingBackend = $cachingOptions['backend'];
            if (!isset($cachingOptions['expire'])) {
                throw new MambaException('Invalid expire field');
            }
            $expire = $cachingOptions['expire'];

            if ($cachingBackend == self::REDIS_CACHE_BACKEND) {
                if ($expire) {
                    return self::getRedis()->setex($cachingKey, (int) $expire, $data);
                }
                return self::getRedis()->set($cachingKey, $data);
            } elseif ($cachingBackend == self::MEMCACHE_CACHE_BACKEND) {
                return self::getMemcache()->set($cachingKey, $data, (int) $expire);
            }

            throw new MambaException("Invalid caching backend");
        }
    }

    /**
     * Пытается проинициализировать объект и возвращает его готовность
     *
     * @return bool,
     */
    public function getReady() {
        if ($this->ready) {
            return $this->ready;
        }

        if ($webUserId = $this->getWebUserId()) {
            if ($platformSettings = self::getRedis()->hGetAll(sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $webUserId))) {
                $this->set($platformSettings);
                return $this->ready = true;
            }
        }

        return false;
    }

//    /**
//     * Возвращает настройки платформы
//     *
//     * @return null|array
//     */
//    public function getPlatformSettings() {
//        if ($webUserId = $this->getWebUserId()) {
//            if ($platformSettings = self::getRedis()->hGetAll(sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $webUserId))) {
//                return $platformSettings;
//            }
//        }
//    }

    /**
     * Выполняет запрос к шлюзу платформы и возращает ответ
     *
     * @param string $method
     * @param array $params
     * @throws MambaException
     * @return array
     */
    public function execute($method, array $params = array()) {
        if (!$this->getReady()) {
            throw new MambaException("Mamba is not ready to work");
        }

        if (strpos($method, "\\") !== false) {
            $method = explode("\\", $method);
            $method = array_pop($method);
        }

        if ($cacheResult = $this->getCache($method, $params)) {
            if ($this->mode == self::MULTI_MODE ) {
                $this->multiQueue[] = array(
                    'cached' => $cacheResult
                );

                return $this;
            }

            return $cacheResult;
        }

        $lastMambaQuery = self::getRedis()->hGet(sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $this->getWebUserId()), 'last_query_time');
        if ($lastMambaQuery && (time() - (int)$lastMambaQuery >= 4*60*60)) {
            throw new MambaException("Sid expired");
        }

        if ($staticParams = $this->get(array('app_id', 'format', 'secure', 'sid'))) {
            $dynamicParams = array(
                'method' => $method,
            );

            $resultParams = array_merge($staticParams, $dynamicParams, $params);

            $resultParams['sig'] = ($this->get('secure'))
                ? $this->getServerToServerSignature($resultParams)
                : $this->getClientToServerSignature($resultParams);

            $httpQuery = self::PLATFORM_GATEWAY_ADDRESS . "?" . http_build_query($resultParams);

            if ($this->mode == self::MULTI_MODE) {
                $this->multiQueue[] = array(
                    'url' => $httpQuery,
                    'method' => $method,
                    'params' => $params,
                );

                return $this;
            }

            if ($platformResponse = @file_get_contents($httpQuery)) {
                self::getRedis()->hSet(sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $this->getWebUserId()), 'last_query_time', time());
                $JSON = @json_decode($platformResponse, true);

                if ($JSON['status'] === 0 && !$JSON['message']) {
                    $this->setCache($method, $params, $JSON['data']);
                    return $JSON['data'];
                } else {
                    throw new MambaException($JSON['message'], $JSON["status"]);
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
        return md5($signature . $this->get('secret_key'));
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
        return md5($this->get('oid') . $signature . $this->get('private_key'));
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

    /**
     * Мультипликатор
     *
     * @return Mamba
     */
    public function multi() {
        $this->mode = self::MULTI_MODE;
        $this->multiQueue = array();
        return $this;
    }

    /**
     * Мультипликационный экзекутор
     *
     * @param int $chunkSize Размер одновременно загружаемых урлов
     * @return array
     */
    public function exec($chunkSize = self::MULTI_FETCH_CHUNK_SIZE, $strict = false) {
        if ($this->mode != self::MULTI_MODE) {
            throw new MambaException("Mamba must be configured to MULTI mode");
        }

        if (!count($this->multiQueue)) {
            throw new MambaException("Request queue is empty");
        }

        $urls = array();
        foreach ($this->multiQueue as $item) {
            if (isset($item['url'])) {
                $urls[] = $item['url'];
            }
        }

        if ($urls) {
            $platformResponses = $this->urlMultiFetch($urls, $chunkSize);
            self::getRedis()->hSet(sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $this->getWebUserId()), 'last_query_time', time());
        } else {
            $platformResponses = array();
        }

        if ($platformResponses) {
            foreach ($this->multiQueue as $key=>&$item) {
                if (isset($item['url']) && !isset($item['content'])) {
                    $platformResponse = $platformResponses[$item['url']];
                    $JSON = @json_decode($platformResponse, true);

                    if ($JSON['status'] === 0 && !$JSON['message']) {
                        $this->setCache($item['method'], $item['params'], $JSON['data']);
                    } elseif ($strict) {
                        throw new MambaException($JSON['message'], $JSON['code']);
                    }

                    $item['content'] = $JSON['data'];

                    continue;
                }
            }
        }

        $this->mode = self::SINGLE_MODE;

        foreach ($this->multiQueue as &$item) {
            if (isset($item['cached'])) {
                $item = $item['cached'];
            } elseif (isset($item['content'])) {
                $item = $item['content'];
            }
        }

        $result = $this->multiQueue;
        $this->multiQueue = array();

        return $result;
    }

    /**
     * Дернуть урлы
     *
     * @param array $urls Список урлов
     * @param int $chunkSize Размер одновременно загружаемых урлов
     * @throws MambaException
     * @return array(
     *     'url' => $content
     * )
     */
    private function urlMultiFetch(array $urls, $chunkSize = self::MULTI_FETCH_CHUNK_SIZE) {
        $result = array();

        if (!count($urls)) {
            throw new MambaException("Empty urls list");
        } elseif (count($urls) <= $chunkSize) {
            $mh = curl_multi_init();
            $singleCurlInstances = array();
            foreach ($urls as $url) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $singleCurlInstances[] = $ch;
                curl_multi_add_handle($mh, $ch);
            }

            $active = null;
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($active && $mrc == CURLM_OK) {
                if (curl_multi_select($mh) != -1) {
                    do {
                        $mrc = curl_multi_exec($mh, $active);
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }
            }

            foreach ($singleCurlInstances as $ch) {
                list($url, $content) = array(
                    curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
                    curl_multi_getcontent($ch),
                );

                if (($code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) != 200) {
                    throw new MambaException("$url has returned $code code");
                }

                $result[$url] = $content;
                curl_multi_remove_handle($mh, $ch);
            }
            curl_multi_close($mh);
            return $result;
        }

        $chunks = array_chunk($urls, $chunkSize);
        foreach ($chunks as $urls) {
            foreach ($this->urlMultiFetch($urls, $chunkSize) as $url=>$content) {
                $result[$url] = $content;
            }
        }
        return $result;
    }
}

/**
 * MambaException
 *
 * @package PlatformBundle
 */
class MambaException extends \Exception {

}