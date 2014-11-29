<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_PushServer_Service_Client extends FCom_PushServer_Service_Abstract
{
    /**
     * set signal status
     */
    public function signal_status()
    {
        if (empty($this->_message['status'])) {
            $this->reply(['signal' => 'error', 'description' => 'Empty status']);
        }
        $status = $this->_message['status'];
        $this->_client->setStatus($status)->save();
    }

    public function signal_ready()
    {
        //TODO: broadcast client connection?
    }

    /**
     * subscribe signal
     */
    public function signal_subscribe()
    {
        $this->_client->subscribe($this->_message['to']);
    }
}
