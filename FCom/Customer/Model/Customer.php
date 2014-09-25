<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Model class for table 'fcom_customer'
 * The followings are the available columns in table 'fcom_customer':
 * @property string $id
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
 * relations
 * @property FCom_Customer_Model_Address $default_billing
 * @property FCom_Customer_Model_Address $default_shipping
 *
 * DI
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 */
class FCom_Customer_Model_Customer extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_customer';
    protected static $_origClass = __CLASS__;

    protected static $_fieldOptions = [
        'status' => [
            'review'   => 'Review',
            'active'   => 'Active',
            'disabled' => 'Disabled',
        ],
    ];

    protected static $_sessionUser;
    protected $defaultShipping = null;
    protected $defaultBilling = null;

    private static $lastImportedCustomer = 0;

    protected static $_validationRules = [
        ['email', '@required'],
        ['firstname', '@required'],
        ['lastname', '@required'],
        //array('password', '@required'),
        ['password_confirm', '@password_confirm'],
        /*array('payment_method', '@required'),
        array('payment_details', '@required'),*/

        ['email', '@email'],

        ['default_shipping_id', '@integer'],
        ['default_billing_id', '@integer'],
        ['password', 'FCom_Customer_Model_Customer::validatePasswordSecurity'],
        /*array('customer_group', '@integer'),*/
    ];
    //todo: set rules password minimum length

    protected $_addresses;

    /**
     * @param bool  $new
     * @param array $args
     * @return FCom_Customer_Model_Customer
     */
    static public function i($new = false, array $args = [])
    {
        return parent::i($new, $args);
    }

    /**
     * override default rules for login form
     */
    public function setLoginRules()
    {
        static::$_validationRules =  [
            ['email', '@required'],
            ['password', '@required'],
            ['email', '@email'],
        ];
    }

    /**
     * override default rules for password recover form
     */
    public function setPasswordRecoverRules()
    {
        static::$_validationRules =  [
            ['email', '@required'],
            ['email', '@email'],
        ];
    }

    public function setAccountEditRules($incChangePassword = false)
    {
        static::$_validationRules = [
            ['email', '@required'],
            ['firstname', '@required'],
            ['lastname', '@required'],
        ];

        if ($incChangePassword) {
            static::$_validationRules[] = ['password', '@required'];
            static::$_validationRules[] = ['password_confirm', '@password_confirm'];
        }
    }

    public function setChangePasswordRules()
    {
        static::$_validationRules = [
            ['current_password', '@required'],
            ['password', '@required'],
            ['password_confirm', '@password_confirm'],
        ];
    }

    public function setSimpleRegisterRules()
    {
        static::$_validationRules = [
            ['email', '@required'],
            ['password', '@required'],
            ['password_confirm', '@password_confirm'],
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

    public function recoverPassword()
    {
        $this->set(['token' => $this->BUtil->randomString(20), 'token_at' => $this->BDb->now()])->save();
        $this->BLayout->view('email/customer-password-recover')->set('customer', $this)->email();
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
        $tokenTtl = $this->BConfig->get('modules/FCom_Customer/password_reset_token_ttl_hr');
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

    public function as_array(array $objHashes = [])
    {
        $data = parent::as_array();
        unset($data['password_hash']);
        return $data;
    }

    public function validatePasswordSecurity($data, $args)
    {
        if (!$this->BConfig->get('modules/FCom_Customer/password_strength')) {
            return true;
        }
        $password = $data[$args['field']];
        if (strlen($password) > 0 && !preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*()_+=}{><;:\]\[?]).{7,}/', $password)) {
            return 'Password must be at least 7 characters in length and must include at least one letter, one capital letter, one number, and one special character.';
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

    public function sessionUserId()
    {
        return $this->BSession->get('customer_id');
    }

    /**
     * @param bool $reset
     * @return bool|FCom_Customer_Model_Customer
     */
    public function sessionUser($reset = false)
    {
        if ($reset || !static::$_sessionUser) {
            $sessData =& $this->BSession->dataToUpdate();
            if (empty($sessData['customer_id'])) {
                return false;
            }
            $userId = $sessData['customer_id'];
            $user = static::$_sessionUser = $this->load($userId);
            $token = $user->get('password_session_token');
            if (!$token) {
                $token = $this->BUtil->randomString(16);
                $user->set('password_session_token', $token)->save();
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
        return static::$_sessionUser;
    }

    public function isLoggedIn()
    {
        return $this->sessionUserId() ? true : false;
    }

    /**
     * @param $username
     * @param $password
     * @return FCom_Customer_Model_Customer|bool
     */
    public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        if (!$this->BLoginThrottle->init('FCom_Customer_Model_Customer', $username)) {
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
     * @return $this
     */
    public function login()
    {
        $this->set('last_login', $this->BDb->now())->save();

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
        if ($this->BConfig->get('modules/FCom_Customer/require_approval')) {
            $r['status'] = 'review';
        } else {
            $r['status'] = 'active';
        }
        $customer = $this->create($r)->save();
        $this->BLayout->view('email/new-customer')->set('customer', $customer)->email();
        $this->BLayout->view('email/new-admin')->set('customer', $customer)->email();
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

        $result['addr'] = $this->FCom_Customer_Model_Address->import($data, $cust);

        $this->BEvents->fire(__METHOD__ . ':after', ['data' => $data, 'result' => &$result]);

        return $result;
    }

    public function getDefaultBillingAddress()
    {
        $addresses = $this->getAddresses();
        foreach ($addresses as $addr) {
            if ($addr->is_default_billing) {
                return $addr;
            }
        }
        return null;
    }

    public function getDefaultShippingAddress()
    {
        $addresses = $this->getAddresses();
        foreach ($addresses as $addr) {
            if ($addr->is_default_shipping) {
                return $addr;
            }
        }
        return null;
    }

    public function getAddresses($reset = false)
    {
        if ($reset || !$this->_addresses) {
            $this->_addresses = $this->FCom_Customer_Model_Address->orm('a')->where('customer_id', $this->id)->find_many();
        }
        return $this->_addresses;
    }

    public function setDefaultAddress($address, $atype = null)
    {
        $addrHlp = $this->FCom_Customer_Model_Address;
        if (is_object($address)) {
            $addressId = $address->id();
        } elseif (is_numeric($address)) {
            $addressId = $address;
            $address = $addrHlp->load($addressId);
        }
        if ($atype === 'billing' || $atype === true) {
            $address->set('is_default_billing', 1);
        }
        if ($atype === 'shipping' || $atype === true) {
            $address->set('is_default_shipping', 1);
        }
        $resetUpdate = [];
        if ($address->is_default_billing) {
            $resetUpdate['is_default_billing'] = 0;
            $this->set('default_billing_id', $addressId);
        }
        if ($address->is_default_shipping) {
            $resetUpdate['is_default_shipping'] = 0;
            $this->set('default_shipping_id', $addressId);
        }
        if ($resetUpdate) {
            $addrHlp->update_many($resetUpdate, ['customer_id' => $this->id(), 'NOT' => ['id' => $addressId]]);
        }
        $this->save();
        $address->save();

        return $this;
    }

    public function addAddress($data)
    {
        $data['customer_id'] = $this->id();
        $address = $this->FCom_Customer_Model_Address->create($data);
        $this->setDefaultAddress($address);
        return $address;
    }

    public function getPaymentMethod()
    {
        return $this->load($this->id)->payment_method;
    }

    public function getPaymentDetails()
    {
        return $this->load($this->id)->payment_details;
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
        if ($this->BModuleRegistry->isLoaded('FCom_Sales')) {
            $orders = $this->FCom_Sales_Model_Order->orm()->where('customer_id', $this->id)->find_many();
            if ($orders) {
                $cntOrders = count($orders);
                foreach ($orders as $order) {
                    $statistics['lifetime'] += $order->grandtotal;
                }
                $statistics['avg'] = $statistics['lifetime'] / $cntOrders;
            }
        }
        return $statistics;
    }
}
