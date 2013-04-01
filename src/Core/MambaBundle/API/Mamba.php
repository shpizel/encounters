<?php
namespace Core\MambaBundle\API;

use Core\MambaBundle\API\Achievement;
use Core\MambaBundle\API\Anketa;
use Core\MambaBundle\API\Contacts;
use Core\MambaBundle\API\Diary;
use Core\MambaBundle\API\Geo;
use Core\MambaBundle\API\Notify;
use Core\MambaBundle\API\Photos;
use Core\MambaBundle\API\Search;

use Core\MambaBundle\Helpers\PlatformSettings;

/**
 * Mamba
 *
 * @package MambaBundle
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
         MULTI_FETCH_CHUNK_SIZE = 16,

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
         * Таймаут для курла
         *
         * @var int
         */
        CURL_TIMEOUT = 5 /** 5 секунд если Мамба не отвечает - отказ */
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
                    'expire'  => 86400,
                ),

                /** Получение интересов */
                'getInterests' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 86400,
                ),

                /** Получение объявлений из попутчиков */
                'getTravel' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 86400,
                ),

                /** Получение списка флагов любой анкеты: VIP, реал, лидер, maketop, интим за деньги */
                'getFlags' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 86400,
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
                    'expire'  => /*3600*/ 86400,
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
                    'expire'  => 86400,
                ),

                /** Получение списка контактов из заданной папки */
                'getFolderContactList' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => true,
                    'expire'  => 10800,
                ),

                /** Получение списка контактов по заданому лимиту */
                'getContactList' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => true,
                    'expire'  => 10800,
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
                    'expire'  => 86400,
                ),

                /** Получение списка фотографий для заданного включенного альбома */
                'get' => array(
                    'backend' => self::MEMCACHE_CACHE_BACKEND,
                    'signed'  => false,
                    'expire'  => 86400,
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
                    'expire'  => 86400,
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
         * Режим временного отключения кеширования
         *
         * @var bool
         */
        $noCacheMode = false,

        /**
         * Multi queue
         *
         * @var array
         */
        $multiQueue = array(),

        /**
         * Список методов, которым sid не нужен
         *
         * @var array
         */
        $sidRequiredMethods = array(
            'contacts.sendMessage',
            'contacts.getContactList',
            'contacts.getFolderContactList',
            'contacts.getFolderList',
            'anketa.inFavourites',
            'anketa.getHitlist',
            'achievement.set',
            'anketa.isAppOwner',
        ),

        /**
         * Метрики использования
         *
         * @var array
         */
        $metrics = array(
            'requests' => array(),
            'timeout'  => 0,
        )
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
    public function __construct($appId, $secretKey, $privateKey, $Session, $Memcache, $Redis) {
        $this->set(array(
            'app_id'      => $appId,
            'secret_key'  => $secretKey,
            'private_key' => $privateKey,
        ));

        self::$Session  = $Session;
        self::$Memcache = $Memcache;
        self::$Redis    = $Redis;

        self::$Instance = $this;
    }

    /**
     * Временное включение режиме НЕ-кеширования
     *
     * @return Mamba
     */
    public function nocache() {
        $this->noCacheMode = true;
        return $this;
    }

    /**
     * Возвращает user_id WebUser'a из сессии
     *
     * @return int
     */
    public function getWebUserId() {
        if (isset($this->options['oid'])) {
            return (int) $this->options['oid'];
        } elseif ($webUserId = self::getSession()->get(self::SESSION_USER_ID_KEY)) {
            return (int) $webUserId;
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

            $this->ready = false;

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
     * Генерирует ключ для хранения кеша, как полного, так и частичного
     *
     * @return string
     */
    private function getCacheKeys($method, $params) {
        $result = array(
            'full'    => null,
            'partial' => array(
                'oids'   => array(),
                'logins' => array(),
            ),
        );

        if (!$this->noCacheMode) {
            list($namespace, $method) = explode(".", $method);
            if (!isset($this->cachingOptions[$namespace][$method])) {
                throw new MambaException("$namespace.$method has no caching options");
            }

            $cachingOptions = $this->cachingOptions[$namespace][$method];

            if (self::CACHE_ENABLED && $cachingOptions['backend']) {
                $signed = $cachingOptions['signed'];
                $cachingKey =  "api://" . ($signed ? ($this->getWebUserId() . "@") : '') . "$namespace.$method";

                if ($getParams = http_build_query($params)) {
                    $result['full'] = $cachingKey . "/?" . $getParams;
                } else {
                    $result['full'] = $cachingKey;
                }

                if (isset($params['oids'])) {
                    $oids = $params['oids'];
                    $oids = explode(",", $oids);

                    foreach ($oids as $oid) {
                        $_params = $params;
                        $_params['oids'] = $oid;
                        $_params['partial'] = true;

                        if ($getParams = http_build_query($_params)) {
                            $result['partial']['oids'][$oid ] = $cachingKey . "/?" . $getParams;
                        }
                    }
                } elseif (isset($params['logins'])) {
                    $logins = $params['logins'];
                    $logins = explode(",", $logins);

                    foreach ($logins as $login) {
                        $_params = $params;
                        $_params['logins'] = $login;
                        $_params['partial'] = true;

                        if ($getParams = http_build_query($_params)) {
                            $result['partial']['logins'][$login] = $cachingKey . "/?" . $getParams;
                        }
                    }
                }

                return $result;
            }
        }
    }

    /**
     * Дергает кеш в соответствии с настройками
     *
     * @return string
     */
    private function getCache($method, $params) {
        if ($cachingKeys = $this->getCacheKeys($method, $params)) {
            $cachingKey = md5($cachingKeys['full']);

            list($namespace, $method) = explode(".", $method);
            $cachingBackend = $this->cachingOptions[$namespace][$method]['backend'];
            $result = null;
            if ($cachingBackend == self::REDIS_CACHE_BACKEND) {
                if (false !== $result = self::getRedis()->get($cachingKey)) {
                    $result = json_decode($result, true);
                }
            } elseif ($cachingBackend == self::MEMCACHE_CACHE_BACKEND) {
                $result = self::getMemcache()->get($cachingKey);
            } else {
                throw new MambaException("Invalid caching backend");
            }

            if (!$result) {
                $cachingKeys = $cachingKeys['partial'];
                if ($cachingKeys['oids']) {
                    $cachingKeys = $cachingKeys['oids'];
                } elseif ($cachingKeys['logins']) {
                    $cachingKeys = $cachingKeys['logins'];
                } else {
                    $cachingKeys = null;
                }

                if ($cachingKeys) {

                    if ($cachingBackend == self::REDIS_CACHE_BACKEND) {
                        $Redis = $this->getRedis();
                        $Redis->multi();
                        foreach ($cachingKeys as $cachingKey) {
                            $Redis->get(md5($cachingKey));
                        }

                        if ($result = $Redis->exec()) {
                            $n = 0;
                            foreach ($cachingKeys as $key => $cachingKey) {
                                if ($result[$n] === false) {
                                    return;
                                } else {
                                    $cachingKeys[$key] = json_decode($result[$n], true);
                                }

                                $n++;
                            }

                            return array_values($cachingKeys);
                        }
                    } elseif ($cachingBackend == self::MEMCACHE_CACHE_BACKEND) {
                        if ($result = $this->getMemcache()->getMulti(array_map(function($key){return md5($key);}, $cachingKeys))) {
                            foreach ($cachingKeys as $key => $cachingKey) {
                                $hash = md5($cachingKey);
                                if (isset($result[$hash])) {
                                    $cachingKeys[$key] = $result[$hash];
                                } else {
                                    return;
                                }
                            }

                            return array_values($cachingKeys);
                        }
                    }
                }
            } else {
                return $result;
            }
        }
    }

    /**
     * Сохраняет кеш в соответствии с настройками
     *
     * @return string
     */
    private function setCache($method, $params, $data) {
        if ($cachingKeys = $this->getCacheKeys($method, $params)) {
            $cachingKey = md5($cachingKeys['full']);

            list($namespace, $method) = explode(".", $method);
            $cachingOptions = $this->cachingOptions[$namespace][$method];
            $cachingBackend = $cachingOptions['backend'];
            if (!isset($cachingOptions['expire'])) {
                throw new MambaException('Invalid expire field');
            }
            $expire = $cachingOptions['expire'];

            if ($cachingBackend == self::REDIS_CACHE_BACKEND) {
                if ($expire) {
                    self::getRedis()->setex($cachingKey, (int)$expire, json_encode($data));
                } else {
                    self::getRedis()->set($cachingKey, json_encode($data));
                }
            } elseif ($cachingBackend == self::MEMCACHE_CACHE_BACKEND) {
                self::getMemcache()->set($cachingKey, $data, (int) $expire);
            } else {
                throw new MambaException("Invalid caching backend");
            }

            $cachingKeys = $cachingKeys['partial'];
            if ($cachingKeys['oids']) {
                $cachingKeys = $cachingKeys['oids'];
            } elseif ($cachingKeys['logins']) {
                $cachingKeys = $cachingKeys['logins'];
            } else {
                $cachingKeys = null;
            }

            if ($cachingKeys && count($cachingKeys) == count($data)) {
                $cachingKeys = array_values($cachingKeys);
                foreach ($data as $i=>$item) {
                    $cachingKey = $cachingKeys[$i];

                    if ($cachingBackend == self::REDIS_CACHE_BACKEND) {
                        if ($expire) {
                            self::getRedis()->setex(md5($cachingKey), (int)$expire, json_encode($item));
                        } else {
                            self::getRedis()->set(md5($cachingKey), json_encode($item));
                        }
                    } elseif ($cachingBackend == self::MEMCACHE_CACHE_BACKEND) {
                        self::getMemcache()->set(md5($cachingKey), $item, (int) $expire);
                    }
                }
            }
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
            if ($platformParams = $this->getPlatformSettingsObject()->get($webUserId)) {
                $this->set($platformParams);
                return $this->ready = true;
            }
        }

        return false;
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
        if (strpos($method, "\\") !== false) {
            $method = explode("\\", $method);
            $method = array_pop($method);
        }

        if (!$this->noCacheMode) {
            if ($cacheResult = $this->getCache($method, $params)) {
                if ($this->mode == self::MULTI_MODE ) {
                    $this->multiQueue[] = array(
                        'cached' => $cacheResult
                    );

                    return $this;
                }

                return $cacheResult;
            }
        }

        $requirements = array('app_id', 'format', 'secure');
        if (in_array($method, $this->sidRequiredMethods)) {

            if (!$this->getReady()) {
                throw new MambaException("Mamba is not ready to work");
            }

            $lastMambaQuery = $this->getPlatformSettingsObject()->getLastQueryTime($this->getWebUserId());
            if ($lastMambaQuery && (time() - (int)$lastMambaQuery >= 4*60*60)) {
                throw new MambaException("Sid expired");
            }

            $requirements[] = 'sid';
        }

        if ($staticParams = $this->get($requirements)) {
            $dynamicParams = array(
                'method' => $method,
            );

            $resultParams = array_merge($staticParams, $dynamicParams, $params);

            $resultParams['sig'] = ($this->get('secure'))
                ? $this->getServerToServerSignature($resultParams)
                : $this->getClientToServerSignature($resultParams);

            $httpQuery = self::PLATFORM_GATEWAY_ADDRESS . "/?" . http_build_query($resultParams);

            if ($this->mode == self::MULTI_MODE) {
                $this->multiQueue[] = array(
                    'url' => $httpQuery,
                    'method' => $method,
                    'params' => $params,
                );

                return $this;
            }

            $this->noCacheMode = false;

            $ch = curl_init($httpQuery);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);

            /** for network debug */
            #curl_setopt($ch, CURLOPT_VERBOSE, 1);
            $platformResponse = curl_exec($ch);

            if (!curl_error($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
                if (in_array($method, $this->sidRequiredMethods)) {
                    $this->getPlatformSettingsObject()->setLastQueryTime($this->getWebUserId());
                }

                $JSON = @json_decode($platformResponse, true);

                if ($JSON && $JSON['status'] === 0 && !$JSON['message']) {
                    $this->setCache($method, $params, $JSON['data']);
                    return $JSON['data'];
                } else {
                    throw new MambaException($JSON['message'], $JSON["status"]);
                }
            }

            throw new MambaException("Could not fetch platform url: $httpQuery\n" . curl_error($ch), curl_getinfo($ch, CURLINFO_HTTP_CODE));
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
     * Возвращает валидность данных пришедших от билинга
     *
     * @param array $params
     * @return bool
     */
    public function checkBillingSignature($params) {
        if (isset($params['sig'])) {
            $sig = $params['sig'];
            unset($params['sig']);

            if ($this->getServerToServerSignature($params) == $sig) {
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
     * Platform settings getter
     *
     * @return PlatformSettings
     */
    public function getPlatformSettingsObject() {
        if (isset($this->Instances[__FUNCTION__])) {
            return $this->Instances[__FUNCTION__];
        }

        return $this->Instances[__FUNCTION__] = new PlatformSettings(self::getRedis());
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
            if ($strict) {
                throw new MambaException("Request queue is empty");
            } else {
                return array();
            }
        }

        $urls = array();
        foreach ($this->multiQueue as $item) {
            if (isset($item['url'])) {
                $urls[] = $item['url'];
            }
        }

        if ($urls) {
            $platformResponses = $this->urlMultiFetch($urls, $chunkSize);

            if ($webUserId = $this->getWebUserId()) {
                $this->getPlatformSettingsObject()->setLastQueryTime($this->getWebUserId());
            }
        } else {
            $platformResponses = array();
        }

        $this->noCacheMode = false;

        if ($platformResponses) {
            foreach ($this->multiQueue as $key=>&$item) {
                if (isset($item['url']) && !isset($item['content'])) {
                    $platformResponse = $platformResponses[$item['url']];
                    $JSON = @json_decode($platformResponse, true);

                    if ($JSON && $JSON['status'] === 0 && !$JSON['message']) {
                        $this->setCache($item['method'], $item['params'], $JSON['data']);
                    } elseif ($strict) {
                        throw new MambaException($JSON['message'], $JSON['code']);
                    }

                    $item['content'] = $JSON ? $JSON['data'] : null;
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
            } else {
                $item = null;
            }
        }

        $result = $this->multiQueue;
        $this->multiQueue = array();

        return array_filter($result, function($item) {
            return (bool) $item;
        });
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
//                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
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

                /**
                 * Комментируем бросание исключения, потому что по замыслу multi-методы не должны бросать исключения
                 *
                 * @author shpizel
                 */
//                if (($code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) != 200) {
//                    throw new MambaException("$url has returned $code code");
//                }

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
}

/**
 * MambaException
 *
 * @package MambaBundle
 */
class MambaException extends \Exception {

}