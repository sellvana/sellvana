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

    protected static $_validationRules = array(
        array('username', '@required'),
        array('email', '@required'),
        array('email', '@email'),
        array('password', 'FCom_Admin_Model_User::validatePasswordSecurity'),

        //array('is_superadmin', '@integer'),
        array('role_id', '@integer'),
        //array('superior_id', '@integer'),
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

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if ($this->get('password')) {
            $this->set('password_hash', BUtil::fullSaltedHash($this->get('password')));
        }
        if ($this->get('api_password')) {
            $this->set('api_password_hash', BUtil::fullSaltedHash($this->get('api_password')));
        }
        if (!$this->get('role_id')) {
            $this->set('role_id', null);
        }
        $this->set('create_at', BDb::now(), 'IFNULL');
        $this->set('update_at', BDb::now());

        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        if ($this->id()===static::sessionUserId()) {
            BSession::i()->set('admin_user', serialize($this));
            static::$_sessionUser = $this;
        }
    }

    public function as_array(array $objHashes=array())
    {
        $data = parent::as_array();
        unset($data['password_hash']);
        unset($data['api_password_hash']);
        return $data;
    }

    public static function validatePasswordSecurity($data, $args)
    {
        if (!BConfig::i()->get('modules/FCom_Admin/password_strength')) {
            return true;
        }
        $password = $data[$args['field']];
        if(strlen($password) > 0 && !preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*()_+=}{><;:\]\[?]).{7,}/', $password)) {
            return BLocale::_('Password must be at least 7 characters in length and must include at least one letter, one capital letter, one number, and one special character.');
        }
        return true;
    }

    public function validatePassword($password, $field='password_hash')
    {
        return BUtil::validateSaltedHash($password, $this->get($field));
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
            $data = BSession::i()->get('admin_user');
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
        $user = static::sessionUser();
        return !empty($user) ? $user->id() : false;
    }

    static public function isLoggedIn()
    {
        return static::sessionUser() ? true : false;
    }

    static public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        BLoginThrottle::i()->init('FCom_Admin_Model_User', $username);
        /** @var FCom_Admin_Model_User */
        $user = static::i()->orm()->where(array('OR'=>array('username'=>$username, 'email'=>$username)))->find_one();
        if (!$user || !$user->validatePassword($password)) {
            BLoginThrottle::i()->failure();
            return false;
        }
        BLoginThrottle::i()->success();
        return $user;
    }

    static public function authenticateApi($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        BLoginThrottle::i()->init('FCom_Admin', $username);
        /** @var FCom_Admin_Model_User */
        $user = static::i()->orm()->where('api_username', $username)->find_one();
        if (!$user || !$user->validatePassword($password, 'api_password_hash')) {
            BLoginThrottle::i()->failure();
            return false;
        }
        BLoginThrottle::i()->success();
        return $user;
    }

    public function login()
    {
        $this->set('last_login', BDb::now())->save();

        BSession::i()->set('admin_user', serialize($this));
        static::$_sessionUser = $this;

        if ($this->get('locale')) {
            setlocale(LC_ALL, $this->get('locale'));
        }
        if ($this->get('timezone')) {
            date_default_timezone_set($this->get('timezone'));
        }
        BEvents::i()->fire('FCom_Admin_Model_User::login:after', array('user' => $this));

        return $this;
    }

    static public function logout()
    {
        BEvents::i()->fire(__METHOD__);
        BSession::i()->set('admin_user', null);
        static::$_sessionUser = null;
    }

    public function recoverPassword()
    {
        $this->set(array('token'=>BUtil::randomString(), 'token_at'=>BDb::now()))->save();
        BLayout::i()->view('email/admin/user-password-recover')->set('user', $this)->email();
        return $this;
    }

    public function resetPassword($password)
    {
        $this->set(array('token'=>null, 'token_at'=>null))->setPassword($password)->save()->login();
        BLayout::i()->view('email/admin/user-password-reset')->set('user', $this)->email();
        return $this;
    }

    public function tzOffset()
    {
        return BLocale::i()->tzOffset($this->get('tz'));
    }

    public function fullname()
    {
        return $this->get('firstname').' '.$this->get('lastname');
    }

    public function thumb($w, $h=null)
    {
        return BUtil::gravatar($this->get('email'));
//        return FCom_Core_Main::i()->resizeUrl().http_build_query(array(
//            'f' => $this->thumb_url,
//        ));
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
            $user = $this->sessionUser();
            if (!$user) {
                return null;
            }
            return $user->personalize($data);
        }
        if (!$this->_persModel) {
            $this->_persModel = FCom_Admin_Model_Personalize::i()->load($this->id(), 'user_id');
            if (!$this->_persModel) {
                $this->_persModel = FCom_Admin_Model_Personalize::i()->create(array('user_id'=>$this->id));
            }
        }
        if (!$this->_persData) {
            $dataJson = $this->_persModel->get('data_json');
            $this->_persData = $dataJson ? BUtil::fromJson($dataJson) : array();
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
        if ($this->get('is_superadmin')) {
            return true;
        }
        if (!$this->get('role_id')) {
            return false;
        }
        if (!$this->get('permissions')) {
            $this->set('permissions', FCom_Admin_Model_Role::i()->load($this->role_id)->get('permissions'));
        }
        if (is_string($paths)) {
            $paths = explode(',', $paths);
        }
        foreach ($paths as $p) {
            $perms = $this->get('permissions');
            if (!empty($perms[$p])) {
                return true;
            }
        }
        return false;
    }
}
