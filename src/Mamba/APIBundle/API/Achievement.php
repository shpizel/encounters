<?php

namespace Mamba\APIBundle\API;

class Achievement {

    /**
     * Обновить запись на доске достижений
     *
     * @param string $caption
     * @param string $params строка дополнительных параметров, которые будут переданы в ссылку на приложение, в выводе данных метода. максимальная длина 255 символов.
     * @return array
     */
    public function set($caption, $params = null) {
        $arguments = array(
            'text' => $caption,
        );

        if (!is_string($caption)) {
            throw new AchievementException("Invalid achievement caption type, expected string");
        }

        if ($params) {
            if (!is_string($params)) {
                throw new AchievementException("Invalid achievement extra params type, expected string");
            }

            $arguments['extra_params'] = $params;
        }

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }
}

class AchievementException extends \Exception {

}