<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PushServer_Main
 *
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class FCom_PushServer_Main extends BCLass
{
    /**
     * @var array
     */
    protected $_services = [];

    /**
     * @var bool
     */
    protected static $_debug = false;

    public function bootstrap()
    {
        $this
            //->addService('/^./', 'FCom_PushServer_Main::catchAll')
            ->addService('client', 'FCom_PushServer_Service_Client')
        ;
        static::$_debug = true;
    }

    /**
     * @param $message
     */
    public function catchAll($message)
    {
        if (!empty($message['seq'])) {
            $this->FCom_PushServer_Model_Client->sessionClient()->send([
                'ref_seq' => $message['seq'],
                'signal' => 'received',
            ]);
        }
    }

    /**
     *
     */
    public function layoutInit()
    {
        /** @var FCom_Core_View_Head $head */
        $head = $this->BLayout->view('head');
        /** @var FCom_Core_View_Text $script */
        $script = $this->BLayout->view('head_script');

        if ($this->FCom_Admin_Model_User->isLoggedIn()) {
            $text = "
FCom.pushserver_url = '" . $this->BApp->src('@FCom_PushServer/index.php') . "';
";
            $head->js_raw('pushserver_init', $text);
            $script->addText('FCom_PushServer:init', $text);
        }
    }

    /**
     * @param $args
     */
    public function onAdminUserLogout($args)
    {
        $userId = $this->FCom_Admin_Model_User->sessionUserId();
        $this->FCom_PushServer_Model_Client->delete_many(['admin_user_id' => $userId]);
        //TODO: implement roster (online/offline) notifications
    }

    /**
     * @param $channel
     * @param $callback
     * @return $this
     */
    public function addService($channel, $callback)
    {
        $this->_services[] = [
            'channel' => $channel,
            'is_pattern' => $channel[0] === '/', //TODO: needs anything fancier?
            'callback' => $callback,
        ];
        return $this;
    }

    /**
     * @return array
     */
    public function getServices()
    {
        return $this->_services;
    }

    /**
     * @return bool
     */
    public function isDebugMode()
    {
        return static::$_debug;
    }

}
