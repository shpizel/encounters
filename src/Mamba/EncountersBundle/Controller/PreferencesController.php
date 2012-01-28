<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;

/**
 * PreferencesController
 *
 * @package EncountersBundle
 */
class PreferencesController extends Controller {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $Request = $this->getRequest();
        $Mamba = $this->get('Mamba');
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $searchPreferences = $this->get('redis')->hGetAll(sprintf(Mamba::REDIS_HASH_USER_SEARCH_PREFERENCES_KEY, $Mamba->get('oid')));

        if ($Request->getMethod() == 'POST') {
            $postParams = $Request->request->all();
            $requiredParams = array('gender', 'age_from', 'age_to');
            if (count(array_intersect(array_keys($postParams), $requiredParams)) == count($requiredParams)) {
                $searchPreferences = array();
                foreach ($requiredParams as $param) {
                    $searchPreferences[$param] = $postParams[$param];
                }

                $searchPreferences['age_from'] = intval($searchPreferences['age_from']);
                $searchPreferences['age_to'] = intval($searchPreferences['age_to']);

                if (in_array($searchPreferences['gender'], array('M', 'F')) && $searchPreferences['age_from'] >= 18 && $searchPreferences['age_to'] <= 80 && $searchPreferences['age_to'] >= 18) {
                    foreach ($searchPreferences as $key=>$value) {
                        $this->get('redis')->hSet(sprintf(Mamba::REDIS_HASH_USER_SEARCH_PREFERENCES_KEY, $Mamba->get('oid')), $key, $value);
                    }
                    return $this->redirect($this->generateUrl('welcome'));
                }
            }
        }

        /**
         * У нас могут быть настройки в Redis'e, но может их не быть (значит надо получить)
         *
         * @author shpizel
         */
        if (!$searchPreferences) {
            if ($anketaInfo = $Mamba->Anketa()->getInfo($Mamba->get('oid'))) {
                $anketaInfo = array_shift($anketaInfo);
                if (isset($anketaInfo['info']['gender']) && isset($anketaInfo['familiarity']['lookfor'])) {
                    $lookfor = $anketaInfo['familiarity']['lookfor'];
                    $gender = $anketaInfo['info']['gender'];

                    $searchPreferences = array(
                        'gender'   => $gender == 'F' ? 'M' : 'F',
                    );

                    if (preg_match("!(\d+)\D(\d+)\sлет!i", $lookfor, $ages)) {
                        $searchPreferences['age_to'] = (int) array_pop($ages);
                        $searchPreferences['age_from'] = (int) array_pop($ages);
                    }
                } elseif (isset($anketaInfo['info']['gender']) && isset($anketaInfo['info']['age'])) {
                    list($gender, $age) = array(
                        $anketaInfo['info']['gender'],
                        $anketaInfo['info']['age'],
                    );

                    $searchPreferences = array(
                        'gender'   => $gender == 'F' ? 'M' : 'F',
                        'age_from' => $age - 5,
                        'age_to'   => $age + 5,
                    );

                } else {
                    throw new \LogicException("Could not get search preferences");
                }
            } else {
                throw new \LogicException("Could not get search preferences");
            }
        }

        return $this->render('EncountersBundle:Preferences:preferences.html.twig', $searchPreferences);
    }
}

/**
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
 */