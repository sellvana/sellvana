<?php

class FCom_AdminChat_PushServer_User extends FCom_PushServer_Service_Abstract
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

    public function signal_subscribe()
    {
        $this->_client->subscribe($this->_message['channel']);
    }

    public function signal_init()
    {
        $users = FCom_Admin_Model_User::i()->orm('u')
            ->left_outer_join('FCom_AdminChat_Model_UserStatus', array('us.user_id','=','u.id'), 'us')
            ->select('u.username')->select('u.firstname')->select('u.lastname')->select('us.status')
            ->find_many();

        $this->reply(array('channel' => 'adminuser', 'signal' => 'status', 'init' => true,
            'users' => BDb::many_as_array($users)));
    }

    public function signal_status()
    {
        FCom_AdminChat_Model_UserStatus::i()->changeStatus($this->_message['status'], $this->_client->get('admin_user_id'));
    }
}
