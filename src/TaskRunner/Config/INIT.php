<?php

namespace MateCat\TaskRunner\Config;

class INIT {


    const MATECAT_USER_AGENT = "MateCat/v";

    /**
     * @var $ENV
     *
     * General server environment settings to define the the usage of hard links rather than copy php method
     * must be one of these:
     *
     * - production
     * - development
     * - test
     *
     * @see EnvWrap
     *
     */
    public static $ENV ;

    public static $ROOT;
    public static $DEBUG                   = true;
    public static $PRINT_ERRORS            = false;
    public static $INSTANCE_ID             = 0;
    public static $REDIS_SERVERS           = array();
    public static $QUEUE_BROKER_ADDRESS;
    public static $QUEUE_JMX_ADDRESS;

    public static $QUEUE_NAME                   = "matecat_analysis_queue";

    public static $SMTP_HOST;
    public static $SMTP_PORT;
    public static $SMTP_SENDER;
    public static $SMTP_HOSTNAME;

    public static $SUPPORT_MAIL = 'support@matecat.com';
    public static $MAILER_FROM = 'cattool@matecat.com' ;
    public static $MAILER_FROM_NAME = 'MateCat';
    public static $MAILER_RETURN_PATH = 'no-reply@matecat.com';

    public static $LOG_REPOSITORY;
    public static $STORAGE_DIR;

    /**
     * Time zone string that should match the one set in the database.
     * @var string
     */
    public static $TIME_ZONE = 'Europe/Rome';

    /**
     * The MateCat Version
     */
    public static $BUILD_NUMBER;

    public function __construct(){}

    /**
     * Definitions for the asynchronous task runner
     * @var array
     */
    public static $TASK_RUNNER_CONFIG = null;

    /**
     * @var string
     */
    public static $PHP_EXECUTABLE = '/usr/bin/php';

    public static $SEND_ERR_MAIL_REPORT = true ;

    /**
     * Initialize the Class Instance
     */
    public static function obtain() {
        new self();
    }

}
