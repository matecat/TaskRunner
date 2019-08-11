<?php

namespace MateCat\Commons;

use MateCat\TaskRunner\Config\INIT;
use Redis;
use RedisException;

/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 09/11/15
 * Time: 18.51
 *
 */
class RedisHandler {

    /**
     * @var Redis
     */
    protected $redisHandler;

    /**
     * Lazy connection
     *
     * Get the connection to Redis server and return it
     *
     * @return Redis
     * @throws RedisException
     */
    public function getConnection() {

        if ( $this->redisHandler !== null && $this->redisHandler->isConnected() ) {
            try {
                $this->redisHandler->ping();
            } catch ( RedisException $e ) {
                $this->redisHandler = null;
            }
        }

        if ( $this->redisHandler === null ) {

            $connectionParams = INIT::$REDIS_SERVERS;

            if ( is_string( $connectionParams ) ) {

                $connectionParams = $this->formatDSN( $connectionParams );

            } elseif ( is_array( $connectionParams ) ) {

                $connectionParams = array_map( 'RedisHandler::formatDSN', $connectionParams );

            }

            $this->redisHandler = new Redis();
            $this->redisHandler->connect( $connectionParams[ 'scheme' ] . $connectionParams[ 'host' ], $connectionParams[ 'port' ] );
            $this->redisHandler->select( $connectionParams[ 'query' ][ 'database' ] );

        }

        return $this->redisHandler;

    }

    protected function formatDSN( $dsnString ) {

        $conf  = parse_url( $dsnString );
        $query = [];

        if( isset( $conf[ 'scheme' ] ) ){
            $conf[ 'scheme' ] .= "://";
        }

        if ( isset( $conf[ 'query' ] ) ) {
            parse_str( $conf[ 'query' ], $query );
        }

        if ( !isset( $query[ 'database' ] ) ) {
            $query[ 'database' ] = INIT::$INSTANCE_ID;
        }

        $conf[ 'query' ] = $query;

        return $conf;

    }

}