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
        $dataArray = array('servers' => array());
        foreach ($this->getGearman()->getNodes() as $node) {
            $dataArray['servers'][$node->getHost() . ":" . $node->getPort()] = array();

            if (null != $handle = fsockopen($node->getHost(), $node->getPort(), $errorNumber, $errorString, 30)) {
                fwrite($handle,"status\n");
                while (!feof($handle)) {
                    $line = fgets($handle, 4096);
                    if( $line==".\n"){
                        break;
                    }

                    if( preg_match("~^(.*)[ \t](\d+)[ \t](\d+)[ \t](\d+)~",$line,$matches) ){
                        $function = $matches[1];
                        $dataArray['servers'][$node->getHost() . ":" . $node->getPort()][$function] = array(
                            'function' => $function,
                            'total' => $matches[2],
                            'active' => $matches[3],
                            'workers' => $matches[4],
                        );
                    }
                }
            }
        }

        $dataArray['controller'] = $this->getControllerName(__CLASS__);

        return $this->render('EncountersBundle:templates:admin.gearman.html.twig', $dataArray);
    }
}