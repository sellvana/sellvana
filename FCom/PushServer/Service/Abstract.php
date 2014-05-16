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

    public function reply($message)
    {
        $message['ref_seq'] = !empty($this->_message['seq']) ? $this->_message['seq'] : null;
        $message['ref_signal'] = !empty($this->_message['signal']) ? $this->_message['signal'] : null;
        if (empty($message['channel'])) {
            $message['channel'] = $this->_message['channel'];
        }
        $this->_client->send($message);
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
        $this->reply(['signal' => 'error', 'description' => 'Unknown signal']);
    }
}
