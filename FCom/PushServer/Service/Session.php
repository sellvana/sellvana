<?php

class FCom_PushServer_Service_Session extends FCom_PushServer_Service_Abstract
{
    static public function catchAll($message)
    {
        if (!empty($message['seq'])) {
            FCom_PushServer_Model_Client::i()->sessionClient()->send(array(
                'ref_seq' => $message['seq'],
                'signal' => 'received',
            ));
        }
    }

    public function signal_load()
    {
        $this->_client->set('status', 'load');
    }
}
