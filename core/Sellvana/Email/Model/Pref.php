<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Model class for table 'fcom_email_pref'
 * The followings are the available columns in table 'fcom_email_pref':
 * @property string $id
 * @property string $email
 * @property integer $unsub_all
 * @property integer $sub_newsletter
 * @property string $create_at
 * @property string $update_at
 */
class Sellvana_Email_Model_Pref extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_email_pref';

    protected static $_validationRules = [
        ['email', '@required'],
        ['email', '@email'],
    ];

    /**
     * @param string $email
     * @return $this
     * @throws BException
     */
    public function unsubAll($email)
    {
        $pref = $this->load($email, 'email');
        $pref->set('unsub_all', 1)->save();
        return $pref;
    }

    /**
     * @param string $email
     * @param array $params
     * @return string
     */
    public function getUrl($email, $params = [])
    {
        if (true === $params) {
            $params = ['unsub_all' => 'true'];
        }
        $params += ['email' => $email, 'token' => $this->getToken($email)];
        return $this->BUtil->setUrlQuery($this->BApp->href('email/pref', true, 1), $params);
    }

    /**
     * @param $email
     * @param null $salt
     * @return string
     * @throws BException
     */
    public function getToken($email, $salt = null)
    {
        $pref = $this->load($email, 'email');
        if (!$salt) $salt = $this->BUtil->randomString(8);
        return $salt . '_' . sha1($salt . '|' . $email . '|' . ($pref ? $pref->update_at : ''));
    }

    /**
     * @param $email
     * @param $token
     * @return bool
     */
    public function validateToken($email, $token)
    {
        if ($email && $token) {
            list($salt, $hash) = explode('_', $token);
            return $this->getToken($email, $salt) === $salt . '_' . $hash;
        }
        return false;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (!$this->create_at) $this->create_at = $this->BDb->now();
        $this->update_at = $this->BDb->now();
        return true;
    }
}
