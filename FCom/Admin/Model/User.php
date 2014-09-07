<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        ['username', '/^[A-Za-z0-9._@-]{1,255}$/', 'Username allowed characters are letters, numbers, dot, underscore, hyphen and @'],
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

    public function statusOptions()
    {
        return [
            static::STATUS_ACTIVE => 'Active',
            static::STATUS_INACTIVE => 'Inactive',
        ];
    }

    public function setPassword($password)
    {
        $token = $this->BUtil->randomString(16);
        $this->set([
            'password_hash' => $this->BUtil->fullSaltedHash($password),
            'password_session_token' => $token,
        ]);
        if ($this->id() === $this->sessionUserId()) {
            $this->BSession->set('admin_user_password_token', $token);
        }
        return $this;
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();

        $this->set([
            'tz' => $this->BConfig->get('modules/FCom_Core/default_tz'),
            'locale' => $this->BConfig->get('modules/FCom_admin/default_locale'),
        ]);
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if ($this->get('password')) {
            $this->setPassword($this->get('password'));
        }
        if ($this->get('api_password')) {
            $this->set('api_password_hash', $this->BUtil->fullSaltedHash($this->get('api_password')));
        }
        if (!$this->get('role_id')) {
            $this->set('role_id', null);
        }
        if (!$this->get('locale')) {
            $this->set('locale', $this->BConfig->get('modules/FCom_Admin/default_locale'));
        }
        if (!$this->get('tz')) {
            $this->set('tz', $this->BConfig->get('modules/FCom_Core/default_tz'));
        }

        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        if ($this->id() === $this->sessionUserId()) {
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

    public function validatePasswordSecurity($data, $args)
    {
        if (!$this->BConfig->get('modules/FCom_Admin/password_strength')) {
            return true;
        }
        $password = $data[$args['field']];
        if (strlen($password) > 0 && !preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*()_+=}{><;:\]\[?]).{7,}/', $password)) {
            return $this->BLocale->_('Password must be at least 7 characters in length and must include at least one letter, one capital letter, one number, and one special character.');
        }
        return true;
    }

    public function validatePassword($password, $field = 'password_hash')
    {
        $hash = $this->get($field);
        if (!$this->BUtil->validateSaltedHash($password, $hash)) {
            return false;
        }
        if (!$this->BUtil->isPreferredPasswordHash($hash)) {
            $this->set('password_hash', $this->BUtil->fullSaltedHash($password))->save();
        }
        return true;
    }

    public function has_role($orm, $role)
    {
        return $orm->where('role', $role);
    }

    public function options()
    {
        $users = $this->orm()
            ->select('id')->select('firstname')->select('lastname')
            ->find_many();
        $options = [];
        foreach ($users as $u) {
            $options[$u->id] = $u->firstname . ' ' . $u->lastname;
        }
        return $options;
    }

    public function sessionUserId()
    {
        return $this->BSession->get('admin_user_id');
    }

    public function sessionUser($reset = false)
    {
        if ($reset || !static::$_sessionUser) {
            $sessData =& $this->BSession->dataToUpdate();
            if (empty($sessData['admin_user_id'])) {
                return false;
            }
            $userId = $sessData['admin_user_id'];
            $user = static::$_sessionUser = $this->load($userId);
            if (!$user) {
                $this->logout();
                return false;
            }
            $token = $user->get('password_session_token');
            if (!$token) {
                $token = $this->BUtil->randomString(16);
                $user->set('password_session_token', $token)->save();
            }
            if (empty($sessData['admin_user_password_token'])) {
                $sessData['admin_user_password_token'] = $token;
            } elseif ($sessData['admin_user_password_token'] !== $token) {
                $user->logout();
                $this->BResponse->cookie('remember_me', 0);
                $this->BResponse->redirect('');
                return false;
            }
        }
        return static::$_sessionUser;
    }

    public function isLoggedIn()
    {
        return $this->sessionUserId() ? true : false;
    }

    public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        if (!$this->BLoginThrottle->init('FCom_Admin_Model_User', $username)) {
            return false;
        }
        /** @var FCom_Admin_Model_User */
        $user = $this->orm()->where(['OR' => [
            'username' => (string)$username,
            'email' => (string)$username,
        ]])->find_one();
        if (!$user || !$user->validatePassword($password)) {
            $this->BLoginThrottle->failure();
            return false;
        }
        $this->BLoginThrottle->success();
        return $user;
    }

    public function authenticateApi($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        if (!$this->BLoginThrottle->init('FCom_ApiServer', $username)) {
            return false;
        }
        /** @var FCom_Admin_Model_User */
        $user = $this->orm()->where('api_username', $username)->find_one();
        if (!$user || !$user->validatePassword($password, 'api_password_hash')) {
            $this->BLoginThrottle->failure();
            return false;
        }
        $this->BLoginThrottle->success();
        return $user;
    }

    public function login()
    {
        //session_regenerate_id(true);

        $this->set('last_login', $this->BDb->now())->save();

        $this->BSession->set('admin_user_id', $this->id());
        static::$_sessionUser = $this;

        if ($this->get('locale')) {
            setlocale(LC_ALL, $this->get('locale'));
        }
        if ($this->get('timezone')) {
            date_default_timezone_set($this->get('timezone'));
        }
        $this->BEvents->fire('FCom_Admin_Model_User::login:after', ['user' => $this]);

        return $this;
    }

    public function logout()
    {
        $this->BEvents->fire(__METHOD__);
        #$this->BSession->set('admin_user_id', null);
        #$this->BSession->set('admin_user_password_token', null);
        $sessData =& $this->BSession->dataToUpdate();
        $sessData = [];

        $this->BSession->regenerateId();

        static::$_sessionUser = null;
    }

    public function recoverPassword()
    {
        $this->set(['token' => $this->BUtil->randomString(), 'token_at' => $this->BDb->now()])->save();
        $this->BLayout->view('email/admin/user-password-recover')->set('user', $this)->email();
        return $this;
    }

    public function validateResetToken($token)
    {
        if (!$token) {
            return false;
        }
        $user = $this->load($token, 'token');
        if (!$user || $user->get('token') !== $token) {
            return false;
        }
        $tokenTtl = $this->BConfig->get('modules/FCom_Admin/password_reset_token_ttl_hr');
        if (!$tokenTtl) {
            $tokenTtl = 24;
        }
        if (strtotime($user->get('token_at')) < time() - $tokenTtl * 3600) {
            $user->set(['token' => null, 'token_at' => null])->save();
            return false;
        }
        return $user;
    }

    public function resetPassword($password)
    {
        $this->set(['token' => null, 'token_at' => null])->setPassword($password)->save();
        $this->BLayout->view('email/admin/user-password-reset')->set('user', $this)->email();
        return $this;
    }

    public function tzOffset()
    {
        return $this->BLocale->tzOffset($this->get('tz'));
    }

    public function fullname()
    {
        return $this->get('firstname') . ' ' . $this->get('lastname');
    }

    public function thumb($w, $h = null)
    {
        return $this->BUtil->gravatar($this->get('email'));
//        return $this->FCom_Core_Main->resizeUrl().http_build_query(array(
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
            $this->_persModel = $this->FCom_Admin_Model_Personalize->load($this->id(), 'user_id');
            if (!$this->_persModel) {
                $this->_persModel = $this->FCom_Admin_Model_Personalize->create(['user_id' => $this->id]);
            }
        }
        if (!$this->_persData) {
            $dataJson = $this->_persModel->get('data_json');
            $this->_persData = $dataJson ? $this->BUtil->fromJson($dataJson) : [];
        }
        if (is_null($data)) {
            return $this->_persData;
        }
        $this->_persData = $this->BUtil->arrayMerge($this->_persData, $data);
        $this->_persModel->set('data_json', $this->BUtil->toJson($this->_persData))->save();
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
            $this->set('permissions', $this->FCom_Admin_Model_Role->load($this->role_id)->get('permissions'));
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
