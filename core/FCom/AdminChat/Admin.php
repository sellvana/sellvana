<?php

/**
 * Class FCom_AdminChat_Admin
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_AdminChat_Model_Participant $FCom_AdminChat_Model_Participant
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 * @property FCom_AdminChat_Model_Chat $FCom_AdminChat_Model_Chat
 * @property FCom_AdminChat_Model_History $FCom_AdminChat_Model_History
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 */
class FCom_AdminChat_Admin extends BClass
{
    public function onAdminUserLogout($args)
    {
        $userId = $this->FCom_Admin_Model_User->sessionUserId();
        $this->FCom_AdminChat_Model_Participant->delete_many(['user_id' => $userId]);
    }

    public function getInitialState()
    {
        $p = $this->BDebug->debug('ADMINCHAT INITIAL STATE');

        $user = $this->FCom_Admin_Model_User->sessionUser();
        if (!$user) {
            return [];
        }
        $userId = $user->id();
        $userName = $user->get('username');

        $sessionClient = $this->FCom_PushServer_Model_Client->sessionClient();
        $sessionClient->subscribe('adminuser');

        $chats = [];

        $reUsername = '#(^|\s*,\s*)' . preg_quote($userName, '#') . '(\s*,\s*|$)#';
        $chatModels = $this->FCom_AdminChat_Model_Chat->orm('c')
            ->join('FCom_AdminChat_Model_Participant', ['c.id', '=', 'p.chat_id'], 'p')->where('p.user_id', $userId)
            ->select('c.id')
            ->select('p.status', 'chat_window_status')
            ->select('p.chat_title')
            ->find_many_assoc('id');
        foreach ($chatModels as $c) {
            $chats[$c->id()] = [
                'channel' => 'adminchat:' . $c->id(),
                'title' => $c->get('chat_title'),
                'status' => $c->get('chat_window_status'),
                'history' => [],
            ];
        }
        if ($chats) {
            foreach ($chats as $chatId => $chat) {
                $sessionClient->subscribe($chat['channel']);
            }
            $history = $this->FCom_AdminChat_Model_History->orm('h')
                ->join('FCom_Admin_Model_User', ['u.id', '=', 'h.user_id'], 'u')
                ->where_in('h.chat_id', array_keys($chats))
                ->where_gt('h.create_at', date('Y-m-d', time()-86400))
                ->select('h.*')
                ->select('u.username')
                ->order_by_asc('h.create_at')
                ->find_many();

            foreach ($history as $msg) {
                $chats[$msg->get('chat_id')]['history'][] = [
                    'time' => date("Y-m-d H:i:s +0000", strtotime($msg->get('create_at'))),
                    'username' => $msg->get('username'),
                    'text' => $msg->get('text'),
                ];
            }
        }

        $users = [];
        $userModels = $this->FCom_Admin_Model_User->orm('u')
            ->left_outer_join('FCom_AdminChat_Model_UserStatus', ['us.user_id', '=', 'u.id'], 'us')
            ->select('u.username')->select('u.firstname')->select('u.lastname')->select('us.status')
            ->select('u.email')
            ->find_many();
        foreach ($userModels as $user) {
            $users[] = [
                'username' => $user->get('username'),
                'firstname' => $user->get('firstname'),
                'lastname' => $user->get('lastname'),
                'status' => $user->get('status') ?: 'offline',
                'avatar' => $this->BUtil->gravatar($user->get('email')),
            ];
        }

        $result = [
            'chats' => array_values($chats),
            'users' => $users,
        ];

        $this->BDebug->profile($p);

        return $result;
    }
}
