<?php

class FCom_Admin_Model_User extends FCom_Core_Model_Abstract
{
    const
        STATUS_ACTIVE   = 'A',
        STATUS_INACTIVE = 'I',
        STATUS_DELETED  = 'D'
    ;

    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_admin_user';

    protected static $_sessionUser;

    protected static $_fieldOptions = array(
        'status' => array(
            'A' => 'Active',
            'I' => 'Inactive',
        ),
        'is_superadmin' => array(
            '0' => 'No',
            '1' => 'Yes',
        ),
    );

    protected $_persModel;
    protected $_persData;

    protected $_permissions;

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
            $data = BSession::i()->data('admin_user');
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
        $user = static::i()->orm()
            ->where_complex(array('OR'=>array(
                'username'=>$username,
                'email'=>$username)))
            ->find_one();
        if (!$user || !$user->validatePassword($password)) {
            return false;
        }
        return $user;
    }

    public function login()
    {
        $this->set('last_login', BDb::now())->save();

        BSession::i()->data('admin_user', serialize($this));
        static::$_sessionUser = $this;

        if ($this->locale) {
            setlocale(LC_ALL, $this->locale);
        }
        if ($this->timezone) {
            date_default_timezone_set($this->timezone);
        }
        BPubSub::i()->fire('FCom_Admin_Model_User::login.after', array('user'=>$this));
        return $this;
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

    public function fullname()
    {
        return $this->firstname.' '.$this->lastname;
    }

    /**
    * Personalize user preferences (grids, dashboard, etc)
    * - grid
    *   - {grid-name}
    *     - colModel
    *
    * @param array|null $data
    * @return FCom_Admin_Model_User|array
    */
    public function personalize($data=null)
    {
        if (!$this->orm) {
            return $this->sessionUser()->personalize($data);
        }
        if (!$this->_persModel) {
            $this->_persModel = FCom_Admin_Model_Personalize::i()->load($this->id, 'user_id');
            if (!$this->_persModel) {
                $this->_persModel = FCom_Admin_Model_Personalize::i()->create(array('user_id'=>$this->id));
            }
        }
        if (!$this->_persData) {
            $this->_persData = $this->_persModel->data_json ? BUtil::fromJson($this->_persModel->data_json) : array();
        }
        if (is_null($data)) {
            return $this->_persData;
        }
        $this->_persData = BUtil::arrayMerge($this->_persData, $data);
        $this->_persModel->set('data_json', BUtil::toJson($this->_persData))->save();
        return $this;
    }

    public function getPermission($paths)
    {
        if ($this->is_superadmin) {
            return true;
        }
        if (!$this->role_id) {
            return false;
        }
        if (!$this->permissions) {
            $this->permissions = FCom_Admin_Model_Role::i()->load($this->role_id)->permissions;
        }
        if (is_string($paths)) {
            $paths = explode(',', $paths);
        }
        foreach ($paths as $p) {
            if (!empty($this->permissions[$p])) {
                return true;
            }
        }
        return false;
    }

    public static function install()
    {
        $tUser = static::table();
        $tRole = FCom_Admin_Model_Role::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tUser} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `superior_id` int(10) unsigned DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `is_superadmin` tinyint(4) NOT NULL DEFAULT '0',
  `role_id` int(11) unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `phone_ext` varchar(50) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `status` char(1) NOT NULL DEFAULT 'A',
  `tz` varchar(50) NOT NULL DEFAULT 'America/Los_Angeles',
  `locale` varchar(50) NOT NULL DEFAULT 'en_US',
  `create_dt` datetime NOT NULL,
  `update_dt` datetime DEFAULT NULL,
  `token` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_email` (`email`),
  UNIQUE KEY `UNQ_username` (`username`),
  CONSTRAINT `FK_{$tUser}_role` FOREIGN KEY (`role_id`) REFERENCES {$tRole} (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_{$tUser}_superior` FOREIGN KEY (`superior_id`) REFERENCES {$tUser} (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}