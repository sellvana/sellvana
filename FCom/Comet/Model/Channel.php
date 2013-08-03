<?php

class FCom_Comet_Model_Channel extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_comet_channel';
    static protected $_origClass = __CLASS__;

    /**
     * - id
     * - channel_name
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

    public function getChannel($channel, $create=false)
    {

    }

    public function subscribe($client)
    {

    }

    public function unsubscribe($client)
    {

    }

    public function publish($message)
    {

    }
}
