<?php

class FCom_Customer_Model_Customer extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_customer';
    protected static $_origClass = __CLASS__;

    protected static $_sessionUser;

    public function setPassword($password)
    {
        $this->password_hash = BUtil::fullSaltedHash($password);
        return $this;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if ($this->password) {
            $this->password_hash = BUtil::fullSaltedHash($this->password);
        }
        return true;
    }

    public function getData()
    {
        $data = $this->as_array();
        unset($data['password_hash']);
        return $data;
    }

    public function validatePassword($password)
    {
        return BUtil::validateSaltedHash($password, $this->password_hash);
    }

    static public function sessionUser($reset=false)
    {
        if ($reset || !static::$_sessionUser) {
            $data = BSession::i()->data('customer_user');
            if (is_string($data)) {
                static::$_sessionUser = $data ? unserialize($data) : false;
            } else {
                return false;
            }
        }
        return static::$_sessionUser;
    }

    static public function sessionUserId()
    {
        $user = self::sessionUser();
        return !empty($user) ? $user['id'] : false;
    }

    static public function isLoggedIn()
    {
        return static::sessionUser() ? true : false;
    }

    static public function authenticate($username, $password)
    {
        /** @var FCom_Admin_Model_User */
        $user = static::i()->orm()->where('email', $username)->find_one();
        if (!$user || !$user->validatePassword($password)) {
            return false;
        }
        return $user;
    }

    public function login()
    {
        $this->set('last_login', BDb::now())->save();

        BSession::i()->data('customer_user', serialize($this));
        static::$_sessionUser = $this;

        if ($this->locale) {
            setlocale(LC_ALL, $this->locale);
        }
        if ($this->timezone) {
            date_default_timezone_set($this->timezone);
        }
        BPubSub::i()->fire(__METHOD__.'.after', array('user'=>$this));
        return $this;
    }

    static public function logout()
    {
        BSession::i()->data('customer_user', false);
        static::$_sessionUser = null;
    }

    public function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `firstname` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `lastname` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` text COLLATE utf8_unicode_ci,
  `default_shipping_id` int(11) DEFAULT NULL,
  `default_billing_id` int(11) DEFAULT NULL,
  `create_dt` datetime NOT NULL,
  `update_dt` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `token` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    public static function import($data)
    {
        BPubSub::i()->fire(__METHOD__.'.before', array('data'=>&$data));

        if (!empty($data['customer']['id'])) {
            $cust = static::load($data['customer']['id']);
        }
        if (empty($cust)) {
            if (empty($data['customer']['email'])) {
                $result = array('status'=>'error', 'message'=>'Missing email address');
                return $result;
            }
            $cust = static::load($data['customer']['email'], 'email');
        }
        $result['status'] = '';
        if (!$cust) {
            $cust = static::create();
            $result['status'] = 'created';
        }
        $result['model'] = $cust;
        $cust->set($data['customer']);
        if ($cust->is_dirty()) {
            if (!$result['status']) $result['status'] = 'updated';
            $cust->save();
        }

        $result['addr'] = FCom_Customer_Model_Address::i()->import($data, $cust);

        BPubSub::i()->fire(__METHOD__.'.after', array('data'=>$data, 'result'=>&$result));

        return $result;
    }

    public function defaultBilling()
    {
        if ($this->default_billing_id && !$this->default_billing) {
            $this->default_billing = FCom_Customer_Model_Address::i()->load($this->default_billing);
        }
        return $this->default_billing;
    }
}