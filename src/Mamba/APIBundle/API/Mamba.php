<?php

namespace Mamba\APIBundle\API;

final class Mamba {

    const

        /**
         * Шлюз платформы
         *
         * @var string
         */
        PLATFORM_GATEWAY_ADDRESS = 'http://api.aplatform.ru'
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
        $Instances = array()
    ;

    protected static

        /**
         * Инстанс для удаленного выполнения процедур
         *
         * @var Mamba
         */
        $Instance
    ;

    /**
     * Конструктор
     *
     * @param $secretKey
     * @param $privateKey
     */
    public function __construct($secretKey, $privateKey) {
        $this->setOption('secret_key', $secretKey);
        $this->setOption('private_key', $privateKey);

        self::$Instance = $this;
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
        if ($staticParams = $this->getOptions(array('app_id', 'format', 'secure', 'sid'))) {
            if (strpos($method, "\\") !== false) {
                $method = explode("\\", $method);
                $method = array_pop($method);
            }

            $dynamicParams = array(
                'method' => $method,
            );

            $resultParams = array_merge($staticParams, $dynamicParams, $params);

            $resultParams['sig'] = ($this->getOption('secure'))
                ? $this->getServerToServerSignature($resultParams)
                : $this->getClientToServerSignature($resultParams);

            $httpQuery = self::PLATFORM_GATEWAY_ADDRESS . "?" . http_build_query($resultParams);

            if ($platformResponse = @file_get_contents($httpQuery)) {
                $JSON = @json_decode($platformResponse, true);

                if ($JSON['status'] === 0 && !$JSON['message']) {
                    return $JSON['data'];
                } else {
                    throw new MambaException($JSON["status"] . ": " . $JSON['message']);
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
}

/**
 * MambaAppPlatformException
 *
 * @package Mamba
 */
class MambaException extends \Exception {

}