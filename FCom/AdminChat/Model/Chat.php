<?php
/*
- id
- status
- num_participants
- create_at // when the session was created
- update_at // when the session had last message
*/
class FCom_AdminChat_Model_Chat extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_adminchat_chat';
    static protected $_origClass = __CLASS__;

    public function getChannel()
    {
        return FCom_PushServer_Model_Channel::i()->getChannel( 'adminchat:' . $this->id, true );
    }

    static public function findByChannel( $channel )
    {
        if ( !preg_match( '/^adminchat:(.*)$/', $channel, $m ) ) {
            return false;
        }
        return FCom_AdminChat_Model_Chat::i()->load( $m[ 1 ] );
    }

    static public function openWithUser( $remoteUser )
    {
        // get local user
        $user = FCom_Admin_Model_User::i()->sessionUser();
        // check if there's existing chat with only 2 users
        $chats = static::orm( 'c' )->where( 'num_participants', 2 )
            ->join( 'FCom_AdminChat_Model_Participant', [ 'p1.chat_id', '=', 'c.id' ], 'p1' )
            ->join( 'FCom_AdminChat_Model_Participant', [ 'p2.chat_id', '=', 'c.id' ], 'p2' )
            ->where( 'p1.user_id', $user->id() )
            ->where( 'p2.user_id', $remoteUser->id() )
            ->select( 'c.*' )
            ->find_many_assoc();
        if ( $chats ) {
            foreach ( $chats as $chat ) {
                //$chat->delete();
                return $chat;
            }
        }
        $chat = static::create( [
            'owner_user_id' => $user->id(),
            'title' => $user->get( 'username' ) . ', ' . $remoteUser->get( 'username' ),
        ] )->save();

        $chat->addParticipant( $user, [ 'chat_title' => $remoteUser->get( 'username' ) ] );
        $chat->addParticipant( $remoteUser, [ 'chat_title' => $user->get( 'username' ) ] );

        $chat->save();

        return $chat;
    }

    public function getHistoryArray()
    {
        $history = FCom_AdminChat_Model_History::i()->orm( 'h' )
            ->join( 'FCom_Admin_Model_User', [ 'u.id', '=', 'h.user_id' ], 'u' )
            ->select( 'u.username' )->select( 'h.create_at' )->select( 'h.text' )
            ->where( 'h.chat_id', $this->id() )
            ->where_gt( 'h.create_at', date( 'Y-m-d', time()-86400 ) )
            ->order_by_asc( 'h.create_at' )->find_many();
        $text = [];
        foreach ( $history as $msg ) {
            $text[] = [
                'time' => gmdate( "Y-m-d H:i:s +0000", strtotime( $msg->get( 'create_at' ) ) ),
                'username' => $msg->get( 'username' ),
                'text' => $msg->get( 'text' ),
            ];
        }
        return $text;
    }

    public function addHistory( $user, $text )
    {
        $msg = FCom_AdminChat_Model_History::i()->create( [
            'chat_id' => $this->id(),
            'user_id' => $user->id(),
            'text' => $text,
        ] )->save();
        return $msg;
    }

    public function addParticipant( $user, $extraData = [] )
    {
        $clients = FCom_PushServer_Model_Client::i()->findByAdminUser( $user );
        $channel = $this->getChannel();

        foreach ( $clients as $client ) {
            $client->subscribe( $channel );
        }

        $hlp = FCom_AdminChat_Model_Participant::i();
        $data = [ 'chat_id' => $this->id(), 'user_id' => $user->id() ];
        $participant = $hlp->load( $data );
        if ( !$participant ) {
            $data[ 'status' ] = 'open';
            $data = array_merge( $data, $extraData );
            $participant = $hlp->create( $data )->save();
            $this->add( 'num_participants' );
            $channel->send( [ 'signal' => 'join', 'username' => $user->get( 'username' ) ] );
        }

        return $participant;
    }

    public function removeParticipant( $user )
    {
        $clients = FCom_PushServer_Model_Client::i()->findByAdminUser( $user );
        $channel = $this->getChannel();
        foreach ( $clients as $client ) {
            $client->unsubscribe( $channel );
        }

        FCom_AdminChat_Model_Participant::i()->delete_many( [
            'chat_id' => $this->id(),
            'user_id' => $user->id(),
        ] );

        $this->add( 'num_participants', -1 );

        if ( $this->get( 'num_participants' ) < 2 ) {
            $this->set( 'status', 'closed' )->save();
        }

        return $this;
    }

    public function onBeforeSave()
    {
        if ( !parent::onBeforeSave() ) return false;

        $this->set( 'status', 'active', 'IFNULL' );
        $this->set( 'create_at', BDb::now(), 'IFNULL' );
        $this->set( 'update_at', BDb::now() );

        return true;
    }

    public function onBeforeDelete()
    {
        $this->getChannel()->delete();
    }
}
