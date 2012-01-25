<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;

/**
 * JsonApiController
 *
 * @package EncountersBundle
 */
class JsonApiController extends Controller {

    /**
     * Index action
     *
     * @param $method
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($method) {
        $JSON = array('error' => true, 'message' => "Invalid params");
        try {
            if ($params = $this->getRequest()->query->get('params')) {
                $JSON = $this->get('mamba')->execute($method, json_decode($params) ?: array());
            }
        } catch (\Exception $e) {
            $JSON = array('error' => true, 'message' => $e->getMessage());
        }

        return new Response(json_encode($JSON), 200, array("application/json"));
    }
}