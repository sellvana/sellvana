<?php

class FCom_Admin_Model_UserG2FA extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_admin_user_g2fa';
    static protected $_origClass = __CLASS__;

    public function createToken($userId)
    {
        $token = $this->BUtil->randomString(16);
        $rec = $this->create(['user_id' => $userId, 'token' => $token])->save();
        return $rec;
    }

    public function verifyToken($userId, $token, $days = 30)
    {
        $rec = $this->loadWhere(['user_id' => (int)$userId, 'token' => (string)$token]);
        if (!$rec) {
            return false;
        }
        if (time() - strtotime($rec->get('create_at')) > 30 * 86400) {
            $rec->delete();
            return false;
        }
        return $rec;
    }
}