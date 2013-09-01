<?php

class FCom_AdminChat_PushServer extends FCom_PushServer_Service_Abstract
{
    static public function bootstrap()
    {
        FCom_PushServer_Main::i()
            ->addService('adminchat', __CLASS__)
            ->addService('/^adminchat:(.*)$/', __CLASS__)
        ;
    }

    public function signal_start()
    {
        // start the chat, receive initial history

        //$this->_client->send($this->_message);
        $user = FCom_Admin_Model_User::i()->load($this->_message['user'], 'username');
        if (!$user) {
            $this->reply(array('signal' => 'error', 'description' => 'Unknown username'));
        }
        $chat = FCom_AdminChat_Model_Chat::i()->start($user);
        $chat->getChannel()->send(array('signal' => 'start'));
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
                        'history' => 'TEST',
                    );
                }
                $this->reply(array('signal' => 'chats', 'chats' => $channels));
            } else {
                $this->reply(array('signal' => 'noop', 'description' => 'No chats found'));
            }
        }
    }

    public function signal_invite()
    {

    }

    public function signal_say()
    {
        $chan = $this->_message['channel'];
        if (preg_match('/^adminchat:(.*)/', $chan, $m)) {
            $user = FCom_Admin_Model_User::i()->sessionUser();
            $channel = FCom_PushServer_Model_Channel::i()->getChannel($chan);
            $channel->send(array('signal' => 'say', 'text' => $user->firstname . ': ' . $this->_message['text'].'<br>'));
            //$chat = FCom_AdminChat_Model_Chat::i()->load($m[1]);
        }

    }

    public function signal_kick()
    {

    }

    public function signal_leave()
    {

    }

    public function signal_text()
    {

    }
}
