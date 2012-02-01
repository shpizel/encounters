/**
 * Queue
 *
 * @author shpizel
 */
$Queue = {

    /**
     * Storage
     *
     * @var object
     */
    __storage__: [],

    /**
     * Добавляет элемент в очередь
     *
     * @param mixed $queueElement
     * @return $Queue
     */
    put: function($queueElement) {
        this.__storage__.push($queueElement);
        return this;
    },

    /**
     * Возращает элемент из очереди
     *
     * @return mixed
     */
    get: function() {
        return this.__storage__.pop();
    },

    /**
     * Возвращает размер очереди
     *
     * @return int
     */
    qsize: function() {
        return this.__storage__.length;
    }
}