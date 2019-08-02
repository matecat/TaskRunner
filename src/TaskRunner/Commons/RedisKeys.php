<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 04/05/15
 * Time: 13.37
 *
 */

namespace MateCat\TaskRunner\Commons;

/**
 * Class RedisKeys
 * @package TaskRunner\Commons
 */
class RedisKeys {

    /**
     * Key Set that holds the main process of Task Manager
     *
     * Every Task Manager must have it's pid registered to distribute the across multiple servers
     *
     */
    const TASK_RUNNER_PID = 'tm_pid_set';

    /**
     * FallBack for bugs on key name to not loose messages
     */
    const DEFAULT_QUEUE_NAME = " unknown_queue";

}