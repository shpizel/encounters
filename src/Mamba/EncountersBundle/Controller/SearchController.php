<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\PlatformBundle\API\Mamba;

/**
 * SearchController
 *
 * @package EncountersBundle
 */
class SearchController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $Mamba = $this->get('Mamba');
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $data = array();
        $data['platformSettings'] = json_encode($this->getPlatformSettingsObject()->get($webUserId = (int) $Mamba->get('oid')));
        $data['searchPreferences'] = json_encode($preferences = $this->getPreferencesObject()->get($webUserId));
        $data['who'] = array(
            'instrumental' => $preferences['gender'] == 'F' ? 'ней' : 'ним',
            'nominative' => $preferences['gender'] == 'F' ? 'она' : 'он'
        );
        $data['stats'] = array();
        $data['stats']['charge'] = $this->getBatteryObject()->get($webUserId);
        $data['stats']['mychoice'] = 10;
        $data['stats']['visitors'] = 10;
        $data['stats']['mutual'] = 10;


        return $this->render("EncountersBundle:templates:search.html.twig", $data);
    }
}