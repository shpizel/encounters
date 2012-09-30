<?php
namespace Core\MambaBundle\API;

/**
 * Notify
 *
 * @package MambaBundle
 */
class Notify {

    /**
     * Отослать извещение в мессенджер от имени пользователя «Менеджер приложений»
     *
     * @param array|int|numeric string with "," $ids (MAX 100 oid анкет)
     * @param string $message
     * @param string $params
     * @throws NotifyException, MambaException
     * @return array
     */
    public function sendMessage($ids, $message, $params = null) {
        if (!is_array($ids)) {
            if (is_string($ids)) {
                $ids = str_replace(" ", "", $ids);
                $ids = explode(",", $ids);
            } elseif (is_int($ids)) {
                $ids = array($ids);
            } else {
                throw new NotifyException("Invalid ids param");
            }

            $ids = array_filter($ids, function($id) {
                return (bool) $id;
            });

            return $this->sendMessage($ids, $message, $params);
        }

        $max = 100;
        if (count($ids) > $max) {
            $ids = array_slice($ids, 0, $max);
        }

        foreach ($ids as &$id) {
            if (!is_int($id)) {
                if (is_numeric($id)) {
                    $id = (int) $id;
                } else {
                    throw new NotifyException("Invalid id type: ". gettype($id));
                }
            }
        }

        if (!is_string($message)) {
            throw new NotifyException("Invalid message type: " . gettype($message));
        }

        $arguments = array(
            'oids' => implode(',', $ids),
            'message' => $message,
        );

        if ($params) {
            if (!is_string($params)) {
                throw new NotifyException("Invalid params type: " . gettype($params));
            }

            $arguments['extra_params'] = $params;
        }

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
    }
}

/**
 * NotifyException
 *
 * @package MambaBundle
 */
class NotifyException extends \Exception {

}