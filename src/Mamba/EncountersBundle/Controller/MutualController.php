<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;

/**
 * MutualController
 *
 * @package EncountersBundle
 */
class MutualController extends Controller {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $Mamba = $this->get('Mamba');
        if ($platformSettings = $Mamba->getReady()) {
            return new Response("<h1>Mutual</h1>");
        }

        return $this->redirect($this->generateUrl('welcome'));
    }
}