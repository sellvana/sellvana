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

    protected static $_fieldOptions = [
        'status' => [
            'A' => 'Active',
            'I' => 'Inactive',
        ],
        'is_superadmin' => [
            '0' => 'No',
            '1' => 'Yes',
        ],
    ];

    protected static $_validationRules = [
        ['username', '@required'],
        ['username', '/^[A-Za-z0-9._@-]+$/', 'Username allowed characters are letters, numbers, dot, underscore, hiphen and @'],
        ['email', '@required'],
        ['email', '@email'],
        ['password', 'FCom_Admin_Model_User::validatePasswordSecurity'],

        //array('is_superadmin', '@integer'),
        ['role_id', '@integer'],
        //array('superior_id', '@integer'),
    ];

    protected $_persModel;
    protected $_persData;

    protected $_permissions;

    public static function statusOptions()
    {
        return [
            static::STATUS_ACTIVE => 'Active',
            static::STATUS_INACTIVE => 'Inactive',
        ];
    }

    public function setPassword($password)
    {
        $token = BUtil::randomString(16);
        $this->set([
            'password_hash' => BUtil::fullSaltedHash($password),
            'password_session_token' => $token,
        ]);
        if ($this->id() === static::sessionUserId()) {
            BSession::i()->set('admin_user_password_token', $token);
        }
        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if ($this->get('password')) {
            $this->setPassword($this->get('password'));
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

        if ($this->id() === static::sessionUserId()) {
            static::$_sessionUser = $this;
        }
    }

    public function as_array(array $objHashes = [])
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
        if (strlen($password) > 0 && !preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*()_+=}{><;:\]\[?]).{7,}/', $password)) {
            return BLocale::_('Password must be at least 7 characters in length and must include at least one letter, one capital letter, one number, and one special character.');
        }
        return true;
    }

    public function validatePassword($password, $field = 'password_hash')
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
        $options = [];
        foreach ($users as $u) {
            $options[$u->id] = $u->firstname . ' ' . $u->lastname;
        }
        return $options;
    }

    static public function sessionUserId()
    {
        return BSession::i()->get('admin_user_id');
    }

    static public function sessionUser($reset = false)
    {
        if ($reset || !static::$_sessionUser) {
            $sessData =& BSession::i()->dataToUpdate();
            if (empty($sessData['admin_user_id'])) {
                return false;
            }
            $userId = $sessData['admin_user_id'];
            $user = static::$_sessionUser = static::load($userId);
            $token = $user->get('password_session_token');
            if (!$token) {
                $token = BUtil::randomString(16);
                $user->set('password_session_token', $token)->save();
            }
            if (empty($sessData['admin_user_password_token'])) {
                $sessData['admin_user_password_token'] = $token;
            } elseif ($sessData['admin_user_password_token'] !== $token) {
                $user->logout();
                BResponse::i()->cookie('remember_me', 0);
                BResponse::i()->redirect('');
                return;
            }
        }
        return static::$_sessionUser;
    }

    static public function isLoggedIn()
    {
        return static::sessionUserId() ? true : false;
    }

    static public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        if (!BLoginThrottle::i()->init('FCom_Admin_Model_User', $username)) {
            return false;
        }
        /** @var FCom_Admin_Model_User */
        $user = static::i()->orm()->where(['OR' => ['username' => $username, 'email' => $username]])->find_one();
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

        BSession::i()->set('admin_user_id', $this->id());
        static::$_sessionUser = $this;

        if ($this->get('locale')) {
            setlocale(LC_ALL, $this->get('locale'));
        }
        if ($this->get('timezone')) {
            date_default_timezone_set($this->get('timezone'));
        }
        BEvents::i()->fire('FCom_Admin_Model_User::login:after', ['user' => $this]);

        return $this;
    }

    static public function logout()
    {
        BEvents::i()->fire(__METHOD__, ['user' => static::sessionUser()]);
        #BSession::i()->set('admin_user_id', null);
        #BSession::i()->set('admin_user_password_token', null);
        $sessData =& BSession::i()->dataToUpdate();
        $sessData = [];
        static::$_sessionUser = null;
    }

    public function recoverPassword()
    {
        $this->set(['token' => BUtil::randomString(), 'token_at' => BDb::now()])->save();
        BLayout::i()->view('email/admin/user-password-recover')->set('user', $this)->email();
        return $this;
    }

    public function resetPassword($password)
    {
        $this->set(['token' => null, 'token_at' => null])->setPassword($password)->save();
        BLayout::i()->view('email/admin/user-password-reset')->set('user', $this)->email();
        return $this;
    }

    public function tzOffset()
    {
        return BLocale::i()->tzOffset($this->get('tz'));
    }

    public function fullname()
    {
        return $this->get('firstname') . ' ' . $this->get('lastname');
    }

    public function thumb($w, $h = null)
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
    public function personalize($data = null)
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
                $this->_persModel = FCom_Admin_Model_Personalize::i()->create(['user_id' => $this->id]);
            }
        }
        if (!$this->_persData) {
            $dataJson = $this->_persModel->get('data_json');
            $this->_persData = $dataJson ? BUtil::fromJson($dataJson) : [];
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
