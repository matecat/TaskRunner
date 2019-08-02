<?php
namespace MateCat\Config;

use Exception;
use MateCat\Commons\Log;

/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 13/05/15
 * Time: 10.54
 *
 */
class Bootstrap {

    public static $_INI_VERSION;
    protected static $CONFIG;
    protected static $_ROOT;

    public static function start() {
        new self();
    }

    private function __construct() {

        self::$_ROOT        = realpath( dirname( __FILE__ ) . '/../../' );
        self::$CONFIG       = parse_ini_file( self::$_ROOT . DIRECTORY_SEPARATOR . 'inc/config.ini', true );
        self::$_INI_VERSION = parse_ini_file( self::$_ROOT . DIRECTORY_SEPARATOR . 'inc/version.ini' )['version'];

        register_shutdown_function( [ 'MateCat\Config\Bootstrap', 'shutdownFunctionHandler' ] );
        set_exception_handler( [ 'MateCat\Config\Bootstrap', 'exceptionHandler' ] );

        // Overridable defaults
        INIT::$ROOT                           = self::$_ROOT; // Accessible by Apache/PHP

        //get the environment configuration
        self::initConfig();

        ini_set( 'display_errors', false );
        if ( INIT::$PRINT_ERRORS ) {
            ini_set( 'display_errors', true );
        }

        if ( empty( INIT::$STORAGE_DIR ) ) {
            INIT::$STORAGE_DIR = INIT::$ROOT . "/local_storage" ;
        }

        date_default_timezone_set( INIT::$TIME_ZONE );

        INIT::$LOG_REPOSITORY                  = INIT::$STORAGE_DIR . "/logs";
        INIT::$TASK_RUNNER_CONFIG              = parse_ini_file( self::$_ROOT . DIRECTORY_SEPARATOR . 'inc/task_manager_config.ini', true );

        if ( !is_dir( INIT::$LOG_REPOSITORY ) ) {
            mkdir( INIT::$LOG_REPOSITORY, 0755, true );
        }

    }

    public static function exceptionHandler( $exception ) {

        Log::$fileName = 'fatal_errors.txt';

        try {
            /**
             * @var $exception Exception
             */
            throw $exception;
        } catch ( Exception $e ) {
            Log::doJsonLog( [ "error" => $exception->getMessage(), "trace" => $exception->getTrace() ] );
        }

        if ( INIT::$PRINT_ERRORS ) {
            echo $exception->getMessage() . "\n";
            echo $exception->getTraceAsString() . "\n";
        }

    }

    public static function shutdownFunctionHandler() {

        $errorType = array(
                E_CORE_ERROR        => 'E_CORE_ERROR',
                E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
                E_ERROR             => 'E_ERROR',
                E_USER_ERROR        => 'E_USER_ERROR',
                E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
                E_DEPRECATED        => 'DEPRECATION_NOTICE', //From PHP 5.3
        );

        # Getting last error
        $error = error_get_last();

        # Checking if last error is a fatal error
        switch ( $error[ 'type' ] ) {
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:

                if ( !ob_get_level() ) {
                    ob_start();
                } else {
                    ob_end_clean();
                    ob_start();
                }

                debug_print_backtrace();
                $output = ob_get_contents();
                ob_end_clean();

                # Here we handle the error, displaying HTML, logging, ...
                $output .= "[ {$errorType[$error['type']]} ]\n";
                $output .= "{$error['message']}\n";
                $output .= "Not Recoverable Error on line {$error['line']} in file " . $error[ 'file' ];
                $output .= " - PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
                $output .= " - REQUEST URI: " . var_export( @$_SERVER[ 'REQUEST_URI' ], true ) . "\n";
                $output .= " - REQUEST Message: " . var_export( $_REQUEST, true ) . "\n";
                $output .= "\n";
                $output .= "Aborting...\n";

                Log::$fileName = 'fatal_errors.txt';
                Log::doJsonLog( $output );

                if ( INIT::$PRINT_ERRORS ) {
                    echo $output;
                }

                break;
        }

    }

    protected static function generate_password( $length = 12 ) {

        $_pwd = md5( uniqid( '', true ) );
        $pwd  = substr( $_pwd, 0, 6 ) . substr( $_pwd, -6, 6 );

        if ( $length > 12 ) {
            while ( strlen( $pwd ) < $length ) {
                $pwd .= self::generate_password();
            }
            $pwd = substr( $pwd, 0, $length );
        }

        return $pwd;

    }

    /**
     * Returns an array of configuration params as parsed from config.ini file.
     * The returned array only return entries that match the current environment.
     *
     */
    public static function getEnvConfig() {

        if ( getenv( 'ENV' ) !== false ) {
            self::$CONFIG['ENV'] = getenv( 'ENV' );
        }

        return self::$CONFIG[ self::$CONFIG['ENV'] ];
    }

    /**
     * Returns a specific key from parsed configuration file
     *
     * @param $key
     * @return mixed
     */
    public static function getEnvConfigKey( $key ) {
        $config = self::getEnvConfig() ;
        return @$config[ $key ] ;
    }

    /**
     *
     * This function initializes the configuration performing all required checks to be sure
     * that configuration is safe.
     *
     * If any sanity check is to be done, this is the right place to do it.
     */
    protected static function initConfig() {

        INIT::$ENV = self::$CONFIG['ENV'];
        INIT::$BUILD_NUMBER = self::$_INI_VERSION;

        $env = self::getEnvConfig();

        foreach( $env as $KEY => $value ){
            if ( property_exists( 'MateCat\Config\INIT', $KEY ) ) {
                INIT::${$KEY} = $value;
            }
        }

        INIT::obtain(); //load configurations

    }

}

return true;
