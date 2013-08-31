<?php

class FCom_PushServer_Model_Message extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_message';
    static protected $_origClass = __CLASS__;

    /**
     * - id
     * - seq
     * - channel_id
     * - subscriber_id
     * - sender_client_id
     * - recipient_client_id
     * - data_serialized
     * - status
     * - create_at
     * - update_at
     */

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('seq', microtime(true), null);
        $this->set('create_at', BDb::now(), null);
        $this->set('update_at', BDb::now());

        return true;
    }

    /**
     * Receive a message from service
     */
    public function receiveFromService($message)
    {

    }

    /**
     * Receive a message from client browser
     *
     */
    public function receiveFromClient($message, $client)
    {

    }

    /**
     * Send a message directly to client browser
     *
     */
    public function sendToClient($message, $client)
    {

    }
}

/*

From server to client:
All messages from server include seq, service, channel, ts

{ service:_pushserver, channel:_connection, action:handover }

From client to server:

{ message:received, seq:12345678 }

*/
