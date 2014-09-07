<?php defined('BUCKYBALL_ROOT_DIR') || die();

/*
- id
- user_id
- status
- message
- create_at // when user joined the session
- update_at // the last time user got updated on chat
*/

class FCom_AdminChat_Model_UserStatus extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_adminchat_userstatus';
    static protected $_origClass = __CLASS__;

    static protected $_sessionUserStatus;

    public function sessionUserStatus($createIfNotExists = false, $defaultStatus = 'offline')
    {
        if (!static::$_sessionUserStatus) {
            $userId = $this->FCom_Admin_Model_User->sessionUserId();
            if (!$userId) {
                return false;
            }

            static::$_sessionUserStatus = $this->load($userId, 'user_id');

            if ($createIfNotExists && !static::$_sessionUserStatus) {
                static::$_sessionUserStatus = $this->create([
                    'user_id' => $userId,
                    'status' => $defaultStatus,
                ]);
            }
        }
        return static::$_sessionUserStatus;
    }

    public function changeStatus($status, $userId = null)
    {
        $userStatus = $this->sessionUserStatus(true);
        if ($userStatus->get('status') != $status) {
            $userStatus->set('status', $status)->save();

            $userHlp = $this->FCom_Admin_Model_User;
            if (is_null($userId) || $userHlp->sessionUserId() === $userId) {
                $user = $userHlp->sessionUser();
            } else {
                $user = $userHlp->load($userId);
            }
            $channel = $this->FCom_PushServer_Model_Channel->getChannel('adminuser', true);
            $channel->send([
                'signal' => 'status',
                'users' => [
                    ['username' => $user->get('username'), 'status' => $status],
                ],
            ]);
        }
        return $this;
    }

}
