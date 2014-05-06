<?php

class FCom_AdminChat_PushServer_Chat extends FCom_PushServer_Service_Abstract
{
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        if (!FCom_Admin_Model_User::i()->isLoggedIn()) {
            $this->reply(['channel' => 'client', 'signal' => 'logout']);
            return false;
        }

        return true;
    }

    public function signal_open()
    {
        // start the chat, receive initial history

        //$this->_client->send($this->_message);
        $user = FCom_Admin_Model_User::i()->load($this->_message['user'], 'username');
        if (!$user) {
            $this->reply(['signal' => 'error', 'description' => 'Unknown username']);
            return;
        }
        $chat = FCom_AdminChat_Model_Chat::i()->openWithUser($user);
        $participant = FCom_AdminChat_Model_Participant::i()->load([
            'chat_id' => $chat->id(),
            'user_id' => FCom_Admin_Model_User::i()->sessionUserId(),
        ]);
        if ($participant->get('status') !== 'open') {
            $participant->set('status', 'open')->save();
        }
        $channel = $chat->getChannel();
        $channel->send([
            'signal' => 'chats',
            'chats' => [
                [
                    'channel' => $channel->get('channel_name'),
                    'title' => $participant->get('chat_title'),
                    'status'  => 'open',
                    'history' => $chat->getHistoryArray(),
                ],
            ],
        ]);
    }

    public function signal_invite()
    {

    }

    public function signal_say()
    {
        $chan = $this->_message['channel'];
        $chat = FCom_AdminChat_Model_Chat::i()->findByChannel($chan);
        $channel = FCom_PushServer_Model_Channel::i()->getChannel($chan);
        if (!$chat) {
            $channel->send(['signal' => 'error', 'description' => 'Chat not found']);
            return;
        }
        $user = FCom_Admin_Model_User::i()->sessionUser();
        $msg = $chat->addHistory($user, $this->_message['text']);
#BDebug::log('ADMINCHAT: say '.print_r($this->_message, 1));
        $channel->send([
            'signal'   => 'say',
            'text'     => $this->_message['text'],
            'username' => $user->get('username'),
            'msg_id'   => $this->_message['msg_id'],
            'time'     => gmdate("Y-m-d H:i:s +0000")
        ]);
    }

    public function signal_kick()
    {

    }

    public function signal_window_status()
    {
        $channel = $this->_message['channel'];
        $chat = FCom_AdminChat_Model_Chat::i()->findByChannel($channel);
        $userId = FCom_Admin_Model_User::i()->sessionUserId();
        $hlp = FCom_AdminChat_Model_Participant::i();
        $data = ['chat_id' => $chat->id(), 'user_id' => $userId];
        $participant = $hlp->load($data);
        $participant->set('status', $this->_message['status'])->save();
    }

    public function signal_leave()
    {
        $channel = $this->_message['channel'];
        $chat = FCom_AdminChat_Model_Chat::i()->findByChannel($channel);
        $user = FCom_Admin_Model_User::i()->sessionUser();
        $chat->removeParticipant($user);

        FCom_PushServer_Model_Channel::i()->getChannel($channel)
            ->send(['signal' => 'leave', 'username' => $user->get('username')]);

        $this->_client->getChannel()
            ->send(['channel' => $channel, 'signal' => 'close']);
    }

    public function signal_text()
    {

    }
}
