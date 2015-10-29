<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_Model_User
 *
 * @property int $id
 * @property int $superior_id
 * @property string $username
 * @property int $is_superadmin
 * @property int $role_id
 * @property string $email
 * @property string $password_hash
 * @property string $firstname
 * @property string $lastname
 * @property string $phone
 * @property string $phone_ext
 * @property string $fax
 * @property int $status
 * @property string $tz
 * @property string $locale
 * @property string $create_at
 * @property string $update_at
 * @property string $token
 * @property string $token_at
 * @property string $api_username
 * @property string $api_password
 * @property string $api_password_hash
 * @property string $data_serialized
 * @property string $password_session_token
 * @property FCom_Admin_Model_Personalize $FCom_Admin_Model_Personalize
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property FCom_Core_Main $FCom_Core_Main
 */
class FCom_Admin_Model_User extends FCom_Core_Model_Abstract
{
    const
        STATUS_ACTIVE   = 'A',
        STATUS_INACTIVE = 'I',
        STATUS_DELETED  = 'D'
    ;

    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_admin_user';

    /** @var  FCom_Admin_Model_User $_sessionUser */
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

    protected static $_fieldDefaults = [
        'locale' => 'en_US',
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

    protected static $_importExportProfile = [
        'skip'       => ['id', 'create_at', 'update_at'],
        'unique_key' => ['username'],
        'related'    => [
            'superior_id' => 'FCom_Admin_Model_User.id',
            'role_id'     => 'FCom_Admin_Model_Role.id'
        ],
    ];

    protected $_persModel;
    protected $_persData;

    protected $_permissions;

    /**
     * @return array
     */
    public function statusOptions()
    {
        return [
            static::STATUS_ACTIVE => 'Active',
            static::STATUS_INACTIVE => 'Inactive',
        ];
    }

    /**
     * @param $password
     * @return FCom_Admin_Model_User
     * @throws BException
     */
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

        $defaultTz = $this->BConfig->get('modules/FCom_Core/default_tz');
        if ($defaultTz) {
            $this->set('tz', $defaultTz);
        }
        $defaultLocale = $this->BConfig->get('modules/FCom_admin/default_locale');
        if ($defaultLocale) {
            $this->set('locale', $defaultLocale);
        }
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

    /**
     * @param array $objHashes
     * @return array
     */
    public function as_array(array $objHashes = [])
    {
        $data = parent::as_array();
        #unset($data['password_hash']);
        #unset($data['api_password_hash']);
        return $data;
    }

    /**
     * validate password strength
     * @param $data
     * @param $args
     * @return bool|false|string
     */
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

    /**
     * validate password
     * @param string $password
     * @param string $field
     * @return bool
     * @throws BException
     */
    public function validatePassword($password, $field = 'password_hash')
    {
        $hash = $this->get($field);
        if ($password[0] !== '$' && $password === $hash) {
            // direct sql access for account recovery
        } elseif (!$this->BUtil->validateSaltedHash($password, $hash)) {
            return false;
        }
        if (!$this->BUtil->isPreferredPasswordHash($hash)) {
            $this->set('password_hash', $this->BUtil->fullSaltedHash($password))->save();
        }
        return true;
    }

    /**
     * @return array
     */
    public function options()
    {
        /** @var FCom_Admin_Model_User[] $users */
        $users = $this->orm()
            ->select('id')->select('firstname')->select('lastname')
            ->find_many();
        $options = [];
        foreach ($users as $u) {
            $options[$u->id] = $u->firstname . ' ' . $u->lastname;
        }
        return $options;
    }

    /**
     * @return int user_id
     */
    public function sessionUserId()
    {
        return $this->BSession->get('admin_user_id');
    }

    /**
     * @param bool $reset
     * @return bool|FCom_Admin_Model_User
     * @throws BException
     */
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

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->sessionUserId() ? true : false;
    }

    /**
     * @param string $username
     * @param string $password
     * @return FCom_Admin_Model_User|bool
     */
    public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        if (!$this->BLoginThrottle->init('FCom_Admin_Model_User', $username)) {
            return false;
        }
        /** @var FCom_Admin_Model_User $user */
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

    /**
     * @param $username
     * @param $password
     * @return bool|FCom_Admin_Model_User
     */
    public function authenticateApi($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        if (!$this->BLoginThrottle->init('FCom_ApiServer', $username)) {
            return false;
        }
        /** @var FCom_Admin_Model_User $user */
        $user = $this->orm()->where('api_username', $username)->find_one();
        if (!$user || !$user->validatePassword($password, 'api_password_hash')) {
            $this->BLoginThrottle->failure();
            return false;
        }
        $this->BLoginThrottle->success();
        return $user;
    }

    /**
     * @return FCom_Admin_Model_User
     * @throws BException
     */
    public function login()
    {
        //session_regenerate_id(true);

        $this->set('last_login', $this->BDb->now())->save();

        $this->BSession->regenerateId();
        $this->BSession->set('admin_user_id', $this->id());
        static::$_sessionUser = $this;

        if ($this->get('locale')) {
            setlocale(LC_ALL, $this->get('locale'));
        }
        if ($this->get('timezone')) {
            date_default_timezone_set($this->get('timezone'));
            $this->BSession->set('_timezone', $this->get('timezone'));
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

    /**
     * @return FCom_Admin_Model_User
     * @throws BException
     */
    public function recoverPassword()
    {
        $this->set(['token' => $this->BUtil->randomString(), 'token_at' => $this->BDb->now()])->save();
        $this->BLayout->view('email/admin/user-password-recover')->set('user', $this)->email();
        return $this;
    }

    /**
     * @param $token
     * @return FCom_Admin_Model_User|bool
     * @throws BException
     */
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

    /**
     * @param $password
     * @return FCom_Admin_Model_User
     * @throws BException
     */
    public function resetPassword($password)
    {
        $this->BSession->regenerateId();
        $this->set(['token' => null, 'token_at' => null])->setPassword($password)->save();
        $this->BLayout->view('email/admin/user-password-reset')->set('user', $this)->email();
        return $this;
    }

    /**
     * @return int
     */
    public function tzOffset()
    {
        return $this->BLocale->tzOffset($this->get('tz'));
    }

    /**
     * @return string
     */
    public function fullname()
    {
        return $this->get('firstname') . ' ' . $this->get('lastname');
    }

    /**
     * @param $w
     * @param null $h
     * @return string
     */
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

    /**
     * @param $paths
     * @return bool
     * @throws BException
     */
    public function getPermission($paths)
    {
        if ($this->get('is_superadmin')) {
            return true;
        }
        $perms = $this->get('permissions');
        if (!$perms) {
            $roleIds = [];
            if ($this->get('role_id')) {
                $roleIds[] = $this->get('role_id');
            }
            $this->BEvents->fire(__METHOD__ . ':roles', ['role_ids' => &$roleIds, 'user' => $this, 'paths' => $paths]);
            if (!$roleIds) {
                return false;
            }
            $roles = $this->FCom_Admin_Model_Role->orm()->where_in('id', $roleIds)->find_many();
            $perms = [];
            foreach ($roles as $role) {
                /* @var FCom_Admin_Model_Role $role */
                $permissions = $role->onAfterLoad()->get('permissions');
                $perms = array_merge($perms, $permissions);
            }

            $this->set('permissions', $perms);
        }
        if (is_string($paths)) {
            $paths = explode(',', $paths);
        }
        foreach ($paths as $p) {
            if (array_key_exists($p, $perms)) {
                return true;
            }
        }
        return false;
    }
}
