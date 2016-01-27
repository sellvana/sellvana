<?php

/**
 * Class Sellvana_CustomerAssist_PushServer
 *
 * @property FCom_PushServer_Main $FCom_PushServer_Main
 */
class Sellvana_CustomerAssist_PushServer extends FCom_PushServer_Service_Abstract
{
    public function bootstrap()
    {
        $this->FCom_PushServer_Main
            ->addService('customer', 'Sellvana_CustomerAssist_PushServer_Customer')
            ->addService('/^customer:(.*)$/', 'Sellvana_CustomerAssist_PushServer_Customer')
        ;
    }
}
