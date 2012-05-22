<?php

class FCom_Email_Model_Pref extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_email_pref';

    public static function unsubAll($email)
    {
        $pref = static::load($email, 'email');
        $pref->set('unsub_all', 1)->save();
        return $pref;
    }

    public static function getUrl($email, $params=array())
    {
        if (true===$params) {
            $params = array('unsub_all'=>'true');
        }
        $params += array('email'=>$email, 'token'=>static::getToken($email));
        return BUtil::setUrlQuery(BApp::href('email/pref'), $params);
    }

    public static function getToken($email, $salt=null)
    {
        $pref = static::load($email, 'email');
        if (!$salt) $salt = BUtil::randomString(8);
        return $salt.'_'.sha1($salt.'|'.$email.'|'.($pref ? $pref->update_dt : ''));
    }

    public static function validateToken($email, $token)
    {
        list($salt, $hash) = explode('_', $token);
        return static::getToken($email, $salt) === $salt.'_'.$hash;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->create_dt) $this->create_dt = BDb::now();
        $this->update_dt = BDb::now();
        return true;
    }

    public static function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `unsub_all` tinyint(4) NOT NULL,
  `sub_newsletter` tinyint(4) NOT NULL,
  `create_dt` datetime NOT NULL,
  `update_dt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}