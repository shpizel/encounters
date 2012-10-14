<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;
use PDO;

/**
 * AdminGearmanController
 *
 * @package EncountersBundle
 */
class AdminGearmanController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $items = array();
        foreach ($this->getGearman()->getNodes() as $node) {
            if (isset($dataArray[$node->getHost() . ":" . $node->getPort()])) {
                return;
            }

            $dataArray[$node->getHost() . ":" . $node->getPort()] = array();

            if (null != $handle = fsockopen($node->getHost(), $node->getPort(), $errorNumber, $errorString, 30)) {
                fwrite($handle,"status\n");
                while (!feof($handle)) {
                    $line = fgets($handle, 4096);
                    if( $line==".\n"){
                        break;
                    }

                    if( preg_match("~^(.*)[ \t](\d+)[ \t](\d+)[ \t](\d+)~",$line,$matches) ){
                        $function = $matches[1];
                        $items[$node->getHost() . ":" . $node->getPort()][$function] = array(
                            'function' => $function,
                            'total' => $matches[2],
                            'active' => $matches[3],
                            'workers' => $matches[4],
                        );
                    }
                }
            }
        }

        /** Отсортируем items чтобы во всех инстансах было одинаково */
        foreach ($items as &$item) {
            uasort($item, function($a, $b) {
                return strcmp($a['function'], $b['function']);
            });
        }

        $dataArray['items'] = $items;
        $dataArray['keys'] = array_keys($items);
        $dataArray['controller'] = $this->getControllerName(__CLASS__);

        return $this->render('EncountersBundle:templates:admin.gearman.html.twig', $dataArray);
    }
}