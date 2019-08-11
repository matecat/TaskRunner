<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 30/04/15
 * Time: 19.21
 *
 */

namespace MateCat\Commons;

use MateCat\TaskRunner\Config\INIT;
use Exception;
use MateCat\TaskRunner\Commons\RedisKeys;
use MateCat\TaskRunner\Commons\Context;
use Redis;
use RedisException;
use Stomp;
use StompException;
use StompFrame;

class AMQHandler extends Stomp {

    /**
     * @var Redis
     */
    protected $redisHandler;

    protected $clientType = null;

    protected $queueTotalID = null;

    const CLIENT_TYPE_PUBLISHER  = 'Publisher';
    const CLIENT_TYPE_SUBSCRIBER = 'Subscriber';

    public $persistent = 'true';

    /**
     * Handle a string for the queue name
     * @throws StompException
     * @var string
     *
     */
    protected $queueName = null;

    public function __construct( $brokerUri = null ) {

        if ( !is_null( $brokerUri ) ) {
            parent::__construct( $brokerUri );
        } else {
            parent::__construct( INIT::$QUEUE_BROKER_ADDRESS );
        }

    }

    /**
     * Lazy connection
     *
     * Get the connection to Redis server and return it
     *
     * @throws RedisException
     */
    public function getRedisClient() {
        if ( empty( $this->redisHandler ) ) {
            $this->redisHandler = new RedisHandler();
        }

        return $this->redisHandler->getConnection();
    }

    /**
     * @param string $queueName
     *
     * @param null   $properties
     * @param null   $sync
     *
     * @return bool
     * @throws StompException
     * @throws Exception
     */
    public function subscribe( $queueName = null, $properties = null, $sync = null ) {

        if ( empty( $queueName ) ) {
            $queueName = RedisKeys::DEFAULT_QUEUE_NAME;
        }

        if ( !empty( $this->clientType ) && $this->clientType != self::CLIENT_TYPE_SUBSCRIBER ) {
            throw new Exception( "This client is a $this->clientType. A client can be only publisher or subscriber, not both." );
        } elseif ( !empty( $this->clientType ) && $this->clientType == self::CLIENT_TYPE_SUBSCRIBER ) {
            //already connected, we want to change the queue
            $this->queueName = $queueName;

            return parent::subscribe( '/queue/' . RedisKeys::DEFAULT_QUEUE_NAME );
        }

        $this->clientType = self::CLIENT_TYPE_SUBSCRIBER;
        $this->setReadTimeout( 0, 250000 );
        $this->queueName = $queueName;

        return parent::subscribe( '/queue/' . (int)INIT::$INSTANCE_ID . "_" . $queueName );

    }

    /**
     * @param string             $destination
     * @param StompFrame|string $msg
     * @param array              $properties
     * @param null               $sync
     *
     * @return bool
     * @throws Exception
     */
    public function send( $destination, $msg, $properties = [], $sync = null ) {

        if ( !empty( $this->clientType ) && $this->clientType != self::CLIENT_TYPE_PUBLISHER ) {
            throw new Exception( "This client is a $this->clientType. A client can be only publisher or subscriber, not both." );
        }

        $this->clientType = self::CLIENT_TYPE_PUBLISHER;

        return parent::send( '/queue/' . (int)INIT::$INSTANCE_ID . "_" . $destination, (string)$msg, $properties );

    }

    /**
     * Get the queue Length
     *
     * @param $queueName
     *
     * @return mixed
     * @throws Exception
     */
    public function getQueueLength( $queueName = null ) {

        if ( !empty( $queueName ) ) {
            $queue = $queueName;
        } elseif ( !empty( $this->queueName ) ) {
            $queue = $this->queueName;
        } else {
            throw new Exception( 'No queue name provided.' );
        }

        $queue_interface_url = INIT::$QUEUE_JMX_ADDRESS . "/api/jolokia/read/org.apache.activemq:type=Broker,brokerName=localhost,destinationType=Queue,destinationName=" . (int)INIT::$INSTANCE_ID . "_" . $queue . "/QueueSize";

        $mHandler = new MultiCurlHandler();

        $options = [
                CURLOPT_HEADER         => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => INIT::MATECAT_USER_AGENT . INIT::$BUILD_NUMBER,
                CURLOPT_CONNECTTIMEOUT => 5, // a timeout to call itself should not be too much higher :D
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER     => [ 'Authorization: Basic ' . base64_encode( "admin:admin" ) ]
        ];

        $resource = $mHandler->createResource( $queue_interface_url, $options );
        $mHandler->multiExec();
        $result = $mHandler->getSingleContent( $resource );
        $mHandler->multiCurlCloseAll();
        $result = json_decode( $result, true );

        Utils::raiseJsonExceptionError();

        return $result[ 'value' ];

    }

    /**
     * @param         $failed_segment
     * @param Context $queueInfo
     *
     * @throws Exception
     */
    public function reQueue( $failed_segment, Context $queueInfo ) {

        if ( !empty( $failed_segment ) ) {
            Log::doJsonLog( "Failed " . var_export( $failed_segment, true ) );
            $this->send( $queueInfo->queue_name, json_encode( $failed_segment ), [ 'persistent' => $this->persistent ] );
        }

    }

}