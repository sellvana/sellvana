<?php

class FCom_AdminChat_PushServer extends FCom_PushServer_Service_Abstract
{
    static public function bootstrap()
    {
        FCom_PushServer_Main::i()->addService('adminchat', __CLASS__);
    }

    public function signal_status()
    {

    }

    public function signal_start()
    {
        // start the chat, receive initial history

        //$this->_client->send($this->_message);
    }

    public function signal_join()
    {

    }

    public function signal_leave()
    {

    }

    public function signal_text()
    {

    }
}
