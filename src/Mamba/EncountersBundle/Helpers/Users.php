<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\EncountersBundle\EncountersBundle;

use PDO;

/**
 * Class Users
 * @package Mamba\EncountersBundle\Helpers
 */
class Users extends Helper {

    const

        USER_INFO_LIFETIME = 86400,

        SQL_USERS_GET_INFO = "
            SELECT
                info.user_id as `user_id`,
                info.name as `info.name`,
                info.gender as `info.gender`,
                info.age as `info.age`,
                info.sign as `info.sign`,
                info.about as `info.about`,
                info.is_app_user as `info.is_app_user`,
                info.lang as `info.lang`,
                UNIX_TIMESTAMP(info.changed) as `info.changed`,

                orientation.orientation as `orientation`,

                avatar.small_photo_url as `avatar.small_photo_url`,
                avatar.medium_photo_url as `avatar.medium_photo_url`,
                avatar.square_photo_url as `avatar.square_photo_url`,

                location.country_id as `location.country_id`,
                location.country_name as `location.country_name`,
                location.region_id as `location.region_id`,
                location.region_name as `location.region_name`,
                location.city_id as `location.city_id`,
                location.city_name as `location.city_name`,

                flags.is_vip as `flags.is_vip`,
                flags.is_real as `flags.is_real`,
                flags.is_leader as `flags.is_leader`,
                flags.maketop as `flags.maketop`,
                flags.is_online as `flags.is_online`,

                type.height as `type.height`,
                type.weight as `type.weight`,
                type.circumstance as `type.circumstance`,
                type.constitution as `type.constitution`,
                type.smoke as `type.smoke`,
                type.drink as `type.drink`,
                type.home as `type.home`,
                type.language as `type.language`,
                type.race as `type.race`,

                familiarity.lookfor as `familiarity.lookfor`,
                familiarity.waitingfor as `familiarity.waitingfor`,
                familiarity.targets as `familiarity.targets`,
                familiarity.marital as `familiarity.marital`,
                familiarity.children as `familiarity.children`,

                interests.interests as `interests`,

                albums.albums as `albums`,
                photos.photos as `photos`

            FROM
                `UserInfo` info
            LEFT JOIN UserAvatar avatar ON
                avatar.user_id = info.user_id
            LEFT JOIN UserLocation location ON
                location.user_id = info.user_id
            LEFT JOIN UserOrientation orientation ON
                orientation.user_id = info.user_id
            LEFT JOIN UserInterests interests ON
                interests.user_id = info.user_id
            LEFT JOIN UserFlags flags ON
                flags.user_id = info.user_id
            LEFT JOIN UserType type ON
                type.user_id = info.user_id
            LEFT JOIN UserFamiliarity familiarity ON
                familiarity.user_id = info.user_id
            LEFT JOIN UserAlbums albums ON
                albums.user_id = info.user_id
            LEFT JOIN UserPhotos photos ON
                photos.user_id = info.user_id
            WHERE
                info.user_id IN (%s)
        "
    ;

    /**
     * Return info about user:
     * 1) info = [^user_id, name, gender, age, sign, about, is_app_user, lang, ^changed]
     * 2) avatar = [^user_id, small_photo_url, medium_photo_url, square_photo_url]
     * 3) location = [^user_id, country_id, country_name, region_id, region_name, city_id, city_name]
     * 4) interests = [^user_id, interests]
     * 5) flags = [^user_id, is_vip, is_real, is_leader, maketop, is_online]
     * 6) type = [^user_id, height, weight, circumstance, constitution, smoke, drink, home, language^json, race]
     * 7) familiarity = [lookfor, waitingfor, targets, marital, children]
     * 8) albums = [^user_id, albums]
     * 9) photos = [^user_id, photos]
     * x) orientation = [orientation]
     */
    public function getInfo(
        $users,
        $skipDatabase = false,
        $apiRetryCount = 1,
        $blocks = [
            'info',
            'avatar',
            'location',
            'interests',
            'flags',
            'type',
            'familiarity',
            'albums',
            'photos',
            'orientation',
        ]
    ) {
        $defaultBlocks = ['info', 'avatar', 'location', 'interests', 'flags', 'type', 'familiarity', 'albums', 'photos', 'orientation',];
        foreach ($blocks as $block) {
            if (!in_array($block, $defaultBlocks)) {
                throw new UsersException("Invalid requested block: " . var_export($block, true));
            }
        }

        if (is_int($users)) {
            $users = [$users];
        } elseif (is_array($users)) {
            foreach ($users as $k=>$userId) {
                if (!is_int($userId) && !is_numeric($userId)) {
                    throw new UsersException("Invalid user id: " . var_export(gettype($userId), true));
                } else {
$users[$k] = (int) $userId;
}
            }
        } elseif (is_numeric($users)) {
            $users = [(int) $users];
        } else {
            throw new UsersException("Invalid user id: " . var_export(gettype($users), true));
        }

        if (count($users) > 100) {
            throw new UsersException("Too much users to get: " . count($users). ", max = 100");
        }

        $result = [];
        foreach ($users as $userId) {
            $result[$userId] = null;
        }

        /**
         * Пытаемся взять данные из базы, если там нету, берем из API
         * Если данных нету - задачу на заполнение
         * Если данные просрочены - задачу на заполнение
         *
         * @author shpizel
         */
        if (!$skipDatabase) {

            /** Работа с кешем */
            $cacheKeys = [];
            foreach ($users as $userId) {
                $cacheKeys[] = "user_{$userId}_info";
            }

            if ($memcacheResult = $this->getMemcache()->getMulti($cacheKeys)) {
                foreach ($memcacheResult as $cacheKey=>$cacheResult) {
                    $cacheResult = json_decode($cacheResult, true);
                    $userId = (int) substr($cacheKey, 5, -5);

                    foreach ($defaultBlocks as $block) {
                        if (!in_array($block, $blocks)) {
                            unset($cacheResult[$block]);
                        }
                    }

                    $result[$userId] = $cacheResult;
                    unset($users[array_search($userId, $users)]);
                }
            }


            if ($users) {
                $stmt = $this->getEntityManager()->getConnection()->prepare(
                    sprintf(
                        self::SQL_USERS_GET_INFO,
                        implode(", ", $users)
                    )
                );

                $usersToUpdate = [];
                if ($stmt->execute()) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $result[$userId = $row['user_id']] = [];

                        if (time() - $row['info.changed'] > self::USER_INFO_LIFETIME) {
                            $usersToUpdate[] = $userId;
                        }

                        /**
                         * info block
                         *
                         * [name, gender, age, sign, about, is_app_user, lang]
                         */
                        if (in_array('info', $blocks)) {
                            $result[$userId]['info'] = [
                                'user_id'     => (int) $row['user_id'],
                                'name'        => $row['info.name'],
                                'gender'      => $row['info.gender'],
                                'age'         => (int) $row['info.age'],
                                'sign'        => $row['info.sign'],
                                'about'       => $row['info.about'],
                                'is_app_user' => $row['info.is_app_user'] == 1,
                                'lang'        => $row['info.lang'],
                            ];
                        }

                        /** orientation block */
                        if (in_array('orientation', $blocks)) {
                            $result[$userId]['orientation'] = $row['orientation'];
                        }

                        /**
                         * avatar block
                         *
                         * [small_photo_url, medium_photo_url, square_photo_url]
                         */
                        if (in_array('avatar', $blocks)) {
                            $result[$userId]['avatar'] = [
                                'small_photo_url'  => $row['avatar.small_photo_url'],
                                'medium_photo_url' => $row['avatar.medium_photo_url'],
                                'square_photo_url' => $row['avatar.square_photo_url'],
                            ];
                        }

                        /**
                         * location block
                         *
                         * [country_id, country_name, region_id, region_name, city_id, city_name]
                         */
                        if (in_array('location', $blocks)) {
                            $result[$userId]['location'] = [
                                'country' => [
                                    'id'   => $row['location.country_id'],
                                    'name' => $row['location.country_name'],
                                ],
                                'region' => [
                                    'id'   => $row['location.region_id'],
                                    'name' => $row['location.region_name'],
                                ],
                                'city' => [
                                    'id'   => $row['location.city_id'],
                                    'name' => $row['location.city_name'],
                                ],
                            ];
                        }

                        /** interests block */
                        if (in_array('interests', $blocks)) {
                            $result[$userId]['interests'] = json_decode($row['interests']);
                        }

                        /**
                         * flags block
                         *
                         * [is_vip, is_real, is_leader, maketop, is_online]
                         */
                        if (in_array('flags', $blocks)) {
                            $result[$userId]['flags'] = [
                                'is_vip'    => (int) $row['flags.is_vip'],
                                'is_real'   => (int) $row['flags.is_real'],
                                'is_leader' => (int) $row['flags.is_leader'],
                                'maketop'   => (int) $row['flags.maketop'],
                                'is_online' => (int) $row['flags.is_online'],
                            ];
                        }

                        /**
                         * type block
                         *
                         * [height, weight, circumstance, constitution, smoke, drink, home, language^json, race]
                         */
                        if (in_array('type', $blocks)) {
                            $result[$userId]['type'] = [
                                'height'       => (int) $row['type.height'],
                                'weight'       => (int) $row['type.weight'],
                                'circumstance' => $row['type.circumstance'],
                                'constitution' => $row['type.constitution'],
                                'smoke'        => $row['type.smoke'],
                                'drink'        => $row['type.drink'],
                                'home'         => $row['type.home'],
                                'language'     => json_decode($row['type.language']),
                                'race'         => $row['type.race'],
                            ];
                        }

                        /**
                         * familiarity block
                         *
                         * [lookfor, waitingfor, targets, marital, children]
                         */
                        if (in_array('familiarity', $blocks)) {
                            $result[$userId]['familiarity'] = [
                                'lookfor'    => $row['familiarity.lookfor'],
                                'waitingfor' => $row['familiarity.waitingfor'],
                                'targets'    => json_decode($row['familiarity.targets']),
                                'marital'    => $row['familiarity.marital'],
                                'children'   => $row['familiarity.children'],
                            ];
                        }

                        /** albums block */
                        if (in_array('albums', $blocks)) {
                            $result[$userId]['albums'] = json_decode($row['albums'], true);
                        }

                        /** photos block */
                        if (in_array('photos', $blocks)) {
                            $result[$userId]['photos'] = json_decode($row['photos'], true);
                        }
                    }
                }

                /** Отправим задачу в очередь на заполнение БД */
                $usersToUpdate && $this->getGearman()->getClient()->doLowBackground(
                    EncountersBundle::GEARMAN_DATABASE_USERS_UPDATE_FUNCTION_NAME,
                    serialize($dataArray = array(
                        'users' => $usersToUpdate,
                        'time'  => time(),
                    ))
                );
            }
        }

        /** прошла обработка базы данных, смотрим где нулы и работаем с ними */
        $users = [];
        foreach ($result as $userId=>$dataArray) {
            if (null === $dataArray) {
                $users[] = $userId;
            }
        }

        if ($users) {
            $Mamba = $this->getMamba();
            $platformResult = null;
            foreach (range(1, $apiRetryCount) as $try) {
                try {
                    $platformResult = $Mamba->Anketa()->getInfo($users);
                    break;
                } catch (\Exception $e) {
                    sleep(mt_rand(1,5));
                }
            }


            if ($platformResult) {

                /**
                 * Multi prefetch albums and photos
                 *
                 * @author shpizel
                 */
                $photosPrefetched = $albumsPrefetched = [];

                $Mamba->multi();
                foreach ($users as $userId) {
                    $Mamba->nocache()->Photos()->get($userId);
                }

                if ($photosResults = $Mamba->exec()) {
                    foreach ($photosResults as $photosResultKey => $photosResult) {
                        $photosPrefetched[$users[$photosResultKey]] = $photosResult;
                    }
                }

                $Mamba->multi();
                foreach ($users as $userId) {
                    $Mamba->Photos()->getAlbums($userId);
                }

                if ($albumsResults = $Mamba->exec()) {
                    foreach ($albumsResults as $albumsResultKey => $albumsResult) {
                        $albumsPrefetched[$users[$albumsResultKey]] = $albumsResult;
                    }
                }

                /** prefetch completed */

                foreach ($platformResult as $dataArray) {
                    $result[$userId = $dataArray['info']['oid']] = [];

                    /**
                     * info block
                     *
                     * [name, gender, age, sign, about, is_app_user, lang]
                     */
                    if (in_array('info', $blocks)) {
                        $result[$userId]['info'] = [
                            'user_id'     => (int) $dataArray['info']['oid'],
                            'name'        => $dataArray['info']['name'],
                            'gender'      => $dataArray['info']['gender'],
                            'age'         => (int) $dataArray['info']['age'],
                            'sign'        => $dataArray['info']['sign'],
                            'about'       => $dataArray['about'],
                            'is_app_user' => $dataArray['info']['is_app_user'] == 1,
                            'lang'        => $dataArray['info']['lang'],
                        ];
                    }

                    /** orientation block */
                    if (in_array('orientation', $blocks)) {
                        $result[$userId]['orientation'] = 1;//по-умолчанию гетеро

                        $SearchPreferences = new SearchPreferences($this->Container);
                        if ($userSearchPreferences = $SearchPreferences->get($userId)) {
                            $result[$userId]['orientation'] = intval(
                                $userSearchPreferences['gender'] != $dataArray['info']['gender']
                            );
                        } elseif (
                            ($dataArray['info']['gender'] == 'M') &&
                            isset($dataArray['familiarity']['lookfor']) &&
                            preg_match("!парнем!", $dataArray['familiarity']['lookfor'])
                        ) {
                            $result[$userId]['orientation'] = 0; //gay
                        } elseif (
                            ($dataArray['info']['gender'] == 'F') &&
                            isset($dataArray['familiarity']['lookfor']) &&
                            preg_match("!девуш!", $dataArray['familiarity']['lookfor'])
                        ) {
                            $result[$userId]['orientation'] = 0; //lesbo
                        }
                    }

                    /**
                     * avatar block
                     *
                     * [small_photo_url, medium_photo_url, square_photo_url]
                     */
                    if (in_array('avatar', $blocks)) {
                        $result[$userId]['avatar'] = [
                            'small_photo_url'  => $dataArray['info']['small_photo_url'],
                            'medium_photo_url' => $dataArray['info']['medium_photo_url'],
                            'square_photo_url' => $dataArray['info']['square_photo_url'],
                        ];
                    }

                    /**
                     * location block
                     *
                     * [country_id, country_name, region_id, region_name, city_id, city_name]
                     */
                    if (in_array('location', $blocks)) {
                        $result[$userId]['location'] = [
                            'country' => [
                                'id'   => $dataArray['location']['country_id'],
                                'name' => $dataArray['location']['country'],
                            ],
                            'region' => [
                                'id'   => $dataArray['location']['region_id'],
                                'name' => $dataArray['location']['region'],
                            ],
                            'city' => [
                                'id'   => $dataArray['location']['city_id'],
                                'name' => $dataArray['location']['city'],
                            ],
                        ];
                    }

                    /** interests block */
                    if (in_array('interests', $blocks)) {
                        $result[$userId]['interests'] = $dataArray['interests'];
                    }

                    /**
                     * flags block
                     *
                     * [is_vip, is_real, is_leader, maketop, is_online]
                     */
                    if (in_array('flags', $blocks)) {
                        $result[$userId]['flags'] = [
                            'is_vip'    => (int) $dataArray['flags']['is_vip'],
                            'is_real'   => (int) $dataArray['flags']['is_real'],
                            'is_leader' => (int) $dataArray['flags']['is_leader'],
                            'maketop'   => (int) $dataArray['flags']['maketop'],
                            'is_online' => (int) $dataArray['flags']['is_online'],
                        ];
                    }

                    /**
                     * type block
                     *
                     * [height, weight, circumstance, constitution, smoke, drink, home, language^json, race]
                     */
                    if (in_array('type', $blocks)) {
                        $result[$userId]['type'] = [
                            'height'       => isset($dataArray['type']['height']) ? (int) $dataArray['type']['height'] : null,
                            'weight'       => isset($dataArray['type']['weight']) ? (int) $dataArray['type']['weight'] : null,
                            'circumstance' => isset($dataArray['type']['circumstance']) ? $dataArray['type']['circumstance'] : null,
                            'constitution' => isset($dataArray['type']['constitution']) ? $dataArray['type']['constitution'] : null,
                            'smoke'        => isset($dataArray['type']['smoke']) ? $dataArray['type']['smoke'] : null,
                            'drink'        => isset($dataArray['type']['drink']) ? $dataArray['type']['drink'] : null,
                            'home'         => isset($dataArray['type']['home']) ? $dataArray['type']['home'] : null,
                            'language'     => isset($dataArray['type']['language']) ? $dataArray['type']['language'] : null,
                            'race'         => isset($dataArray['type']['race']) ? $dataArray['type']['race'] : null,
                        ];
                    }

                    /**
                     * familiarity block
                     *
                     * [lookfor, waitingfor, targets, marital, children]
                     */
                    if (in_array('familiarity', $blocks)) {
                        $result[$userId]['familiarity'] = [
                            'lookfor'    => isset($dataArray['familiarity']['lookfor']) ? $dataArray['familiarity']['lookfor'] : null,
                            'waitingfor' => isset($dataArray['familiarity']['waitingfor']) ? $dataArray['familiarity']['waitingfor'] : null,
                            'targets'    => isset($dataArray['familiarity']['targets']) ? $dataArray['familiarity']['targets'] : null,
                            'marital'    => isset($dataArray['familiarity']['marital']) ? $dataArray['familiarity']['marital'] : null,
                            'children'   => isset($dataArray['familiarity']['children']) ? $dataArray['familiarity']['children'] : null,
                        ];
                    }

                    /** albums block */
                    if (in_array('albums', $blocks)) {
                        $result[$userId]['albums'] = [];
                        if ($albums = /*$Mamba->Photos()->getAlbums*/$albumsPrefetched[$userId]) {
                            foreach ($albums['albums'] as $album) {
                                $result[$userId]['albums'][] = [
                                    'album_id' => $album['album_id'],
                                    'name'     => $album['name'],
                                    'type'     => $album['type'],
                                ];
                            }
                        }
                    }

                    /** photos block */
                    if (in_array('photos', $blocks)) {
                        $result[$userId]['photos'] = [];
                        if ($photos = /*$Mamba->Photos()->get*/$photosPrefetched[$userId]) {
                            foreach ($photos['photos'] as $photo) {
                                $result[$userId]['photos'][] = [
                                    'photo_id'         => $photo['photo_id'],
                                    'name'             => $photo['name'],
                                    'small_photo_url'  => $photo['small_photo_url'],
                                    'medium_photo_url' => $photo['medium_photo_url'],
                                    'square_photo_url' => $photo['square_photo_url'],
                                    'huge_photo_url'   => $photo['huge_photo_url'],
                                ];
                            }
                        }
                    }
                }
            }

            /**
             * Задачу добавляем только в случае когда в базе инфы нету
             * В случае если запрашивается пропуск базы — мы не знаем есть в базе или нет
             *
             * @author shpizel
             */
            if ($users && !$skipDatabase) {
                /** Отправим задачу в очередь на заполнение БД */
                $this->getGearman()->getClient()->doLowBackground(
                    EncountersBundle::GEARMAN_DATABASE_USERS_UPDATE_FUNCTION_NAME,
                    serialize($dataArray = array(
                        'users' => $users,
                        'time'  => time(),
                    ))
                );
            }
        }

        return array_filter($result, function($item) {
            return (bool) $item;
        });
    }
}

/**
 * Class Users
 * @package Mamba\EncountersBundle\Helpers
 */
class UsersException extends \Exception {

}
