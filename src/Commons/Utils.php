<?php
namespace MateCat\Commons;

use DirectoryIterator;
use Exception;

class Utils {

    public static function randomString( $maxlength = 15 ) {

        $_pwd = base64_encode( md5( uniqid( '', true ) ) ); //we want more characters not only [0-9a-f]
        $pwd  = substr( $_pwd, 0, 6 ) . substr( $_pwd, -8, 6 ); //exclude last 2 char2 because they can be == sign

        if ( $maxlength > 15 ) {
            while ( strlen( $pwd ) < $maxlength ) {
                $pwd .= self::randomString();
            }
            $pwd = substr( $pwd, 0, $maxlength );
        }

        return $pwd;

    }


    public static function mysqlTimestamp( $time ) {
        return date( 'Y-m-d H:i:s', $time );
    }

    public static function underscoreToCamelCase( $string ) {
        return str_replace( ' ', '', ucwords( str_replace( '_', ' ', $string ) ) );
    }

    /**
     * @param $params
     * @param $required_keys
     *
     * @return mixed
     * @throws Exception
     */
    public static function ensure_keys( $params, $required_keys ) {
        $missing = [];

        foreach ( $required_keys as $key ) {
            if ( !array_key_exists( $key, $params ) ) {
                $missing[] = $key;
            }
        }

        if ( count( $missing ) > 0 ) {
            throw new Exception( "Missing keys: " . implode( ', ', $missing ) );
        }

        return $params;
    }

    public static function is_assoc( $array ) {
        return is_array( $array ) AND (bool)count( array_filter( array_keys( $array ), 'is_string' ) );
    }

    public static function getRealIpAddr() {

        foreach ( [
                          'HTTP_CLIENT_IP',
                          'HTTP_X_FORWARDED_FOR',
                          'HTTP_X_FORWARDED',
                          'HTTP_X_CLUSTER_CLIENT_IP',
                          'HTTP_FORWARDED_FOR',
                          'HTTP_FORWARDED',
                          'REMOTE_ADDR'
                  ] as $key ) {
            if ( isset( $_SERVER[ $key ] ) ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    if ( filter_var( trim( $ip ), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) !== false ) {
                        return $ip;
                    }
                }
            }
        }

    }

    protected static function _getBackTrace() {

        $trace = debug_backtrace();
        $now   = date( 'Y-m-d H:i:s' );

        $ip = Utils::getRealIpAddr();

        $stringDataInfo = "[$now (User IP: $ip)]";

        if ( isset( $trace[ 2 ][ 'class' ] ) ) {
            $stringDataInfo .= " " . $trace[ 2 ][ 'class' ] . "-> ";
        }
        if ( isset( $trace[ 2 ][ 'function' ] ) ) {
            $stringDataInfo .= $trace[ 2 ][ 'function' ] . " ";
        }
        $stringDataInfo .= "(line:" . $trace[ 1 ][ 'line' ] . ")";

        return $stringDataInfo;

    }

    public static function deleteDir( $dirPath ) {

        $iterator = new DirectoryIterator( $dirPath );

        foreach ( $iterator as $fileInfo ) {
            if ( $fileInfo->isDot() ) {
                continue;
            }
            if ( $fileInfo->isDir() ) {
                self::deleteDir( $fileInfo->getPathname() );
            } else {
                $fileName = $fileInfo->getFilename();
                if ( $fileName{0} == '.' ) {
                    continue;
                }
                $outcome = unlink( $fileInfo->getPathname() );
                if ( !$outcome ) {
                    Log::doJsonLog( "fail deleting " . $fileInfo->getPathname() );
                }
            }
        }
        rmdir( $iterator->getPath() );

    }

    /**
     * Call the output in JSON format
     *
     * @param bool $raise
     *
     * @return null|string
     * @throws Exception
     */
    public static function raiseJsonExceptionError( $raise = true ) {

        if ( function_exists( "json_last_error" ) ) {

            $error = json_last_error();

            switch ( $error ) {
                case JSON_ERROR_NONE:
                    $msg = null; # - No errors
                    break;
                case JSON_ERROR_DEPTH:
                    $msg = ' - Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $msg = ' - Underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $msg = ' - Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $msg = ' - Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $msg = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                default:
                    $msg = ' - Unknown error';
                    break;
            }

            if ( $raise && $error != JSON_ERROR_NONE ) {
                throw new Exception( $msg, $error );
            } elseif( $error != JSON_ERROR_NONE ){
                return $msg;
            }

        }

    }

}

