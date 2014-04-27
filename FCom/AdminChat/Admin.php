<?php

class FCom_AdminChat_Admin extends BClass
{
    static public function onAdminUserLogout( $args )
    {
        $userId = FCom_Admin_Model_User::i()->sessionUserId();
        FCom_AdminChat_Model_Participant::i()->delete_many( array( 'user_id' => $userId ) );
    }

    static public function onClientSetStatus( $args )
    {
        return; // not needed, status set from pushclient

        $userId = $args[ 'client' ]->admin_user_id;
        if ( !$userId ) {
            return;
        }
        if ( $args[ 'status' ] === 'offline' ) {
            $clients = FCom_PushServer_Model_Client::i()->findByAdminUser( $userId );
            foreach ( $clients as $c ) { // check other clients
                if ( $c->id !== $args[ 'client' ]->id && $c->status !== 'offline' ) {
                    return; // if at least one of the not offline, abort
                }
            }
        } else {
            $args[ 'client' ]->send( array( 'signal' => 'status', 'status' => $args[ 'status' ] ) ); // update other clients of the user
        }

        // send admin user status change
        $user = FCom_Admin_Model_User::i()->load( $userId );
        FCom_PushServer_Model_Channel::i()->getChannel( 'adminuser:' . $user->username, true )
            ->send( array( 'signal' => 'status', 'status' => $args[ 'status' ] ) );
    }

    public function getInitialState()
    {
        $p = BDebug::debug( 'ADMINCHAT INITIAL STATE' );

        $user = FCom_Admin_Model_User::i()->sessionUser();
        if ( !$user ) {
            return array();
        }
        $userId = $user->id();
        $userName = $user->get( 'username' );

        $sessionClient = FCom_PushServer_Model_Client::i()->sessionClient();
        $sessionClient->subscribe( 'adminuser' );

        $chats = array();

        $reUsername = '#(^|\s*,\s*)' . preg_quote( $userName ) . '(\s*,\s*|$)#';
        $chatModels = FCom_AdminChat_Model_Chat::i()->orm( 'c' )
            ->join( 'FCom_AdminChat_Model_Participant', array( 'c.id', '=', 'p.chat_id' ), 'p' )->where( 'p.user_id', $userId )
            ->select( 'c.id' )
            ->select( 'p.status', 'chat_window_status' )
            ->select( 'p.chat_title' )
            ->find_many_assoc( 'c.id' );
        foreach ( $chatModels as $c ) {
            $chats[ $c->id() ] = array(
                'channel' => 'adminchat:' . $c->id(),
                'title' => $c->get( 'chat_title' ),
                'status' => $c->get( 'chat_window_status' ),
                'history' => array(),
            );
        }
        if ( $chats ) {
            foreach ( $chats as $chatId => $chat ) {
                $sessionClient->subscribe( $chat[ 'channel' ] );
            }
            $history = FCom_AdminChat_Model_History::i()->orm( 'h' )
                ->join( 'FCom_Admin_Model_User', array( 'u.id', '=', 'h.user_id' ), 'u' )
                ->where_in( 'h.chat_id', array_keys( $chats ) )
                ->where_gt( 'h.create_at', date( 'Y-m-d', time()-86400 ) )
                ->select( 'h.*' )
                ->select( 'u.username' )
                ->order_by_asc( 'h.create_at' )
                ->find_many();

            foreach ( $history as $msg ) {
                $chats[ $msg->get( 'chat_id' ) ][ 'history' ][] = array(
                    'time' => date( "Y-m-d H:i:s +0000", strtotime( $msg->get( 'create_at' ) ) ),
                    'username' => $msg->get( 'username' ),
                    'text' => $msg->get( 'text' ),
                );
            }
        }

        $users = array();
        $userModels = FCom_Admin_Model_User::i()->orm( 'u' )
            ->left_outer_join( 'FCom_AdminChat_Model_UserStatus', array( 'us.user_id', '=', 'u.id' ), 'us' )
            ->select( 'u.username' )->select( 'u.firstname' )->select( 'u.lastname' )->select( 'us.status' )
            ->select( 'u.email' )
            ->find_many();
        foreach ( $userModels as $user ) {
            $users[] = array(
                'username' => $user->get( 'username' ),
                'firstname' => $user->get( 'firstname' ),
                'lastname' => $user->get( 'lastname' ),
                'status' => $user->get( 'status' ),
                'avatar' => BUtil::gravatar( $user->get( 'email' ) ),
            );
        }

        $result = array(
            'chats' => array_values( $chats ),
            'users' => $users,
        );

        BDebug::profile( $p );

        return $result;
    }
}
