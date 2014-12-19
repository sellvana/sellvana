<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_AdminChat_PushServer_Chat
 *
 * @property FCom_AdminChat_Model_Chat $FCom_AdminChat_Model_Chat
 * @property FCom_AdminChat_Model_Participant $FCom_AdminChat_Model_Participant
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class FCom_AdminChat_PushServer_Chat extends FCom_PushServer_Service_Abstract
{
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        if (!$this->FCom_Admin_Model_User->isLoggedIn()) {
            $this->reply(['channel' => 'client', 'signal' => 'logout']);
            return false;
        }

        return true;
    }

    /**
     * start the chat, receive initial history
     * @throws BException
     */
    public function signal_open()
    {

        //$this->_client->send($this->_message);
        $user = $this->FCom_Admin_Model_User->load($this->_message['user'], 'username');
        if (!$user) {
            $this->reply(['signal' => 'error', 'description' => 'Unknown username']);
            return;
        }
        $chat = $this->FCom_AdminChat_Model_Chat->openWithUser($user);
        $participant = $this->FCom_AdminChat_Model_Participant->loadWhere([
            'chat_id' => $chat->id(),
            'NOT' => ['user_id' => $this->FCom_Admin_Model_User->sessionUserId()],
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

    /**
     *
     */
    public function signal_invite()
    {

    }

    /**
     *
     */
    public function signal_say()
    {
        $chan = $this->_message['channel'];
        $chat = $this->FCom_AdminChat_Model_Chat->findByChannel($chan);
        $channel = $this->FCom_PushServer_Model_Channel->getChannel($chan);
        if (!$chat) {
            $channel->send(['signal' => 'error', 'description' => 'Chat not found']);
            return;
        }
        $user = $this->FCom_Admin_Model_User->sessionUser();
        $msg = $chat->addHistory($user, $this->_message['text']);
#$this->BDebug->log('ADMINCHAT: say '.print_r($this->_message, 1));
        $channel->send([
            'signal'   => 'say',
            'text'     => $this->_message['text'],
            'username' => $user->get('username'),
            'msg_id'   => $this->_message['msg_id'],
            'time'     => gmdate("Y-m-d H:i:s +0000")
        ]);
    }

    /**
     *
     */
    public function signal_kick()
    {

    }

    /**
     * @throws BException
     */
    public function signal_window_status()
    {
        $channel = $this->_message['channel'];
        $chat = $this->FCom_AdminChat_Model_Chat->findByChannel($channel);
        $userId = $this->FCom_Admin_Model_User->sessionUserId();
        $hlp = $this->FCom_AdminChat_Model_Participant;
        $data = ['chat_id' => $chat->id(), 'user_id' => $userId];
        $participant = $hlp->loadWhere($data);
        $participant->set('status', $this->_message['status'])->save();
    }

    public function signal_leave()
    {
        $channel = $this->_message['channel'];
        $chat = $this->FCom_AdminChat_Model_Chat->findByChannel($channel);
        $user = $this->FCom_Admin_Model_User->sessionUser();
        $chat->removeParticipant($user);

        $this->FCom_PushServer_Model_Channel->getChannel($channel)
            ->send(['signal' => 'leave', 'username' => $user->get('username')]);

        $this->_client->getChannel()
            ->send(['channel' => $channel, 'signal' => 'close']);
    }

    public function signal_text()
    {

    }
}
