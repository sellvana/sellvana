<?php

class FCom_PushServer_Service_Client extends FCom_PushServer_Service_Abstract
{
    public function signal_status()
    {
        if ( empty( $this->_message[ 'status' ] ) ) {
            $this->reply( array( 'signal' => 'error', 'description' => 'Empty status' ) );
        }
        $status = $this->_message[ 'status' ];
        $this->_client->setStatus( $status )->save();
    }

    public function signal_ready()
    {
        //TODO: broadcast client connection?
    }

    public function signal_subscribe()
    {
        $this->_client->subscribe( $this->_message[ 'to' ] );
    }
}
