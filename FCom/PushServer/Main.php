<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_PushServer_Main extends BCLass
{
    protected $_services = [];

    protected static $_debug = false;

    public function bootstrap()
    {
        $this
            //->addService('/^./', 'FCom_PushServer_Main::catchAll')
            ->addService('client', 'FCom_PushServer_Service_Client')
        ;
        static::$_debug = true;
    }

    public function catchAll($message)
    {
        if (!empty($message['seq'])) {
            $this->FCom_PushServer_Model_Client->sessionClient()->send([
                'ref_seq' => $message['seq'],
                'signal' => 'received',
            ]);
        }
    }

    public function layoutInit()
    {
        $head = $this->BLayout->view('head');
        if ($head && $this->FCom_Admin_Model_User->isLoggedIn()) {
            $head->js_raw('pushserver_init', ['content' => "
FCom.pushserver_url = '" . $this->BApp->src('@FCom_PushServer/index.php') . "';
            "]);
        }
    }

    public function onAdminUserLogout($args)
    {
        $userId = $this->FCom_Admin_Model_User->sessionUserId();
        $this->FCom_PushServer_Model_Client->delete_many(['admin_user_id' => $userId]);
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

    public function isDebugMode()
    {
        return static::$_debug;
    }

    public function onGetHeaderNotifications()
    {
        if ($this->BApp->m('FCom_PushServer')->run_status === BModule::LOADED
            && $this->BConfig->get('modules/FCom_PushServer/recentactivity_realtime_notification')
        ) {
            $this->FCom_PushServer_Model_Client->sessionClient()->subscribe('activities_feed');
        }
    }
}
