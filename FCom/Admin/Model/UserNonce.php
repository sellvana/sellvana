<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Not sure yet if to allow SSO in emailed links
 *
 * @property int id
 * @property int user_id
 * @property varchar(20) nonce
 * @property datetime create_at
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class FCom_Admin_Model_UserNonce extends FCom_Core_Model_Abstract
{
    static protected $_origClass = __CLASS__;
    static protected $_table = 'fcom_admin_user_nonce';

    public function generateNonce($userId)
    {
        for ($i = 0; $i < 10; $i++) {
            $nonce = $this->BUtil->randomString(20);
            if (!$this->load($nonce, 'nonce')) {
                break;
            }
        }
        if ($i === 10) {
            throw new BException('Unable to find available nonce'); //???
        }
        $nonceRecord = $this->create([
            'user_id' => $userId,
            'nonce' => $nonce,
            'create_at' => $this->BDb->now(),
        ])->save();
        return $nonce;
    }

    public function login($nonce)
    {
        $user = false;
        $userHlp = $this->FCom_Admin_Model_User;
        if ($userHlp->isLoggedIn()) {
            $user = $userHlp->sessionUser();
        }
        if ($nonce) {
            $nonceRecord = $this->load($nonce, 'nonce');
            if ($nonceRecord) {
                if (!$user) {
                    $user = $userHlp->load($nonceRecord->user_id)->login();
                }
                $nonceRecord->delete();
            }
        }
        return $user;
    }

    public function gc()
    {
        $this->delete_many('create_at < ' . date('Y-m-d H:i:s', time()-60 * 60 * 24 * 7));
    }
}
