<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;
use PDO;

/**
 * AdminEventsController
 *
 * @package EncountersBundle
 */
class AdminEventsController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($limit = 10) {
        $dataArray = array(
            'common' => array(
                'items' => array(),
                'info'  => array(
                    'achievement' => 0,
                    'notify'      => 0,

                    'limit' => $limit,
                ),
            ),

            'decision_get' => array(
                'items' => array(),
                'info'  => array(
                    'decision.get-battery.notrequired' => 0,
                    'decision.get-battery.charge'      => 0,
                    'decision.get-battery.decr'        => 0,
                    'decision.get-battery.empty'       => 0,

                    'limit' => $limit,
                ),
            ),

            'battery' => array(
                'items' => array(),
                'info'  => array(
                    'battery-decr' => 0,
                    'battery-incr' => 0,

                    'limit' => $limit,
                ),
            ),

            'account' => array(
                'items' => array(),
                'info'  => array(
                    'account-decr' => 0,
                    'account-incr' => 0,

                    'limit' => $limit,
                ),
            ),

            'photoline' => array(
                'items' => array(),
                'info'  => array(
                    'photoline-add'   => 0,
                    'photoline-click' => 0,

                    'limit' => $limit,
                ),
            ),

            'profile' => array(
                'items' => array(),
                'info'  => array(
                    'profile-hits'   => 0,
                    'gifts-sent' => 0,

                    'limit' => $limit,
                ),
            ),

            'messenger' => array(
                'items' => array(),
                'info'  => array(
                    'messages-sent'   => 0,
                    'contacts-created' => 0,
                    'mutual-messages-sent' => 0,

                    'limit' => $limit,
                ),
            ),

            'traffic' => array(
                'items' => array(),
                'info'  => array(
                    'ref-notifications' => 0,
                    'ref-achievement' => 0,

                    'limit' => $limit,
                ),
            ),
        );

        $Redis = $this->getRedis();
        $Redis->multi();
        foreach (range(0, $limit - 1) as $day) {
            $Redis->hGetAll("stats_by_" . ($date = date("dmy", strtotime("-$day day"))));
        }

        if ($data = $Redis->exec()) {
            foreach ($data as $key=>$item) {
                foreach ($requiredKeys = array('achievement', 'notify') as $_key) {
                    if (!isset($item[$_key])) {
                        $item[$_key] = null;
                    } else {
                        $dataArray['common']['info'][$_key] += $item[$_key];
                    }
                }

                /** фильтруем item */
                foreach ($item as $ikey=>$val) {
                    if (!in_array($ikey, $requiredKeys)) {
                        unset($item[$ikey]);
                    }
                }

                $dataArray['common']['items'][] = array(
                    'date' => date('Y-m-d', strtotime("-$key day")),
                    'ts'   => strtotime("-$key day"),
                    'item' => $item,
                );
            }
        }

        /**
         * Decision get metrics
         *
         * @author shpizel
         */

        foreach ($data as $key=>$item) {
            foreach (
                $requiredKeys = array(
                    'decision.get-battery.notrequired',
                    'decision.get-battery.charge',
                    'decision.get-battery.decr',
                    'decision.get-battery.empty'
                ) as $_key
            ) {
                if (!isset($item[$_key])) {
                    $item[$_key] = null;
                } else {
                    $dataArray['decision_get']['info'][$_key] += $item[$_key];
                }
            }

            /** фильтруем item */
            foreach ($item as $ikey=>$val) {
                if (!in_array($ikey, $requiredKeys)) {
                    unset($item[$ikey]);
                }
            }

            $dataArray['decision_get']['items'][] = array(
                'date' => date('Y-m-d', strtotime("-$key day")),
                'ts'   => strtotime("-$key day"),
                'item' => $item,
            );
        }

        /**
         * Account metrics
         *
         * @author shpizel
         */

        foreach ($data as $key=>$item) {
            foreach ($requiredKeys = array('account-decr','account-incr') as $_key) {
                if (!isset($item[$_key])) {
                    $item[$_key] = null;
                } else {
                    $dataArray['account']['info'][$_key] += $item[$_key];
                }
            }

            /** фильтруем item */
            foreach ($item as $ikey=>$val) {
                if (!in_array($ikey, $requiredKeys)) {
                    unset($item[$ikey]);
                }
            }

            $dataArray['account']['items'][] = array(
                'date' => date('Y-m-d', strtotime("-$key day")),
                'ts'   => strtotime("-$key day"),
                'item' => $item,
            );
        }

        /**
         * Battery metrics
         *
         * @author shpizel
         */

        foreach ($data as $key=>$item) {
            foreach ($requiredKeys = array('battery-decr','battery-incr') as $_key) {
                if (!isset($item[$_key])) {
                    $item[$_key] = null;
                } else {
                    $dataArray['battery']['info'][$_key] += $item[$_key];
                }
            }

            /** фильтруем item */
            foreach ($item as $ikey=>$val) {
                if (!in_array($ikey, $requiredKeys)) {
                    unset($item[$ikey]);
                }
            }

            $dataArray['battery']['items'][] = array(
                'date' => date('Y-m-d', strtotime("-$key day")),
                'ts'   => strtotime("-$key day"),
                'item' => $item,
            );
        }

        /**
         * Photoline metrics
         *
         * @author shpizel
         */

        foreach ($data as $key=>$item) {
            foreach ($requiredKeys = array(
                'photoline-add',
                'photoline-click') as $_key) {
                if (!isset($item[$_key])) {
                    $item[$_key] = null;
                } else {
                    $dataArray['photoline']['info'][$_key] += $item[$_key];
                }
            }

            /** фильтруем item */
            foreach ($item as $ikey=>$val) {
                if (!in_array($ikey, $requiredKeys)) {
                    unset($item[$ikey]);
                }
            }

            $dataArray['photoline']['items'][] = array(
                'date' => date('Y-m-d', strtotime("-$key day")),
                'ts'   => strtotime("-$key day"),
                'item' => $item,
            );
        }

        foreach ($data as $key=>$item) {
            foreach ($requiredKeys = array(
                'profile-hits',
                'gifts-sent') as $_key) {
                if (!isset($item[$_key])) {
                    $item[$_key] = null;
                } else {
                    $dataArray['profile']['info'][$_key] += $item[$_key];
                }
            }

            /** фильтруем item */
            foreach ($item as $ikey=>$val) {
                if (!in_array($ikey, $requiredKeys)) {
                    unset($item[$ikey]);
                }
            }

            $dataArray['profile']['items'][] = array(
                'date' => date('Y-m-d', strtotime("-$key day")),
                'ts'   => strtotime("-$key day"),
                'item' => $item,
            );
        }

        foreach ($data as $key=>$item) {
            foreach ($requiredKeys = array(
                'messages-sent',
                'contacts-created',
                'mutual-messages-sent') as $_key) {
                if (!isset($item[$_key])) {
                    $item[$_key] = null;
                } else {
                    $dataArray['messenger']['info'][$_key] += $item[$_key];
                }
            }

            /** фильтруем item */
            foreach ($item as $ikey=>$val) {
                if (!in_array($ikey, $requiredKeys)) {
                    unset($item[$ikey]);
                }
            }

            $dataArray['messenger']['items'][] = array(
                'date' => date('Y-m-d', strtotime("-$key day")),
                'ts'   => strtotime("-$key day"),
                'item' => $item,
            );
        }

        foreach ($data as $key=>$item) {
            foreach ($requiredKeys = array(
                'ref-notifications',
                'ref-achievement') as $_key) {
                if (!isset($item[$_key])) {
                    $item[$_key] = null;
                } else {
                    $dataArray['traffic']['info'][$_key] += $item[$_key];
                }
            }

            /** фильтруем item */
            foreach ($item as $ikey=>$val) {
                if (!in_array($ikey, $requiredKeys)) {
                    unset($item[$ikey]);
                }
            }

            $dataArray['traffic']['items'][] = array(
                'date' => date('Y-m-d', strtotime("-$key day")),
                'ts'   => strtotime("-$key day"),
                'item' => $item,
            );
        }

        $dataArray['controller'] = $this->getControllerName(__CLASS__);

        return $this->TwigResponse('EncountersBundle:templates:admin.events.html.twig', $dataArray);
    }
}