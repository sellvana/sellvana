<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_AdminChat_PushServer extends FCom_PushServer_Service_Abstract
{
    static public function bootstrap()
    {
        FCom_PushServer_Main::i()
            ->addService('adminchat', 'FCom_AdminChat_PushServer_Chat')
            ->addService('/^adminchat:(.*)$/', 'FCom_AdminChat_PushServer_Chat')

            ->addService('adminuser', 'FCom_AdminChat_PushServer_User')
            ->addService('/^adminuser:(.*)$/', 'FCom_AdminChat_PushServer_User')
        ;
    }
}
