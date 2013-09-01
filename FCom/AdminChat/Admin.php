<?php

class FCom_AdminChat_Admin extends BClass
{
    static public function onAdminUserLogout($args)
    {
        $userId = FCom_Admin_Model_User::i()->sessionUserId();
        FCom_AdminChat_Model_Participant::i()->delete_many(array('user_id' => $userId));
    }
}
