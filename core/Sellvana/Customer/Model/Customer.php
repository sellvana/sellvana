<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Model class for table 'fcom_customer'
 * The followings are the available columns in table 'fcom_customer':
 *
*@property string $id
 * @property string $email
 * @property string $firstname
 * @property string $lastname
 * @property string $password_hash
 * @property string $default_shipping_id
 * @property string $default_billing_id
 * @property string $create_at
 * @property string $update_at
 * @property string $last_login
 * @property string $token
 * @property string $payment_method
 * @property string $payment_details
 * @property string $customer_group
 * @property string $status
 *
 * other
 * @property $session_cart_id
 *
 * relations
 * @property Sellvana_Customer_Model_Address $default_billing
 * @property Sellvana_Customer_Model_Address $default_shipping
 *
 * DI
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 * @property Sellvana_Customer_Model_Address $Sellvana_Customer_Model_Address
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_CustomerGroups_Model_Group $Sellvana_CustomerGroups_Model_Group
*/
class Sellvana_Customer_Model_Customer extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_customer';
    protected static $_origClass = __CLASS__;

    protected static $_fieldOptions = [
        'status' => [
            'new'      => 'New',
            'review'   => 'Review',
            'active'   => 'Active',
            'disabled' => 'Disabled',
        ],
    ];
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['email'],
        'related'    => [
            'customer_group'      => 'Sellvana_CustomerGroups_Model_Group.id',
            'default_shipping_id' => 'Sellvana_Customer_Model_Address.id',
            'default_billing_id'  => 'Sellvana_Customer_Model_Address.id'
        ],
    ];

    protected static $_fieldDefaults = [
        'status' => 'new',
    ];

    protected static $_sessionUser;
    protected $defaultShipping = null;
    protected $defaultBilling = null;

    private static $lastImportedCustomer = 0;

    protected static $_validationRules = [
        ['email', '@required'],
        ['email', 'Sellvana_Customer_Model_Customer::ruleEmailUnique', 'An account with this email address already exists'],
        ['firstname', '@required'],
        ['lastname', '@required'],
        //array('password', '@required'),
        ['password_confirm', '@password_confirm'],
        /*array('payment_method', '@required'),
        array('payment_details', '@required'),*/

        ['email', '@email'],

        ['default_shipping_id', '@integer'],
        ['default_billing_id', '@integer'],
        ['password', 'Sellvana_Customer_Model_Customer::validatePasswordSecurity'],
        /*array('customer_group', '@integer'),*/
    ];
    //todo: set rules password minimum length

    protected $_addresses;

    /**
     * override default rules for login form
     */
    public function getLoginRules()
    {
        return [
            ['email', '@required'],
            ['password', '@required'],
            ['email', '@email'],
        ];
    }

    /**
     * override default rules for password recover form
     */
    public function getPasswordRecoverRules()
    {
        return [
            ['email', '@required'],
            ['email', '@email'],
        ];
    }

    public function getAccountEditRules($incChangePassword = false)
    {
        $rules = [
            ['email', '@required'],
            ['firstname', '@required'],
            ['lastname', '@required'],
        ];

        if ($incChangePassword) {
            $rules[] = ['password', '@required'];
            $rules[] = ['password_confirm', '@password_confirm'];
        }

        return $rules;
    }

    public function getChangePasswordRules()
    {
        return [
            ['current_password', '@required'],
            ['password', '@required'],
            ['password_confirm', '@password_confirm'],
        ];
    }

    public function getSimpleRegisterRules()
    {
        return [
            ['email', '@required'],
            ['password', '@required'],
            ['password_confirm', '@password_confirm'],
        ];
    }

    /**
     * @param $password
     * @return $this
     * @throws BException
     */
    public function setPassword($password)
    {
        $token = $this->BUtil->randomString(16);
        $this->set([
            'password_hash' => $this->BUtil->fullSaltedHash($password),
            'password_session_token' => $token,
        ]);
        if ($this->id() === $this->sessionUserId() && !$this->isOnBackend()) {
            $this->BSession->set('admin_user_password_token', $token);
        }
        return $this;
    }

    /**
     * @return $this
     * @throws BException
     */
    public function recoverPassword()
    {
        $this->set(['token' => $this->BUtil->randomString(20), 'token_at' => $this->BDb->now()])->save();
        $this->BLayout->view('email/customer-password-recover')->set('customer', $this)->email();
        return $this;
    }

    protected function isOnBackend() {
        return strpos($_SERVER['SERVER_NAME'],'admin') === false;
    }

    /**
     * @param $token
     * @return $this|bool
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
        $tokenTtl = $this->BConfig->get('modules/Sellvana_Customer/password_reset_token_ttl_hr');
        if (!$tokenTtl) {
            $tokenTtl = 24;
        }
        if (strtotime($user->get('token_at')) < time() - $tokenTtl * 3600) {
            $user->set(['token' => null, 'token_at' => null])->save();
            return false;
        }
        return $user;
    }

    public function validateCustomerStatus()
    {
        $result = ['allow_login' => false];
        $locale = $this->BLocale;
        switch ($this->get('status')) {
            case 'active':
                $result['allow_login'] = true;
                break;
            case 'review':
                $result['error']['message'] = $locale->_('Your account is under review. Once approved, we\'ll notify you. Thank you for your patience.');
                break;
            case 'disabled':
                $result['error']['message'] = $locale->_('Your account is disabled. Please contact us for more details.');
                break;
            default:
                $result['error']['message'] = $locale->_('Your account status has a problem. Please contact us for more details.');
                break;
        }
        return $result;
    }

    /**
     * @param $password
     * @return $this
     * @throws BException
     */
    public function resetPassword($password)
    {
        $this->BSession->regenerateId();
        $this->set(['token' => null, 'token_at' => null])->setPassword($password)->save();
        $this->BLayout->view('email/customer-password-reset')->set('customer', $this)->email();
        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if ($this->get('password')) {
            $this->setPassword($this->get('password'));
        }

        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        if ($this->sessionUserId() === $this->id()) {
            $this->BSession->set('customer_user', serialize($this));
            static::$_sessionUser = $this;
        }
    }

    /**
     * @param $customers
     * @return array
     */
    public function prepareApiData($customers)
    {
        $result = [];
        foreach ($customers as $customer) {
            $result[] = [
                'id'                => $customer->id,
                'email'             => $customer->email,
                'firstname'         => $customer->firstname,
                'lastname'          => $customer->lastname,
                'shipping_address_id'  => $customer->default_shipping_id,
                'billing_address_id'   => $customer->default_billing_id
            ];
        }
        return $result;
    }

    /**
     * @param $post
     * @return array
     */
    public function formatApiPost($post)
    {
        $data = [];

        if (!empty($post['email'])) {
            $data['email'] = $post['email'];
        }
        if (!empty($post['password'])) {
            $data['password'] = $post['password'];
        }
        if (!empty($post['firstname'])) {
            $data['firstname'] = $post['firstname'];
        }
        if (!empty($post['lastname'])) {
            $data['lastname'] = $post['lastname'];
        }
        if (!empty($post['shipping_address'])) {
            $data['shipping_address_id'] = $post['shipping_address'];
        }
        if (!empty($post['billing_address'])) {
            $data['billing_address_id'] = $post['billing_address'];
        }
        return $data;
    }

    /**
     * @param array $objHashes
     * @return array
     */
    public function as_array(array $objHashes = [])
    {
        $data = parent::as_array();
        unset($data['password_hash']);
        return $data;
    }

    /**
     * @param $data
     * @param $args
     * @return bool|string
     */
    public function validatePasswordSecurity($data, $args)
    {
        if (!$this->BConfig->get('modules/Sellvana_Customer/password_strength')) {
            return true;
        }
        $password = $data[$args['field']];
        if (strlen($password) > 0 && !preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*()_+=}{><;:\]\[?]).{7,}/', $password)) {
            return 'Password must be at least 7 characters in length and must include at least one letter, one capital letter, one number, and one special character.';
        }
        return true;
    }

    /**
     * @param $password
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
     * @return int
     */
    public function sessionUserId()
    {
        $sess = $this->BSession;
        //return $sess->get('admin_customer_id') ?: $sess->get('customer_id');
        return $sess->get('customer_id');
    }

    /**
     * @param bool $reset
     * @return bool|Sellvana_Customer_Model_Customer
     */
    public function sessionUser($reset = false)
    {
        if ($reset || !static::$_sessionUser) {
            $userId = $this->sessionUserId();
            if (!$userId) {
                return false;
            }
            $sessData =& $this->BSession->dataToUpdate();
            $user = static::$_sessionUser = $this->load($userId);
            if (!$user) {
                $sessData['customer_id'] = null;
                return false;
            }
            if (!$this->BSession->get('admin_user_id')) {
                $save = false;
                $sessId = $this->BSession->sessionId();
                if ($user->get('last_session_id') !== $sessId) {
                    $user->set('last_session_id', $sessId);
                    $save = true;
                }
                $token = $user->get('password_session_token');
                if (!$token) {
                    $token = $this->BUtil->randomString(16);
                    $user->set('password_session_token', $token);
                    $save = true;
                }
                if ($save) {
                    $user->save();
                }
                if (empty($sessData['customer_password_token'])) {
                    $sessData['customer_password_token'] = $token;
                } elseif ($sessData['customer_password_token'] !== $token) {
                    $user->logout();
                    $this->BResponse->cookie('remember_me', 0);
                    $this->BResponse->redirect('');
                    return;
                }
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
     * @param $username
     * @param $password
     * @return Sellvana_Customer_Model_Customer|bool
     */
    public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        if (!$this->BLoginThrottle->init('Sellvana_Customer_Model_Customer', $username)) {
            return false;
        }
        /** @var FCom_Admin_Model_User $user */
        $user = $this->orm()->where('email', $username)->find_one();
        if (!$user || !$user->validatePassword($password)) {
            $this->BLoginThrottle->failure();
            return false;
        }
        $this->BLoginThrottle->success();

        return $user;
    }

    /**
     * @param boolean $resetSessionId
     *
     * @return $this
     */
    public function login($resetSessionId = true)
    {
        $this->set('last_login', $this->BDb->now())->save();

        if ($resetSessionId) {
            $this->BSession->regenerateId();
        }
        $this->BSession->set('customer_id', $this->id());
        static::$_sessionUser = $this;
        if ($this->locale) {
            setlocale(LC_ALL, $this->locale);
        }
        if ($this->timezone) {
            date_default_timezone_set($this->timezone);
        }
        $this->BEvents->fire(__METHOD__ . ':after', ['user' => $this]);
        return $this;
    }

    public function logout()
    {
        $this->BEvents->fire(__METHOD__ . ':before', ['user' => $this->sessionUser()]);

        $sessData =& $this->BSession->dataToUpdate();
        $sessData = [];
        static::$_sessionUser = null;

        $this->BSession->regenerateId();
    }

    /**
     * @param array $r post data
     * @return static
     * @throws Exception
     */
    public function register($r)
    {
        if (empty($r['email'])
            || empty($r['password']) || empty($r['password_confirm'])
            || $r['password'] != $r['password_confirm']
        ) {
            throw new Exception('Incomplete or invalid form data.');
        }

        unset($r['id']);
        if ($this->BConfig->get('modules/Sellvana_Customer/require_approval')) {
            $r['status'] = 'review';
        } else {
            $r['status'] = 'active';
        }
        $customer = $this->create($r)->save();
        $this->BLayout->view('email/new-customer')->set('customer', $customer)->email();
        $this->BLayout->view('email/new-customer-admin')->set('customer', $customer)->email();
        return $customer;
    }

    /**
     * @param array $data
     * @return array
     */
    public function import($data)
    {
        $this->BEvents->fire(__METHOD__ . ':before', ['data' => &$data]);

        if (!empty($data['customer']['id'])) {
            $cust = $this->load($data['customer']['id']);
        }
        $result['status'] = '';
        if (empty($cust)) {
            if (empty($data['customer']['email'])) {
                if (static::$lastImportedCustomer) {
                    $cust = static::$lastImportedCustomer;
                    $result['status'] = 'updated';
                } else {
                    $result = ['status' => 'error', 'message' => 'Missing email address'];
                    return $result;
                }
            } else {
                $cust = $this->load($data['customer']['email'], 'email');
            }
        }
        if (!$cust) {
            $cust = $this->create();
            $result['status'] = 'created';
        }
        $result['model'] = $cust;
        if (!empty($data['customer']['email'])) {
            $cust->set($data['customer']);
            if ($cust->is_dirty()) {
                if (!$result['status']) $result['status'] = 'updated';
                $cust->save();
            }
        }

        static::$lastImportedCustomer = $cust;

        $result['addr'] = $this->Sellvana_Customer_Model_Address->import($data, $cust);

        $this->BEvents->fire(__METHOD__ . ':after', ['data' => $data, 'result' => &$result]);

        return $result;
    }

    /**
     * @return null|Sellvana_Customer_Model_Address
     */
    public function getDefaultBillingAddress()
    {
        $addresses = $this->getAddresses();
        foreach ($addresses as $addr) {
            if ($this->default_billing_id === $addr->id()) {
                return $addr;
            }
        }
        return null;
    }

    /**
     * @return null|Sellvana_Customer_Model_Address
     */
    public function getDefaultShippingAddress()
    {
        $addresses = $this->getAddresses();
        foreach ($addresses as $addr) {
            if ($this->default_shipping_id === $addr->id()) {
                return $addr;
            }
        }
        return null;
    }

    /**
     * @param bool $reset
     * @return mixed
     */
    public function getAddresses($reset = false)
    {
        if ($reset || !$this->_addresses) {
            $this->_addresses = $this->Sellvana_Customer_Model_Address->orm('a')->where('customer_id', $this->id)->find_many();
        }
        return $this->_addresses;
    }

    public function setDefaultAddress($address, $atype = null)
    {
        $addrHlp = $this->Sellvana_Customer_Model_Address;
        if (is_numeric($address)) {
            $address = $addrHlp->load($address);
        }
        /** @var Sellvana_Customer_Model_Address $address */
        if ($atype === 'billing' || $atype === true) {
            $address->set('is_default_billing', 1);
        }
        if ($atype === 'shipping' || $atype === true) {
            $address->set('is_default_shipping', 1);
        }
        $resetUpdate = [];
        if ($address->get('is_default_billing')) {
            $resetUpdate['is_default_billing'] = 0;
            $this->set('default_billing_id', $address->id());
        }
        if ($address->get('is_default_shipping')) {
            $resetUpdate['is_default_shipping'] = 0;
            $this->set('default_shipping_id', $address->id());
        }
        if ($resetUpdate) {
            $addrHlp->update_many($resetUpdate, ['customer_id' => $this->id(), 'NOT' => ['id' => $address->id()]]);
        }
        $this->save();
        $address->save();

        return $this;
    }

    public function addAddress($data, $atype = null)
    {
        $data['customer_id'] = $this->id();
        $address = $this->Sellvana_Customer_Model_Address->create($data)->save();
        $this->setDefaultAddress($address, $atype);
        return $address;
    }

    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    public function getPaymentDetails()
    {
        return $this->payment_details;
    }

    public function setPaymentDetails($data)
    {
        $this->payment_details = $this->BUtil->toJson($data);
        $this->save();
    }

    public function onAddProductToCart($args)
    {
        $cart = $args['model'];

        $user = $this->sessionUser();
        if ($user) {
            $user->session_cart_id = $cart->id();
            $user->save();
        }
    }

    /**
     * get options data to create options html in select
     * @param $labelIncId
     * @return array
     */
    public function getOptionsData($labelIncId = false)
    {
        /** @var Sellvana_Customer_Model_Customer[] $results */
        $results = $this->orm('p')->find_many();
        $data = [];
        if (count($results)) {
            foreach ($results as $r) {
                $fullname = $r->firstname . ' ' . $r->lastname;
                $data[$r->id] = $labelIncId ? $r->id . ' - ' . $fullname : $fullname;
            }
        }
        return $data;
    }

    /**
     * rule email unique
     * @param $data
     * @param $args
     * @return bool
     */
    public function ruleEmailUnique($data, $args)
    {
        if (empty($data[$args['field']])) {
            return true;
        }
        $orm = $this->orm()->where('email', $data[$args['field']]);
        if (!empty($data['id'])) {
            $orm->where_not_equal('id', $data['id']);
        }
        if ($orm->find_one()) {
            return false;
        }
        return true;
    }

    /**
     * calc sales statistics
     * @return array
     */
    public function saleStatistics()
    {
        $statistics = [
            'lifetime' => 0,
            'avg'      => 0,
        ];
        if ($this->BModuleRegistry->isLoaded('Sellvana_Sales')) {
            $orders = $this->Sellvana_Sales_Model_Order->orm()->where('customer_id', $this->id)->find_many();
            if ($orders) {
                $cntOrders = count($orders);
                foreach ($orders as $order) {
                    $statistics['lifetime'] += $order->grand_total;
                }
                $statistics['avg'] = $statistics['lifetime'] / $cntOrders;
            }
        }
        return $statistics;
    }

    public function getCustomerGroupId()
    {
        $group = $this->get('customer_group');
        if(!$group && $this->BModuleRegistry->isLoaded('Sellvana_CustomerGroups')) {
            $group = $this->Sellvana_CustomerGroups_Model_Group->notLoggedInId();
            $this->set('customer_group', $group);
        }
        return $group;
    }
}
