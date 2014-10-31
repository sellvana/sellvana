<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_AdminChat_PushServer
 *
 * @property FCom_PushServer_Main $FCom_PushServer_Main
 */
class FCom_AdminChat_PushServer extends FCom_PushServer_Service_Abstract
{
    public function bootstrap()
    {
        $this->FCom_PushServer_Main
            ->addService('adminchat', 'FCom_AdminChat_PushServer_Chat')
            ->addService('/^adminchat:(.*)$/', 'FCom_AdminChat_PushServer_Chat')

            ->addService('adminuser', 'FCom_AdminChat_PushServer_User')
            ->addService('/^adminuser:(.*)$/', 'FCom_AdminChat_PushServer_User')
        ;
    }
}
