<?php

class FCom_Customer_Model_Customer extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_customer';
    protected static $_origClass = __CLASS__;

    protected static $_fieldOptions = array(
        'status' => array(
            'new'      => 'New',
            'active'   => 'Active',
            'disabled' => 'Disabled',
        ),
    );

    protected static $_sessionUser;
    protected $defaultShipping = null;
    protected $defaultBilling = null;

    private static $lastImportedCustomer = 0;

	protected $_validationRules = array(
		array('email', '@required'),
		array('firstname', '@required'),
		array('lastname', '@required'),
		array('password', '@required'),
		array('password_confirm', '@password_confirm'),
		/*array('payment_method', '@required'),
		array('payment_details', '@required'),*/

		array('email', '@email'),

		array('default_shipping_id', '@integer'),
		array('default_billing_id', '@integer'),
		/*array('customer_group', '@integer'),*/
	);

    /**
     * @param bool  $new
     * @param array $args
     * @return FCom_Customer_Model_Customer
     */
    public static function i($new=false, array $args=array())
    {
        return parent::i($new, $args);
    }

    /**
     * override default rules for login form
     */
    public function setLoginRules()
    {
        $this->_validationRules =  array(
            array('email', '@required'),
            array('password', '@required'),
            array('email', '@email'),
        );
    }

    /**
     * override default rules for password recover form
     */
    public function setPasswordRecoverRules()
    {
        $this->_validationRules =  array(
            array('email', '@required'),
            array('email', '@email'),
        );
    }

    public function setAccountEditRules($incChangePassword = false)
    {
        $this->_validationRules = array(
            array('email', '@required'),
            array('firstname', '@required'),
            array('lastname', '@required'),
        );

        if ($incChangePassword) {
            $this->_validationRules[] = array('password', '@required');
            $this->_validationRules[] = array('password_confirm', '@password_confirm');
        }
    }

    public function setChangePasswordRules()
    {
        $this->_validationRules = array(
            array('password', '@required'),
            array('password_confirm', '@password_confirm'),
        );
    }

    public function setPassword($password)
    {
        $this->password_hash = BUtil::fullSaltedHash($password);
        return $this;
    }

    public function recoverPassword()
    {
        $this->set(array('token'=>BUtil::randomString(20)))->save();
        BLayout::i()->view('email/customer-password-recover')->set('customer', $this)->email();
        return $this;
    }

    public function resetPassword($password)
    {
        $this->set(array('token'=>null))->setPassword($password)->save()->login();
        BLayout::i()->view('email/customer-password-reset')->set('customer', $this)->email();
        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (!$this->create_at) $this->create_at = BDb::now();
        $this->update_at = BDb::now();
        if ($this->password) {
            $this->password_hash = BUtil::fullSaltedHash($this->password);
        }
        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        if (self::sessionUser()) {
            BSession::i()->data('customer_user', serialize($this));
            static::$_sessionUser = $this;
        }
    }

    public function prepareApiData($customers)
    {
        $result = array();
        foreach($customers as $customer) {
            $result[] = array(
                'id'                => $customer->id,
                'email'             => $customer->email,
                'firstname'         => $customer->firstname,
                'lastname'          => $customer->lastname,
                'shipping_address_id'  => $customer->default_shipping_id,
                'billing_address_id'   => $customer->default_billing_id
            );
        }
        return $result;
    }

    public function formatApiPost($post)
    {
        $data = array();

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
        if (!empty($post['billing_address_id'])) {
            $data['billing_address_id'] = $post['billing_address_id'];
        }
        return $data;
    }

    public function as_array(array $objHashes=array())
    {
        $data = parent::as_array();
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
        return !empty($user) ? $user->id() : false;
    }

    static public function isLoggedIn()
    {
        return static::sessionUser() ? true : false;
    }

    static public function authenticate($username, $password)
    {
        BLoginThrottle::i()->init('FCom_Customer_Model_Customer', $username);
        /** @var FCom_Admin_Model_User */
        $user = static::i()->orm()->where('email', $username)->find_one();
        if (!$user || !$user->validatePassword($password)) {
            BLoginThrottle::i()->failure();
            return false;
        }
        BLoginThrottle::i()->success();
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
        BEvents::i()->fire(__METHOD__.'.after', array('user'=>$this));
        return $this;
    }

    static public function logout()
    {
        BEvents::i()->fire(__METHOD__.'.before', array('user'=>  self::sessionUser()));

        BSession::i()->data('customer_user', false);
        static::$_sessionUser = null;
    }

    static public function register($r)
    {
        if (empty($r['email'])
            || empty($r['password']) || empty($r['password_confirm'])
            || $r['password']!=$r['password_confirm']
        ) {
            throw new Exception('Incomplete or invalid form data.');
        }

        unset($r['id']);
        $customer = static::i()->create($r)->save();
        BLayout::i()->view('email/new-customer')->set('customer', $customer)->email();
        BLayout::i()->view('email/new-admin')->set('customer', $customer)->email();
        return $customer;
    }



    public static function import($data)
    {
        BEvents::i()->fire(__METHOD__.'.before', array('data'=>&$data));

        if (!empty($data['customer']['id'])) {
            $cust = static::load($data['customer']['id']);
        }
        $result['status'] = '';
        if (empty($cust)) {
            if (empty($data['customer']['email'])) {
                if (self::$lastImportedCustomer) {
                    $cust = self::$lastImportedCustomer;
                    $result['status'] = 'updated';
                } else {
                    $result = array('status'=>'error', 'message'=>'Missing email address');
                    return $result;
                }
            } else {
                $cust = static::load($data['customer']['email'], 'email');
            }
        }
        if (!$cust) {
            $cust = static::create();
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

        self::$lastImportedCustomer = $cust;

        $result['addr'] = FCom_Customer_Model_Address::i()->import($data, $cust);

        BEvents::i()->fire(__METHOD__.'.after', array('data'=>$data, 'result'=>&$result));

        return $result;
    }

    public function defaultBilling()
    {
        if ($this->default_billing_id && !$this->default_billing) {
            $this->default_billing = FCom_Customer_Model_Address::i()->load($this->default_billing_id);
        }
        return $this->default_billing;
    }

    public function defaultShipping()
    {
        if ($this->default_shipping_id && !$this->default_billing) {
            $this->default_billing = FCom_Customer_Model_Address::i()->load($this->default_shipping_id);
        }
        return $this->default_billing;
    }

    public function addresses()
    {
        return FCom_Customer_Model_Address::i()->orm('a')->where('customer_id', $this->id)->find_many();
    }

    public function getPaymentMethod()
    {
        return self::i()->load($this->id)->payment_method;
    }

    public function getPaymentDetails()
    {
        return self::i()->load($this->id)->payment_details;
    }

    public function setPaymentDetails($data)
    {
        $this->payment_details = Butil::toJson($data);
        $this->save();
    }

    static public function onAddProductToCart($args)
    {
        $cart = $args['model'];

        $user = self::sessionUser();
        if($user){
            $user->session_cart_id = $cart->id();
            $user->save();
        }
    }

    /**
     * rule email unique
     * @param $data
     * @param $args
     * @return bool
     */
    public static function ruleEmailUnique($data, $args)
    {
        if (!isset($data[$args['field']])) {
            return false;
        }
        $model = self::i()->orm()->where('email', $data[$args['field']])->find_one();
        if ($model)
            return false;
        return true;
    }
}
