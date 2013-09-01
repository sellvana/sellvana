<?php

class FCom_PushServer_Service_Abstract extends BClass implements FCom_PushServer_Service_Interface
{
    protected $_message;
    protected $_client;

    public function setMessage($message, $client = null)
    {
        $this->_message = $message;
        $this->_client = $client;
        return $this;
    }

    public function onBeforeDispatch()
    {
        return true;
    }

    public function onAfterDispatch()
    {

    }

    public function onUnknownSignal()
    {
        $this->_client->send(array(
            'signal' => 'error',
            'ref_seq' => !empty($this->_message['seq']) ? $this->_message['seq'] : null,
            'ref_signal' => !empty($this->_message['signal']) ? $this->_message['signal'] : null,
        ));
    }
}
