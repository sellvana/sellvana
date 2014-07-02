<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_AdminLiveFeed_Main extends BCLass
{
    public function onGetHeaderNotifications()
    {
        if ($this->BApp->m('FCom_PushServer')->run_status === BModule::LOADED
            && $this->BConfig->get('modules/FCom_AdminLiveFeed/livefeed_recent_activity')
        ) {
            $this->FCom_PushServer_Model_Client->sessionClient()->subscribe('activities_feed');
        }
    }
}
