<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;

/**
 * OptionsController
 *
 * @package EncountersBundle
 */
class OptionsController extends Controller {

    public function indexAction() {
        $Mamba = $this->get('mamba');
        if ($platformSettings = $Mamba->getPlatformSettings()) {
            $familiarity = $Mamba->Anketa()->getInfo($platformSettings['oid'], array('familiarity'));
            header('content-type: text/html; charset=utf8');
            if (preg_match("!(\d+)\D(\d+)\sлет!i", $familiarity[0]['familiarity']['lookfor'], $r)) {
                //var_dump($r);
            }

            $s = $Mamba->Search()->get('M', 'F');
            $total = $s['total'];

            $Mamba->multi();
            for ($i=1;$i<$total/12;$i++)
            {
                $Mamba->Search()->get('M', 'F', null, null, null, null, null, null, null, null, null, null, null, 12);
                break;
            }


            $x = $Mamba->exec();
            var_dump($x[0]);
            exit();
            var_dump($Mamba->exec());

            exit();
        }

        exit('ok');
    }
}