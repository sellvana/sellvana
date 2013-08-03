<?php

class FCom_Comet_Model_Client extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_comet_client';
    static protected $_origClass = __CLASS__;
    /**
     * - id
     * - session_id
     * - create_at
     * - update_at
     * - data_serialized
     *   - admin_user_id
     *   - customer_id
     */

    /**
     * Get or create client record for current browser session
     */
    static public function sessionClient()
    {
        $sessId = BSession::i()->sessionId();
        $client = static::load($sessId, 'session_id');
        if (!$client) {
            $client = static::create(array('session_id' => $sessId))->save();
        }
        return $client;
    }

    /**
     * Get client by id or session_id
     */
    static public function getClient($client)
    {
        if (is_numeric($client)) {
            return static::load($client);
        } elseif (is_string($client)) {
            return static::load($client, 'session_id');
        } else {
            throw new BException('Invalid client id');
        }
    }

    /**
     * Check in the client and receive request from browser
     *
     * @param string $request
     */
    public function checkIn($request)
    {

    }

    /**
     * Check out the client
     */
    public function checkOut()
    {

    }

    /**
     * Get messages to be sent to browser
     */
    public function getPushQueue()
    {

    }

    /**
     * Subscribe the client to a channel
     */
    public function subscribe($channel)
    {

    }

    /**
     * Unsubscribe the client from the channel
     */
    public function unsubscribe($channel)
    {

    }

    /**
     * Receive a message to be sent to browser
     *
     */
    public function receive($message)
    {

    }

    /**
     * Publish a message to a channel
     */
    public function publish($channel, $message)
    {

    }
}
