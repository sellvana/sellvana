<?php

class FCom_AdminChat_PushServer_Chat extends FCom_PushServer_Service_Abstract
{
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        if (!FCom_Admin_Model_User::i()->isLoggedIn()) {
            $this->reply(array('channel' => 'client', 'signal' => 'logout'));
            return false;
        }

        return true;
    }

    public function signal_status()
    {
        if ($this->_client->admin_user_id) {
            $chats = FCom_AdminChat_Model_Chat::i()->orm('c')
                ->join('FCom_AdminChat_Model_Participant', array('c.id','=','p.chat_id'), 'p')
                ->where('p.user_id', $this->_client->admin_user_id)
                ->select('c.id')
                ->find_many();
            if ($chats) {
                $channels = array();
                foreach ($chats as $chat) {
                    //$chat->addParticipant($this->_client->admin_user_id); //TODO: figure out why it disappears??

                    $channels[] = array(
                        'channel' => 'adminchat:' . $chat->id,
                        'history' => nl2br($chat->getHistoryText()),
                    );
                }
                $this->reply(array('signal' => 'chats', 'chats' => $channels));
            } else {
                $this->reply(array('signal' => 'noop', 'description' => 'No chats found'));
            }
        }
    }

    public function signal_start()
    {
        // start the chat, receive initial history

        //$this->_client->send($this->_message);
        $user = FCom_Admin_Model_User::i()->load($this->_message['user'], 'username');
        if (!$user) {
            $this->reply(array('signal' => 'error', 'description' => 'Unknown username'));
            return;
        }
        $chat = FCom_AdminChat_Model_Chat::i()->start($user);
        $chat->getChannel()->send(array('signal' => 'start'));
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
            $channel->send(array('signal' => 'error', 'description' => 'Chat not found'));
            return;
        }
        $user = FCom_Admin_Model_User::i()->sessionUser();
        $msg = $chat->addHistory($user, $this->_message['text']);
#BDebug::log('ADMINCHAT: say '.print_r($this->_message, 1));
        $channel->send(array(
            'signal' => 'say',
            'text' => '['.date('h:i', strtotime($msg->create_at)).'] '.$user->username . ': ' . $this->_message['text'].'<br>',
        ));
    }

    public function signal_kick()
    {

    }

    public function signal_leave()
    {
        $channel = $this->_message['channel'];
        $chat = FCom_AdminChat_Model_Chat::i()->findByChannel($channel);
        $user = FCom_Admin_Model_User::i()->sessionUser();
        $chat->removeParticipant($user);

        FCom_PushServer_Model_Channel::i()->getChannel($channel)
            ->send(array('signal' => 'leave', 'username' => $user->username));

        $this->_client->getChannel()
            ->send(array('channel' => $channel, 'signal' => 'close'));
    }

    public function signal_text()
    {

    }
}
