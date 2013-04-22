<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;

/**
 * AdminNginxController
 *
 * @package EncountersBundle
 */
class AdminNginxController extends ApplicationController {

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
                $HTTPRequest = "GET /nginx-status HTTP/1.1\r\n";
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

                if (preg_match("!Active\s*connections:\s*(?P<active_connections>\d+).*?server\s*accepts\s*handled\s*requests.*?\s+(?P<accepts>\d+)\s+(?P<handled>\d+)\s+(?P<requests>\d+).*?Reading:\s+(?P<reading>\d+)\s+Writing:\s+(?P<writing>\d+)\s+Waiting:\s+(?P<waiting>\d+)!is", $HTTPResponse, $result)) {
                    $items[$serverName/* . ":" . $ips['ext']*/] = $result;
                }
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

        return $this->TwigResponse('EncountersBundle:templates:admin.nginx.html.twig', $dataArray);
    }
}