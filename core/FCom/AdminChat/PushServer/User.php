<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_AdminChat_PushServer_User
 *
 * @property FCom_AdminChat_Model_UserStatus $FCom_AdminChat_Model_UserStatus
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class FCom_AdminChat_PushServer_User extends FCom_PushServer_Service_Abstract
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

    public function signal_subscribe()
    {
        $this->_client->subscribe($this->_message['channel']);
    }

    public function signal_status()
    {
        $userId = $this->FCom_Admin_Model_User->sessionUserId();
        $this->FCom_AdminChat_Model_UserStatus->changeStatus($this->_message['status'], $userId);
    }
}
