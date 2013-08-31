<?php

class FCom_AdminChat_PushServer extends FCom_PushServer_Service_Abstract
{
    static public function bootstrap()
    {
        FCom_PushServer_Main::i()->addService(__CLASS__);
    }

    public function init()
    {

    }

    static public function channel_chat($args)
    {

    }

    static public function message_start($msg)
    {
        // start the chat, receive initial history
    }

    static public function message_join($msg)
    {

    }

    static public function message_leave($msg)
    {

    }

    static public function message_text($msg)
    {

    }
}
