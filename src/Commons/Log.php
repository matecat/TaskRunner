<?php

namespace MateCat\Commons;

use MateCat\TaskRunner\Config\INIT;
use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Log {

    /**
     * @var Logger
     */
    protected static $logger;

    /**
     * @var bool
     */
    public static $useMonolog = true;

    public static $fileName;

    public static $uniqID;

    public static $requestID;

    protected static function _writeTo( $stringData ) {
        try {
            self::getLogger()->debug( $stringData );
        } catch ( Exception $e ) {
            file_put_contents( self::getFileNamePath(), $stringData, FILE_APPEND );
        }
    }

    /**
     * @throws Exception
     */
    protected static function _configure() {
        if ( self::$logger === null ) {
            if ( !self::$useMonolog ) {
                throw new Exception( 'Logger is not set. Is monolog available?' );
            }
            $fileHandler   = new StreamHandler( self::getFileNamePath() );
            $fileFormatter = new LineFormatter( "%message%\n", "", true, true );
            $fileHandler->setFormatter( $fileFormatter );
            self::$logger = new Logger( 'MateCat-Analysis', [ $fileHandler ] );
        }
    }

    protected static function getFileNamePath() {
        if ( !empty( self::$fileName ) ) {
            $name = INIT::$LOG_REPOSITORY . "/" . self::$fileName;
        } else {
            $name = INIT::$LOG_REPOSITORY . "/default.log";
        }

        return $name;
    }

    public static function getContext() {

        $trace = debug_backtrace( 2 );
        $_ip   = Utils::getRealIpAddr();

        $context         = [];
        $context[ 'ip' ] = !empty( $_ip ) ? $_ip : gethostbyname( gethostname() );

        if ( isset( $trace[ 3 ][ 'class' ] ) ) {
            $context[ 'class' ] = $trace[ 3 ][ 'class' ];
        }

        if ( isset( $trace[ 3 ][ 'function' ] ) ) {
            $context[ 'function' ] = $trace[ 3 ][ 'function' ];
        }

        $context[ 'line' ] = isset( $trace[ 2 ][ 'line' ] ) ? $trace[ 2 ][ 'line' ] : null;

        return $context;

    }

    public static function doJsonLog( $content, $filename = null ) {

        $old_name = Log::$fileName;

        if ( $filename !== null ) {
            Log::$fileName = $filename;
        }

        $_logObject = [
                "log" => [
                        "token_hash" => self::$uniqID,
                        "context"    => self::getContext(),
                        "time"       => time(),
                        "date"       => date( DATE_W3C ),
                        "content"    => $content
                ]
        ];

        self::_writeTo( json_encode( $_logObject ) );

        Log::$fileName = $old_name;

    }

    /**
     * @return Logger
     * @throws Exception
     */
    public static function getLogger() {
        self::_configure();

        return self::$logger;
    }

    /**
     * Based on http://aidanlister.com/2004/04/viewing-binary-data-as-a-hexdump-in-php/
     *
     * @param      $data
     * @param bool $htmlOutput
     * @param bool $uppercase
     * @param bool $return
     *
     * @return string
     * @author      Peter Waller <iridum@php.net>
     *
     * View any string as a hexDump.
     *
     * This is most commonly used to view binary data from streams
     * or sockets while debugging, but can be used to view any string
     * with non-viewable characters.
     *
     * @author      Aidan Lister <aidan@php.net>
     */
    public static function hexDump( $data, $htmlOutput = false, $uppercase = true, $return = false ) {

        if ( is_array( $data ) ) {
            $data = print_r( $data, true );
        }

        $hex    = '';
        $ascii  = '';
        $dump   = ( $htmlOutput === true ) ? '<pre>' : '';
        $offset = 0;
        $len    = strlen( $data );

        $x = ( $uppercase === false ) ? 'x' : 'X';

        for ( $i = $j = 0; $i < $len; $i++ ) {

            $hex .= sprintf( "%02$x ", ord( $data[ $i ] ) );

            // Replace non-viewable bytes with '.'
            if ( ord( $data[ $i ] ) >= 32 ) {
                $ascii .= ( $htmlOutput === true ) ?
                        htmlentities( $data[ $i ] ) :
                        $data[ $i ];
            } else {
                $ascii .= '.';
            }

            if ( $j === 7 ) {
                $hex   .= ' ';
                $ascii .= ' ';
            }


            if ( ++$j === 16 || $i === $len - 1 ) {
                $dump .= sprintf( "%04$x  %-49s  %s", $offset, $hex, $ascii );

                // Reset vars
                $hex    = $ascii = '';
                $offset += 16;
                $j      = 0;

                // Add newline            
                if ( $i !== $len - 1 ) {
                    $dump .= "\n";
                }

            }

        }

        $dump .= $htmlOutput === true ? '</pre>' : '';
        $dump .= "\n";

        // Output method
        if ( $return === false ) {
            self::_writeTo( $dump );
        } else {
            return $dump;
        }

        return null;

    }

    public static function getRequestID() {
        if ( self::$requestID == null ) {
            self::$requestID = uniqid();
        }

        return self::$requestID;
    }

}
