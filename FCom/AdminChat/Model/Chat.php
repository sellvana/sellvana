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

    static public function start($remoteUser)
    {
        // get local user
        $user = FCom_Admin_Model_User::i()->sessionUser();
        // check if there's existing chat with only 2 users
        $chat = static::orm('c')->where('num_participants', 2)
            ->join('FCom_AdminChat_Model_Participant', array('p1.chat_id','=','c.id'), 'p1')
            ->join('FCom_AdminChat_Model_Participant', array('p2.chat_id','=','c.id'), 'p2')
            ->where('p1.user_id', $user->id)
            ->where('p2.user_id', $remoteUser->id)
            ->find_one();
        if ($existing) {
            return $chat;
        }
        $chat = static::create()->save();
        $channelName = 'FCom_AdminChat:chat:' . $chat->id;
        $channel = FCom_PushServer_Model_Channel::i()->getChannel($channelName, true);
        $channel->subscribeService('FCom_AdminChat_PushServer::channel_chat'));
    }

    public function addParticipant($user)
    {
        $client = FCom_PushServer_Model_Client::i()->findByAdminUserId($user);
        $client->subscribe('FCom_AdminChat:chat:' . $this->id);
        return $this;
    }

    public function removeParticipant($user)
    {
        $client = FCom_PushServer_Model_Client::i()->findByAdminUserId($user);
        $client->unsubscribe('FCom_AdminChat:chat:' . $this->id);
        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('status', 'active', null);
        $this->set('num_participants', 2, null);
        $this->set('create_at', BDb::now(), null);
        $this->set('update_at', BDb::now());

        return true;
    }
}
