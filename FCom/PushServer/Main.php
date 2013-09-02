<?php

class FCom_PushServer_Main extends BCLass
{
    static public function bootstrap()
    {
        static::i()
            ->addService('/^./', 'FCom_PushServer_Service_Default::catchAll')
            ->addService('session', 'FCom_PushServer_Service_Default')
            ->addService('/^session:(.*)$/', 'FCom_PushServer_Service_Default')
        ;
    }

    static public function layoutInit()
    {
        $head = BLayout::i()->view('head');
        if ($head) {
            $head->js_raw('pushserver_init', array('content'=>"
FCom.pushserver_url = '".BApp::src('@FCom_PushServer/index.php')."';
            "));
        }
    }

    static public function onAdminUserLogout($args)
    {
        $userId = FCom_Admin_Model_User::i()->sessionUserId();
        FCom_PushServer_Model_Client::i()->delete_many(array('admin_user_id' => $userId));
        //TODO: implement roster (online/offline) notifications
    }

    protected $_services = array();

    public function addService($channel, $callback)
    {
        $this->_services[] = array(
            'channel' => $channel,
            'is_pattern' => $channel[0] === '/', //TODO: needs anything fancier?
            'callback' => $callback,
        );
        return $this;
    }

    public function getServices()
    {
        return $this->_services;
    }
}
