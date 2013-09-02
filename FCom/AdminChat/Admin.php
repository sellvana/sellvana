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
        $userId = $args['client']->admin_user_id;
        if (!$userId) {
            return;
        }
        if ($args['status'] === 'offline') {
            $clients = FCom_PushServer_Model_Client::i()->findByAdminUser($userId);
            foreach ($clients as $c) { // check other clients
                if ($c->id !=== $args['client']->id && $c->status !== 'offline') {
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
}
