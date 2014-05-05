<?php

class FCom_PushServer_Model_Channel extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_channel';
    static protected $_origClass = __CLASS__;

    static protected $_channelCache = [];

    /**
     * - id
     * - channel_name
     * - channel_out
     * - create_at
     * - update_at
     * - data_serialized
     *   - permissions
     *     - can_subscribe
     *       - everyone
     *       - admin_user
     *       - customer
     *       - none
     *     - can_publish
     *       - everyone
     *       - admin_user
     *       - customer
     *       - none
     *   - subscribers
     *   - message_queue
     */

    public function getChannel( $channel, $create = false )
    {
        if ( is_object( $channel ) && ( $channel instanceof FCom_PushServer_Model_Channel ) ) {
            return $channel;
        }
        if ( !empty( static::$_channelCache[ $channel ] ) ) {
            return static::$_channelCache[ $channel ];
        }
        if ( is_string( $channel ) ) {
            $channelName = $channel;
            $channel = static::load( $channel, 'channel_name' );
            if ( !$channel ) {
                $channel = static::create( [ 'channel_name' => $channelName ] )->save();
            }
            static::$_channelCache[ $channelName ] = $channel;
        }
        return $channel;
    }

    public function onBeforeSave()
    {
        if ( !parent::onBeforeSave() ) return false;

        $this->set( 'create_at', BDb::now(), 'IFNULL' );
        $this->set( 'update_at', BDb::now() );

        return true;
    }

    public function onBeforeDelete()
    {
        if ( !parent::onBeforeDelete() ) return false;

        $this->send( [ 'signal' => 'delete' ] );

        return true;
    }

    public function listen( $callback )
    {
        $channelName = $this->channel_name;
        BEvents::i()->on( 'FCom_PushServer_Model_Channel::send:' . $channelName, $callback );
        return $this;
    }

    public function subscribe( $client )
    {
        FCom_PushServer_Model_Client::i()->getClient( $client )->subscribe( $this );
        return $this;
    }

    public function unsubscribe( $client )
    {
        FCom_PushServer_Model_Client::i()->getClient( $client )->unsubscribe( $this );
        return $this;
    }

    public function send( $message, $fromClient = null )
    {
        if ( empty( $message[ 'channel' ] ) ) {
            $message[ 'channel' ] = $this->channel_name;
        }


if ( FCom_PushServer_Main::isDebugMode() ) {
    BDebug::log( "SEND1: " . print_r( $message, 1 ) );
}
        BEvents::i()->fire( __METHOD__ . ':' . $this->get( 'channel_name' ), [
            'channel' => $this,
            'message' => $message,
            'client'  => $fromClient,
        ] );

        $clientHlp = FCom_PushServer_Model_Client::i();
        $fromWindowName = $clientHlp->getWindowName();
        $fromConnId = $clientHlp->getConnId();
        $msgHlp = FCom_PushServer_Model_Message::i();
        $msgIds = [];

        $toClients = FCom_PushServer_Model_Client::i()->orm( 'c' )
            ->join( 'FCom_PushServer_Model_Subscriber', [ 'c.id', '=', 's.client_id' ], 's' )
            ->where( 's.channel_id', $this->id() )
            ->select( 's.id', 'sub_id' )->select( 'c.id' )->select( 'c.data_serialized' )
            ->find_many();

if ( FCom_PushServer_Main::isDebugMode() ) {
    BDebug::log( 'SEND2: ' . sizeof( $toClients ) . ': ' . print_r( $this->as_array(), 1 ) );
}

        foreach ( $toClients as $toClient ) {
            if ( $fromClient && $fromClient->id() === $toClient->id() ) {
                //continue;
            }
            $windows = (array)$toClient->getData( 'windows' );
            foreach ( $windows as $toWindowName => $toWindowData ) {
                $toConnId = !empty( $toWindowData[ 'connections' ] ) ? key( $toWindowData[ 'connections' ] ) : null;
                $msg = $msgHlp->create( [
                    'seq' => !empty( $message[ 'seq' ] ) ? $message[ 'seq' ] : null,
                    'channel_id' => $this->id(),
                    'subscriber_id' => $toClient->get( 'sub_id' ),
                    'client_id' => $toClient->id(),
                    'window_name' => $toWindowName,
                    'conn_id' => $toConnId,
                    'status' => 'published',
                ] )->setData( $message )->save();
                //$msgIds[] = $msg->id;

if ( FCom_PushServer_Main::isDebugMode() ) {
    BDebug::log( "SEND3: " . print_r( $msg->as_array(), 1 ) );
}
            }
        }
        if ( $msgIds ) {
            $msgHlp->update_many( [ 'status' => 'published' ], [ 'id' => $msgIds ] );
        }
        return $this;
    }
}
