<?php

class FCom_PushServer_Main extends BCLass
{
    protected $_services = [];

    protected static $_debug = false;

    static public function bootstrap()
    {
        static::i()
            //->addService('/^./', 'FCom_PushServer_Main::catchAll')
            ->addService('client', 'FCom_PushServer_Service_Client')
        ;
        static::$_debug = true;
    }

    static public function catchAll($message)
    {
        if (!empty($message['seq'])) {
            FCom_PushServer_Model_Client::i()->sessionClient()->send([
                'ref_seq' => $message['seq'],
                'signal' => 'received',
            ]);
        }
    }

    static public function layoutInit()
    {
        $head = BLayout::i()->view('head');
        if ($head) {
            $head->js_raw('pushserver_init', ['content' => "
FCom.pushserver_url = '" . BApp::src('@FCom_PushServer/index.php') . "';
            "]);
        }
    }

    static public function onAdminUserLogout($args)
    {
        $userId = FCom_Admin_Model_User::i()->sessionUserId();
        FCom_PushServer_Model_Client::i()->delete_many(['admin_user_id' => $userId]);
        //TODO: implement roster (online/offline) notifications
    }

    public function addService($channel, $callback)
    {
        $this->_services[] = [
            'channel' => $channel,
            'is_pattern' => $channel[0] === '/', //TODO: needs anything fancier?
            'callback' => $callback,
        ];
        return $this;
    }

    public function getServices()
    {
        return $this->_services;
    }

    static public function isDebugMode()
    {
        return static::$_debug;
    }
}
