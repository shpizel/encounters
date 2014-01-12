<?php
namespace Core\GearmanBundle;

/**
 * Class GearmanClient
 *
 * @package Core\GearmanBundle
 */
class GearmanClient extends \GearmanClient {

    /**
     * Runs a task in the background, returning a job handle which can be used to get
     * the status of the running task.
     *
     * @link http://php.net/manual/en/gearmanclient.dobackground.php
     * @param string $function_name
     * @param string $workload
     * @param string $unique
     * @return string The job handle for the submitted task
     */
    public function doBackground($function_name, $workload, $unique = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if (Gearman::getInstance()->metricsEnabled) {
            Gearman::getInstance()->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            Gearman::getInstance()->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * Runs a high priority task in the background, returning a job handle which can be
     * used to get the status of the running task. High priority tasks take precedence
     * over normal and low priority tasks in the job queue.
     *
     * @link http://php.net/manual/en/gearmanclient.dohighbackground.php
     * @param string $function_name
     * @param string $workload
     * @param string $unique
     * @return string The job handle for the submitted task
     */
    public function doHighBackground($function_name, $workload, $unique = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if (Gearman::getInstance()->metricsEnabled) {
            Gearman::getInstance()->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            Gearman::getInstance()->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * Runs a low priority task in the background, returning a job handle which can be
     * used to get the status of the running task. Normal and high priority tasks take
     * precedence over low priority tasks in the job queue.
     *
     * @link http://php.net/manual/en/gearmanclient.dolowbackground.php
     * @param string $function_name
     * @param string $workload
     * @param string $unique
     * @return string The job handle for the submitted task
     */
    public function doLowBackground($function_name, $workload, $unique = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if (Gearman::getInstance()->metricsEnabled) {
            Gearman::getInstance()->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            Gearman::getInstance()->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * Adds a task to be run in parallel with other tasks. Call this method for all the
     * tasks to be run in parallel, then call GearmanClient::runTasks to perform the
     * work. Note that enough workers need to be available for the tasks to all run in
     * parallel.
     *
     * @link http://php.net/manual/en/gearmanclient.addtask.php
     * @param string $function_name
     * @param string $workload
     * @param mixed $context
     * @param string $unique
     * @return GearmanTask A GearmanTask object or false if the task could not be added
     */
    public function addTask($function_name, $workload, $context = null, $unique = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if (Gearman::getInstance()->metricsEnabled) {
            Gearman::getInstance()->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            Gearman::getInstance()->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * Adds a high priority task to be run in parallel with other tasks. Call this
     * method for all the high priority tasks to be run in parallel, then call
     * GearmanClient::runTasks to perform the work. Tasks with a high priority will be
     * selected from the queue before those of normal or low priority.
     *
     * @link http://php.net/manual/en/gearmanclient.addtaskhigh.php
     * @param string $function_name
     * @param string $workload
     * @param mixed $context
     * @param string $unique
     * @return GearmanTask A GearmanTask object or false if the task could not be added
     */
    public function addTaskHigh($function_name, $workload, $context = null, $unique = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if (Gearman::getInstance()->metricsEnabled) {
            Gearman::getInstance()->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            Gearman::getInstance()->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * Adds a low priority background task to be run in parallel with other tasks. Call
     * this method for all the tasks to be run in parallel, then call
     * GearmanClient::runTasks to perform the work. Tasks with a low priority will be
     * selected from the queue after those of normal or low priority.
     *
     * @link http://php.net/manual/en/gearmanclient.addtasklow.php
     * @param string $function_name
     * @param string $workload
     * @param mixed $context
     * @param string $unique
     * @return GearmanTask A GearmanTask object or false if the task could not be added
     */
    public function addTaskLow($function_name, $workload, $context = null, $unique = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if (Gearman::getInstance()->metricsEnabled) {
            Gearman::getInstance()->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            Gearman::getInstance()->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * Adds a background task to be run in parallel with other tasks. Call this method
     * for all the tasks to be run in parallel, then call GearmanClient::runTasks to
     * perform the work.
     *
     * @link http://php.net/manual/en/gearmanclient.addtaskbackground.php
     * @param string $function_name
     * @param string $workload
     * @param mixed $context
     * @param string $unique
     * @return GearmanTask A GearmanTask object or false if the task could not be added
     */
    public function addTaskBackground($function_name, $workload, $context = null, $unique = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if (Gearman::getInstance()->metricsEnabled) {
            Gearman::getInstance()->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            Gearman::getInstance()->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * Adds a high priority background task to be run in parallel with other tasks.
     * Call this method for all the tasks to be run in parallel, then call
     * GearmanClient::runTasks to perform the work. Tasks with a high priority will be
     * selected from the queue before those of normal or low priority.
     *
     * @link http://php.net/manual/en/gearmanclient.addtaskhighbackground.php
     * @param string $function_name
     * @param string $workload
     * @param mixed $context
     * @param string $unique
     * @return GearmanTask A GearmanTask object or false if the task could not be added
     */
    public function addTaskHighBackground($function_name, $workload, $context = null, $unique = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if (Gearman::getInstance()->metricsEnabled) {
            Gearman::getInstance()->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            Gearman::getInstance()->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * Adds a low priority background task to be run in parallel with other tasks. Call
     * this method for all the tasks to be run in parallel, then call
     * GearmanClient::runTasks to perform the work. Tasks with a low priority will be
     * selected from the queue after those of normal or high priority.
     *
     * @link http://php.net/manual/en/gearmanclient.addtasklowbackground.php
     * @param string $function_name
     * @param string $workload
     * @param mixed $context
     * @param string $unique
     * @return GearmanTask A GearmanTask object or false if the task could not be added
     */
    public function addTaskLowBackground($function_name, $workload, $context = null, $unique = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if (Gearman::getInstance()->metricsEnabled) {
            Gearman::getInstance()->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            Gearman::getInstance()->metrics['timeout']+=$timeout;
        }

        return $ret;
    }
}