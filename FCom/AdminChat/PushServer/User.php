<?php

class FCom_AdminChat_PushServer_User extends FCom_PushServer_Service_Abstract
{
    public function onBeforeDispatch()
    {
        if ( !parent::onBeforeDispatch() ) return false;

        if ( !FCom_Admin_Model_User::i()->isLoggedIn() ) {
            $this->reply( array( 'channel' => 'client', 'signal' => 'logout' ) );
            return false;
        }

        return true;
    }

    public function signal_subscribe()
    {
        $this->_client->subscribe( $this->_message[ 'channel' ] );
    }

    public function signal_status()
    {
        $userId = FCom_Admin_Model_User::i()->sessionUserId();
        FCom_AdminChat_Model_UserStatus::i()->changeStatus( $this->_message[ 'status' ], $userId );
    }
}
