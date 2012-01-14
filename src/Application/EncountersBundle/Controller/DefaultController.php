<?php

namespace Application\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {

    /**
     * Точка входа
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {

        /**
         * Необходимые гет-параметры для старта приложения
         *
         * @var array
         */
        $requiredGetParams = array(
            'app_id',
            'oid',
            'auth_key',
            'sid',
            'partner_url',
        );

        $API = $this->get('mamba_api');
        foreach ($requiredGetParams as $param) {
            $API->setOption($param, $this->getRequest()->get($param));
        }

        var_dump('<pre>' . var_export($this->get('mamba_api')->Geo()->getMetro(4400)));#->getInfo(array(706566852, 475401026, (int)$this->getRequest()->get('oid'))),1) . "</pre>");
        //$this->get('mamba_api');
        exit();
        return new Response(var_export(get_class_vars(get_class($this->get('mamba_api'))),1));

        $Session = $this->get('session');
        if (count(array_intersect(array_keys($Session->all()), $requiredGetParams)) == count($requiredGetParams)) {
            //в сессии уже есть эти параметры
        } else {
            $getParams = array();
            foreach ($requiredGetParams as $requiredGetParam) {
                if ($this->getRequest()->query->has($requiredGetParam)) {
                    $Session->set(
                        $requiredGetParam,
                        $getParams[$requiredGetParam] = $this->getRequest()->query->get($requiredGetParam)
                    );
                } else {
                    $this->redirect("/");
                }
            }
        }

        return new Response(
            "<pre>" .
            var_export($Session->all(), 1).
            "</pre>"
        );
        //    ;('ApplicationEncountersBundle:Default:index.html.twig', array('name' => 1));
    }
}
