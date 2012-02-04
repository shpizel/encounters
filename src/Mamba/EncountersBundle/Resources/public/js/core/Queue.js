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
    $storage: [],

    /**
     * Добавляет элемент в очередь
     *
     * @param mixed $queueElement
     * @return $Queue
     */
    put: function($queueElement) {
        this.$storage.push($queueElement);
        return this;
    },

    /**
     * Возращает элемент из очереди
     *
     * @return mixed
     */
    get: function() {
        return this.$storage.pop();
    },

    /**
     * Возвращает размер очереди
     *
     * @return int
     */
    qsize: function() {
        return this.$storage.length;
    }
}