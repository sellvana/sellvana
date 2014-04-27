<?php

/*
- id
- user_id
- status
- message
- create_at // when user joined the session
- update_at // the last time user got updated on chat
*/

class FCom_AdminChat_Model_UserStatus extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_adminchat_userstatus';
    static protected $_origClass = __CLASS__;

    static protected $_sessionUserStatus;

    public function sessionUserStatus( $createIfNotExists = false, $defaultStatus = 'offline' )
    {
        if ( !static::$_sessionUserStatus ) {
            $userId = FCom_Admin_Model_User::i()->sessionUserId();
            if ( !$userId ) {
                return false;
            }

            static::$_sessionUserStatus = static::load( $userId, 'user_id' );

            if ( $createIfNotExists && !static::$_sessionUserStatus ) {
                static::$_sessionUserStatus = static::create( array(
                    'user_id' => $userId,
                    'status' => $defaultStatus,
                ) );
            }
        }
        return static::$_sessionUserStatus;
    }

    public function changeStatus( $status, $userId = null )
    {
        $userStatus = $this->sessionUserStatus( true );
        if ( $userStatus->get( 'status' ) != $status ) {
            $userStatus->set( 'status', $status )->save();

            $userHlp = FCom_Admin_Model_User::i();
            if ( is_null( $userId ) || $userHlp->sessionUserId() === $userId ) {
                $user = $userHlp->sessionUser();
            } else {
                $user = $userHlp->load( $userId );
            }
            $channel = FCom_PushServer_Model_Channel::i()->getChannel( 'adminuser', true );
            $channel->send( array(
                'signal' => 'status',
                'users' => array(
                    array( 'username' => $user->get( 'username' ), 'status' => $status ),
                ),
            ) );
        }
        return $this;
    }

    public function onBeforeSave()
    {
        if ( !parent::onBeforeSave() ) return false;

        $this->set( 'create_at', BDb::now(), 'IFNULL' );
        $this->set( 'update_at', BDb::now() );

        return true;
    }
}
