<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_AdminLiveFeed_Main extends BCLass
{
    public function onGetHeaderNotifications()
    {
        if ($this->BModuleRegistry->isLoaded('FCom_PushServer')
            && $this->BConfig->get('modules/FCom_AdminLiveFeed/livefeed_recent_activities')
        ) {
            $this->FCom_PushServer_Model_Client->sessionClient()->subscribe('activities_feed');
        }
    }
}
