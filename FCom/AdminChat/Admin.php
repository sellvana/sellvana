<?php

class FCom_AdminChat_Admin extends BClass
{
    static public function onAdminUserLogout($args)
    {
        $userId = FCom_Admin_Model_User::i()->sessionUserId();
        FCom_AdminChat_Model_Participant::i()->delete_many(array('user_id' => $userId));
    }

    static public function onClientSetStatus($args)
    {
        return; // not needed, status set from pushclient

        $userId = $args['client']->admin_user_id;
        if (!$userId) {
            return;
        }
        if ($args['status'] === 'offline') {
            $clients = FCom_PushServer_Model_Client::i()->findByAdminUser($userId);
            foreach ($clients as $c) { // check other clients
                if ($c->id !== $args['client']->id && $c->status !== 'offline') {
                    return; // if at least one of the not offline, abort
                }
            }
        } else {
            $args['client']->send(array('signal' => 'status', 'status' => $args['status'])); // update other clients of the user
        }

        // send admin user status change
        $user = FCom_Admin_Model_User::i()->load($userId);
        FCom_PushServer_Model_Channel::i()->getChannel('adminuser:'.$user->username, true)
            ->send(array('signal' => 'status', 'status' => $args['status']));
    }

    public function getInitialState()
    {
        $userId = FCom_Admin_Model_User::i()->sessionUserId();

        $chats = array();

        $chatModels = FCom_AdminChat_Model_Chat::i()->orm('c')
            ->join('FCom_AdminChat_Model_Participant', array('c.id','=','p.chat_id'), 'p')
            ->where('p.user_id', $userId)
            ->select('c.id')
            ->select('(p.status)', 'chat_window_status')
            ->find_many_assoc('c.id');
        foreach ($chatModels as $c) {
            $chats[$c->id()] = array(
                'channel' => 'adminchat:' . $c->id(),
                'status' => $c->get('chat_window_status'),
                'history' => array(),
            );
        }

        $history = FCom_AdminChat_Model_History::i()->orm()
            ->where_in('chat_id', array_keys($chatModels))
            ->where_gt('create_at', date('Y-m-d', time()-86400))
            ->find_many();
        foreach ($history as $msg) {
            $chats[$msg->get('chat_id')][] = array(
                'time' => gmdate("Y-m-d H:i:s +0000", strtotime($msg->get('create_at'))),
                'username' => $msg->get('username'),
                'text' => $msg->get('text'),
            );
        }
        return array(
            'users' => $users,
            'chats' => $chats,
        )
    }
}
