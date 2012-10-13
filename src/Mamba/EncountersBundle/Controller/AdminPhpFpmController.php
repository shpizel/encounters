<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;

/**
 * AdminPhpFpmController
 *
 * @package EncountersBundle
 */
class AdminPhpFpmController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $items = array();
        $wwwServers = $this->getServers()->getServers('www');
        foreach ($wwwServers as $serverName=>$ips) {

            if ($HTTPConnection = fsockopen($ips['ext'], 80, $errno, $errstr, 5)) {
                $HTTPRequest = "GET /fpm-status?json HTTP/1.1\r\n";
                $HTTPRequest .= "Host: meetwithme.ru\r\n";
                $HTTPRequest .= "Connection: Close\r\n\r\n";
                fwrite($HTTPConnection, $HTTPRequest);

                $HTTPResponse = "";
                while (!feof($HTTPConnection)) {
                    $HTTPResponse.= fgets($HTTPConnection, 128);
                }
                fclose($HTTPConnection);

                /** Обрежем HTTP header */
                if (($contentStartingPoint = strpos($HTTPResponse, $marker = "\r\n\r\n")) !== false) {
                    $HTTPResponse = substr($HTTPResponse, $contentStartingPoint + strlen($marker));
                }

                /** Отрезаем чанки (быдлокодер ага) */
                $HTTPResponse = preg_replace_callback(
                    '/(?:(?:\r\n|\n)|^)([0-9A-F]+)(?:\r\n|\n){1,2}(.*?)'.
                        '((?:\r\n|\n)(?:[0-9A-F]+(?:\r\n|\n))|$)/si',
                    create_function(
                        '$matches',
                        'return hexdec($matches[1]) == strlen($matches[2]) ? $matches[2] : $matches[0];'
                    ),
                    $HTTPResponse
                );

                $items[$serverName] = json_decode($HTTPResponse, true);
            } else {
                $items[$serverName . ":" . $ips['ext']] = array(
                    'error' => array(
                        'code' => $errno,
                        'msg'  => $errstr,
                    ),
                );
            }
        }

        $dataArray['items'] = $items;
        $dataArray['keys'] = array_keys($items);
        $dataArray['controller'] = $this->getControllerName(__CLASS__);

        return $this->render('EncountersBundle:templates:admin.phpfpm.html.twig', $dataArray);
    }
}