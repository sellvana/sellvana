<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_AdminChat_Model_Chat
 *
 * @property int $id
 * @property string $status
 * @property int $owner_user_id
 * @property int $num_participants
 * @property string $create_at when the session was created
 * @property string $update_at when the session had last message
 *
 * DI
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 * @property FCom_AdminChat_Model_Chat $FCom_AdminChat_Model_Chat
 * @property FCom_AdminChat_Model_History $FCom_AdminChat_Model_History
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 * @property FCom_AdminChat_Model_Participant $FCom_AdminChat_Model_Participant
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class FCom_AdminChat_Model_Chat extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_adminchat_chat';
    static protected $_origClass = __CLASS__;

    /**
     * @return FCom_PushServer_Model_Channel
     * @throws BException
     */
    public function getChannel()
    {
        return $this->FCom_PushServer_Model_Channel->getChannel('adminchat:' . $this->id, true);
    }

    /**
     * @param $channel
     * @return FCom_AdminChat_Model_Chat|bool
     * @throws BException
     */
    public function findByChannel($channel)
    {
        if (!preg_match('/^adminchat:(.*)$/', $channel, $m)) {
            return false;
        }
        return $this->FCom_AdminChat_Model_Chat->load($m[1]);
    }

    /**
     * @param FCom_Admin_Model_User $remoteUser
     * @return $this
     */
    public function openWithUser($remoteUser)
    {
        // get local user
        $user = $this->FCom_Admin_Model_User->sessionUser();
        // check if there's existing chat with only 2 users
        $chats = $this->orm('c')->where('num_participants', 2)
            ->join('FCom_AdminChat_Model_Participant', ['p1.chat_id', '=', 'c.id'], 'p1')
            ->join('FCom_AdminChat_Model_Participant', ['p2.chat_id', '=', 'c.id'], 'p2')
            ->where('p1.user_id', $user->id())
            ->where('p2.user_id', $remoteUser->id())
            ->select('c.*')
            ->find_many_assoc();
        if ($chats) {
            foreach ($chats as $chat) {
                //$chat->delete();
                return $chat;
            }
        }
        /** @var static $chat */
        $chat = $this->create([
            'owner_user_id' => $user->id(),
            'title' => $user->get('username') . ', ' . $remoteUser->get('username'),
        ])->save();

        $chat->addParticipant($user, ['chat_title' => $remoteUser->get('username')]);
        $chat->addParticipant($remoteUser, ['chat_title' => $user->get('username')]);

        $chat->save();

        return $chat;
    }

    /**
     * @return array
     */
    public function getHistoryArray()
    {
        $history = $this->FCom_AdminChat_Model_History->orm('h')
            ->join('FCom_Admin_Model_User', ['u.id', '=', 'h.user_id'], 'u')
            ->select('u.username')->select('h.create_at')->select('h.text')
            ->where('h.chat_id', $this->id())
            ->where_gt('h.create_at', date('Y-m-d', time()-86400))
            ->order_by_asc('h.create_at')->find_many();
        $text = [];
        foreach ($history as $msg) {
            $text[] = [
                'time' => date("Y-m-d H:i:s +0000", strtotime($msg->get('create_at'))),
                'username' => $msg->get('username'),
                'text' => $msg->get('text'),
            ];
        }
        return $text;
    }

    /**
     * @param $user
     * @param $text
     * @return $this
     */
    public function addHistory($user, $text)
    {
        $msg = $this->FCom_AdminChat_Model_History->create([
            'chat_id' => $this->id(),
            'user_id' => $user->id(),
            'text' => $text,
        ])->save();
        return $msg;
    }

    /**
     * @param $user
     * @param array $extraData
     * @return $this|BModel
     */
    public function addParticipant($user, $extraData = [])
    {
        $clients = $this->FCom_PushServer_Model_Client->findByAdminUser($user);
        $channel = $this->getChannel();

        foreach ($clients as $client) {
            $client->subscribe($channel);
        }

        $hlp = $this->FCom_AdminChat_Model_Participant;
        $data = ['chat_id' => $this->id(), 'user_id' => $user->id()];
        $participant = $hlp->loadWhere($data);
        if (!$participant) {
            $data['status'] = 'open';
            $data = array_merge($data, $extraData);
            $participant = $hlp->create($data)->save();
            $this->add('num_participants');
        } elseif ($participant->get('status') !== 'open') {
            $participant->set('status', 'open')->save();
        }
        $channel->send(['signal' => 'join', 'username' => $user->get('username')]);

        return $participant;
    }

    /**
     * @param $user
     * @return $this
     * @throws BException
     */
    public function removeParticipant($user)
    {
        $clients = $this->FCom_PushServer_Model_Client->findByAdminUser($user);
        $channel = $this->getChannel();
        foreach ($clients as $client) {
            $client->unsubscribe($channel);
        }

        $this->FCom_AdminChat_Model_Participant->delete_many([
            'chat_id' => $this->id(),
            'user_id' => $user->id(),
        ]);

        $this->add('num_participants', -1);

        if ($this->get('num_participants') < 2) {
            $this->set('status', 'closed')->save();
        }

        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('status', 'active', 'IFNULL');

        return true;
    }

    public function onBeforeDelete()
    {
        $this->getChannel()->delete();
    }
}
