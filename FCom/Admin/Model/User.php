<?php

class FCom_Admin_Model_User extends FCom_Core_Model_Abstract
{
    const
        STATUS_ACTIVE   = 'A',
        STATUS_INACTIVE = 'I',
        STATUS_DELETED  = 'D'
    ;

    protected static $_table = 'a_admin_user';

    protected static $_sessionUser;

    public static function statusOptions()
    {
        return array(
            static::STATUS_ACTIVE => 'Active',
            static::STATUS_INACTIVE => 'Inactive',
        );
    }

    public function setPassword($password)
    {
        $this->password_hash = BUtil::fullSaltedHash($password);
        return $this;
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

    public static function has_role($orm, $role)
    {
        return $orm->where('role', $role);
    }

    public static function options()
    {
        $users = static::i()->orm()
            ->select('id')->select('firstname')->select('lastname')
            ->find_many();
        $options = array();
        foreach ($users as $u) {
            $options[$u->id] = $u->firstname.' '.$u->lastname;
        }
        return $options;
    }

    static public function sessionUser($reset=false)
    {
        if ($reset || !static::$_sessionUser) {
            static::$_sessionUser = BSession::i()->data('admin_user');
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

    static public function login($username, $password)
    {
        /** @var Denteva_Model_User */
        $user = static::i()->orm()->where_complex(array('OR'=>array('username'=>$username, 'email'=>$username)))->find_one();
        if (!$user || !$user->validatePassword($password)) {
            return false;
        }

        BSession::i()->data('admin_user', $user);

        if ($user->locale) {
            setlocale(LC_ALL, $user->locale);
        }
        if ($user->timezone) {
            date_default_timezone_set($user->timezone);
        }
        BPubSub::i()->fire('FCom_Admin_Model_User::login.after', array('user'=>$user));

        return true;
    }

    static public function logout()
    {
        BSession::i()->data('admin_user', false);
        static::$_sessionUser = null;
    }

    public function tzOffset()
    {
        return BLocale::i()->tzOffset($this->tz);
    }

    public function config()
    {
        return $this->relatedModel('Denteva_Model_Config', array('user_id'=>$this->id), true);
    }

    public function fullname()
    {
        return $this->firstname.' '.$this->lastname;
    }
}