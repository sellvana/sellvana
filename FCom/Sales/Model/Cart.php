<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * model class for table "fcom_sales_cart"
 *
 * The followings are the available columns in table 'fcom_sales_cart':
 * @property string $id
 * @property string $item_qty
 * @property integer $item_num
 * @property string $subtotal
 * @property string $tax_amount
 * @property string $discount_amount
 * @property string $grand_total
 * @property string $session_id
 * @property string $customer_id
 * @property string $customer_email
 * @property string $shipping_method
 * @property string $shipping_price
 * @property string $shipping_service
 * @property string $payment_method
 * @property string $payment_details
 * @property string $coupon_code
 * @property string $status
 * @property string $create_at
 * @property string $update_at
 * @property string $data_serialized
 * @property string $last_calc_at
 * @property string $admin_id
 *
 * other property
 * @property int $same_address flag to know shipping is same as billing
 * @property array $data from json_decode data_serialized
 *
 * DI
 * @property FCom_Sales_Model_Cart_Item $FCom_Sales_Model_Cart_Item
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Sales_Model_Cart_Address $FCom_Sales_Model_Cart_Address
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 */
class FCom_Sales_Model_Cart extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_cart';
    protected static $_origClass = __CLASS__;

    protected static $_sessionCart;
    protected static $_totalRowHandlers = [];

    protected static $_fieldOptions = [
        'status' => [
            'new'     => 'New',
            'ordered' => 'Ordered',
        ],
    ];

    protected $_addresses;
    public $items;
    public $totals;

    /**
     * @param null $id
     * @return int
     */
    public function sessionCartId($id = null)
    {
        return $this->sessionCart()->id();
    }

    /**
     * @param bool $createAnonymousIfNeeded
     * @param bool|FCom_Sales_Model_Cart $reset
     * @return FCom_Sales_Model_Cart
     */
    public function sessionCart($createAnonymousIfNeeded = false, $reset = false)
    {
        if (!static::$_sessionCart || $reset) {
            if ($reset instanceof FCom_Sales_Model_Cart) {
                static::$_sessionCart = $reset;
            }
            $customer = $this->FCom_Customer_Model_Customer->sessionUser();
            //fix bug when guests login and then checkout
            if ($customer && !$this->BRequest->cookie('cart')) {
                $cart = $this->loadOrCreate(['customer_id' => $customer->id(), "status" => "new"]);
            } else {
                $cookieToken = $this->BRequest->cookie('cart');
                if ($cookieToken) {
                    $cart = $this->loadWhere(['cookie_token' => (string)$cookieToken, 'status' => 'new']);
                    if (!$cart && !$createAnonymousIfNeeded) {
                        $this->BResponse->cookie('cart', false);
                        return false;
                    }
                }
                if (empty($cart)) {
                    if ($createAnonymousIfNeeded) {
                        $cookieToken = $this->BUtil->randomString(32);
                        $cart = $this->create(['cookie_token' => (string)$cookieToken, 'status' => 'new'])->save();
                        $ttl = $this->BConfig->get('modules/FCom_Sales/cart_cookie_token_ttl_days') * 86400;
                        $this->BResponse->cookie('cart', $cookieToken, $ttl);
                    } else {
                        return false;
                    }
                }
            }

            static::$_sessionCart = $cart;
        }
        return static::$_sessionCart;
    }

    /**
     * @return FCom_Sales_Model_Cart
     */
    public function resetSessionCart()
    {
        static::$_sessionCart = null;
        return $this;
    }

    /**
     * @throws BException
     */
    public function onUserLogin()
    {
        // load just logged in customer
        $customer = $this->FCom_Customer_Model_Customer->sessionUser();
        // something wrong, abort abort!
        if (!$customer) {
            return;
        }
        // get session cart id
        $sessCart = $this->sessionCart();
        // try to load customer cart which is new (not abandoned or converted to order)
        $custCart = $this->FCom_Sales_Model_Cart->loadWhere(['customer_id' => $customer->id(), 'status' => 'new']);

        if ($sessCart && $custCart && $sessCart->id() !== $custCart->id()) {

            // if both current session cart and customer cart exist and they're different carts
            $custCart->merge($sessCart)->save(); // merge them into customer cart
            $this->sessionCart(false, $custCart); // and set it as session cart

        } elseif ($sessCart && !$custCart) { // if only session cart exist

            $this->sessionCart()->set('customer_id', $customer->id())->save(); // assign it to customer

        } elseif (!$sessCart && $custCart) { // if only customer cart exist

            $this->sessionCart(false, $custCart); // set it as session cart

        }
        // clear cookie token
        $this->BResponse->cookie('cart', false);
    }

    /**
     *
     */
    public function onUserLogout()
    {
        static::$_sessionCart = null;
    }

    /**
     * @param FCom_Sales_Model_Cart $cart
     * @return FCom_Sales_Model_Cart
     * @throws BException
     */
    public function merge($cart)
    {
        if (is_numeric($cart)) {
            $cart = $this->load($cart);
        }
        foreach ($cart->items() as $item) {
            $this->addProduct($item->product_id, ['qty' => $item->qty, 'price' => $item->price]);
        }
        $cart->delete();
        $this->calculateTotals()->save();
        return $this;
    }

    /**
     * Return total UNIQUE number of items in the cart
     * @param boolean $assoc
     * @return FCom_Sales_Model_Cart_Item[]
     */
    public function items($assoc = true)
    {
        $this->items = $this->FCom_Sales_Model_Cart_Item->orm()->where('cart_id', $this->id)->find_many_assoc();
        return $assoc ? $this->items : array_values($this->items);
    }

    /**
     * @param int $limit
     * @return array
     */
    public function recentItems($limit = 3)
    {
        if (!$this->id()) {
            return [];
        }
        $orm = $this->FCom_Sales_Model_Cart_Item->orm('ci')->where('ci.cart_id', $this->id())
            ->order_by_desc('ci.update_at')->limit($limit);
        $this->BEvents->fire(__METHOD__ . ':orm', ['orm' => $orm]);
        $items = $orm->find_many();
        $this->BEvents->fire(__METHOD__ . ':data', ['items' => &$items]);
        return $items;
    }

    /**
     * @param null $items
     * @return FCom_Sales_Model_Cart
     */
    public function loadProducts($items = null)
    {
        if (is_null($items)) {
            $items = $this->items();
        }
        $productIds = [];
        foreach ($items as $item) {
            if ($item->product) continue;
            if (($cached = $this->FCom_Catalog_Model_Product->cacheFetch('id', $item->product_id))) {
                $item->product = $cached;
            } else {
                $productIds[$item->product_id] = $item->id;
            }
        }
        if ($productIds) {
            //todo: fix bug for ambigious field ID
            //$this->FCom_Catalog_Model_Product->cachePreloadFrom(array_keys($productIds));
        }
        foreach ($items as $item) {
            $item->product = $this->FCom_Catalog_Model_Product->load($item->product_id);
        }
        return $this;
    }

    /**
     * @param $cartId
     * @return array
     */
    public function cartItems($cartId)
    {
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        return $this->BDb->many_as_array($this->FCom_Catalog_Model_Product->orm()
            ->join($tCartItem, [$tCartItem . '.product_id', '=', $tProduct . '.id'])
            ->select($tProduct . '.*')
            ->select($tCartItem . '.*')
            ->where($tCartItem . '.cart_id', $cartId)
            ->find_many());
    }

    /**
     * Return total number of items in the cart
     * @return integer
     */
    public function itemQty()
    {
        return $this->get('item_qty') * 1;
    }

    /**
     * @param $productId
     * @param array $params
     * @return $this
     * @throws BException
     */
    public function addProduct($productId, $params = [])
    {
        //save cart to DB on add first product
        if (!$this->id()) {
            $this->item_qty = 1;
            $this->save();
        }

        if (empty($params['qty']) || !is_numeric($params['qty'])) {
            $params['qty'] = 1;
        }
        $params['qty'] = intval($params['qty']);
        if (empty($params['price']) || !is_numeric($params['price'])) {
            $params['price'] = 0;
        }
        $item = $this->FCom_Sales_Model_Cart_Item->loadWhere(['cart_id' => $this->id, 'product_id' => $productId]);
        if ($item && $item->promo_id_get == 0) {
            $item->add('qty', $params['qty']);
            $item->set('price', $params['price']);
        } else {
            $item = $this->FCom_Sales_Model_Cart_Item->create(['cart_id' => $this->id, 'product_id' => $productId,
                'qty' => $params['qty'], 'price' => $params['price']]);
        }
        if (isset($params['data'])) {

            $variants = $item->getData('variants');
            $flag = true;
            $params['data']['variants']['field_values'] = $this->BUtil->fromJson($params['data']['variants']['field_values']);
            if (null !== $variants) {
                foreach ($variants as &$arr) {
                    if (in_array($params['data']['variants']['field_values'], $arr)) {
                        $flag = false;
                        $arr['variant_qty'] = $arr['variant_qty'] + $params['qty'];
                        if (isset($params['shopper'])) {
                            $arr['shopper'] = $params['shopper'];
                        }

                    }
                }
            }
            if ($flag) {
                if (!empty($params['data']['variants'])) {
                    $params['data']['variants']['variant_qty'] = $params['qty'];
                    $variants = (null !== $variants)? $variants : [];
                    if (isset($params['shopper'])) {
                        $params['data']['variants']['shopper'] = $params['shopper'];
                    }
                    array_push($variants, $params['data']['variants']);
                }
            }
            $item->setData('variants', $variants);
        }
        $item->save();
        if (empty($params['no_calc_totals'])) {
            $this->calculateTotals()->save();
        }

        $this->BEvents->fire(__METHOD__, ['model' => $this, 'item' => $item]);

        #$this->sessionCartId($this->id);7
        return $this;
    }

    /**
     * @param $item
     * @return $this
     */
    public function removeItem($item)
    {
        if (is_numeric($item)) {
            $this->items();
            $item = $this->childById('items', $item);
        }
        if ($item) {
            unset($this->items[$item->id]);
            $item->delete();
            $this->calculateTotals()->save();
        }
        return $this;
    }

    /**
     * @param $productId
     * @return $this
     */
    public function removeProduct($productId)
    {
        $this->items();
        $this->removeItem($this->childById('items', $productId, 'product_id'));
        $this->BEvents->fire(__METHOD__, ['model' => $this]);
        return $this;
    }

    /**
     * @param $request
     * @return $this
     * @throws BException
     */
    public function updateItemsQty($request)
    {
        $items = $this->items();
        foreach ($request as $data) {
            if (!empty($items[$data->id])) {
                $data->qty = intval($data->qty);
                $items[$data->id]->set('qty', $data->qty)->save();
            }
        }
        $this->calculateTotals()->save();
        return $this;
    }

    /**
     * @param $name
     * @param null $class
     * @return $this
     */
    public function registerTotalRowHandler($name, $class = null)
    {
        if (is_null($class)) $class = $name;
        static::$_totalRowHandlers[$name] = $class;
        return $this;
    }

    /**
     * @return array
     */
    public function getTotalRowInstances()
    {
        if (!$this->totals) {
            $this->totals = [];
            foreach (static::$_totalRowHandlers as $name => $class) {
                $inst = $class::i(true)->init($this);
                $this->totals[$inst->getCode()] = $inst;
            }
            uasort($this->totals, function($a, $b) { return $a->getSortOrder() - $b->getSortOrder(); });
        }
        return $this->totals;
    }

    /**
     * @return $this
     */
    public function calculateTotals()
    {
        $this->loadProducts();
        $data = $this->data;
        $data['totals'] = [];
        foreach ($this->getTotalRowInstances() as $total) {
            $total->init($this)->calculate();
            $data['totals'][$total->getCode()] = $total->asArray();
        }
        $data['last_calc_at'] = time();
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        //TODO: price invalidate
        if (empty($this->data['totals']) || empty($this->data['last_calc_at'])
            || $this->data['last_calc_at'] < time() - 86400
        ) {
            $this->calculateTotals()->save();
        }

        return $this->getTotalRowInstances();
    }

    /**
     * @return bool
     */
    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (!$this->create_at) {
            $this->create_at = $this->BDb->now();
        }
        if (!$this->customer_id && $this->FCom_Customer_Model_Customer->isLoggedIn()) {
            $this->customer_id = $this->FCom_Customer_Model_Customer->sessionUserId();
        }
        $shippingMethod = $this->getShippingMethod();

        if ($shippingMethod) {
            $services = $shippingMethod->getDefaultService();
            $this->shipping_service = key($services);
        } else {
            // handle orders with no shipping needed
            #throw new BException("No shipping methods configured.");
        }

        if (!$this->payment_method) {
            $this->payment_method = $this->BConfig->get('modules/FCom_Sales/default_payment_method');
        }

        $this->update_at = $this->BDb->now();
        $this->data_serialized = $this->BUtil->toJson($this->data);
        return true;
    }

    /**
     *
     */
    public function onAfterLoad()
    {
        parent::onAfterLoad();
        $this->data = !empty($this->data_serialized) ? $this->BUtil->fromJson($this->data_serialized) : [];
    }

    /**
     * @return mixed
     */
    public function getAddresses()
    {
        if (!$this->_addresses) {
            $this->_addresses = $this->FCom_Sales_Model_Cart_Address->orm()
                ->where("cart_id", $this->id())
                ->find_many_assoc('atype');
        }
        return $this->_addresses;
    }

    /**
     * @return null
     */
    public function getBillingAddress()
    {
        $addresses = $this->getAddresses();
        return !empty($addresses['billing']) ? $addresses['billing'] : null;
    }

    /**
     * @return null
     */
    public function getShippingAddress()
    {
        $addresses = $this->getAddresses();
        return !empty($addresses['shipping']) ? $addresses['shipping'] : $this->getBillingAddress();
    }

    /**
     * @param $atype
     * @param $data
     * @return $this
     */
    public function setAddressByType($atype, $data)
    {
        $address = $atype === 'billing' ? $this->getBillingAddress() : $this->getShippingAddress();
        if (!$address) {
            $address = $this->FCom_Sales_Model_Cart_Address->create(['cart_id' => $this->id, 'atype' => $atype]);
        }
        if ($data instanceof FCom_Customer_Model_Address) {
            $data = $this->BUtil->arrayMask($data->as_array(), 'firstname,lastname,attn,' .
                'street1,street2,street3,city,region,postcode,country,phone,fax,lat,lng');
        }
        $address->set($data)->save();
        $this->_addresses[$atype] = $address;
        return $this;
    }

    /**
     * @param $customer
     * @return bool
     */
    public function importAddressesFromCustomer($customer)
    {
        $hlp = $this->FCom_Sales_Model_Cart_Address;

        $defBilling = $customer->getDefaultBillingAddress();
        if (!$defBilling) {
            return false;
        }
        $defShipping = $customer->getDefaultShippingAddress();

        $this->setAddressByType('billing', $defBilling);

        if ($defBilling->id == $defShipping->id) {
            $this->same_address = 1;
        } else {
            $this->same_address = 0;
            $this->setAddressByType('shipping', $defShipping);
        }
        return true;
    }

    /**
     * @return null
     */
    public function getShippingMethod()
    {
        if (!$this->shipping_method) {
            $shippingMethod = $this->BConfig->get('modules/FCom_Sales/default_shipping_method');
            if (!$shippingMethod) {
                return null;
            }
            $this->shipping_method = $shippingMethod;
        }
        $methods = $this->FCom_Sales_Main->getShippingMethods();
        return $methods[$this->shipping_method];
    }

    /**
     * Set shipping method
     *
     * Check if provided code is valid shipping method and apply it
     * @param string $shipping_method
     * @return $this
     */
    public function setShippingMethod($shipping_method)
    {
        if ($this->shipping_method != $shipping_method &&
            in_array($shipping_method, $this->FCom_Sales_Main->getShippingMethods())) {
            $this->shipping_method = $shipping_method;
        }
        return $this;
    }
    /**
     * @return null|FCom_Sales_Method_Payment_Interface
     */
    public function getPaymentMethod()
    {
        if (!$this->payment_method) {
            return null;
        }
        $methods = $this->FCom_Sales_Main->getPaymentMethods();
        return $methods[$this->payment_method];
    }

    /**
     * Set payment method
     *
     * Check if provided code is valid payment method and apply it
     * @param string $payment_method
     * @return $this
     */
    public function setPaymentMethod($payment_method)
    {
        if ($this->payment_method != $payment_method &&
            array_key_exists($payment_method, $this->FCom_Sales_Main->getPaymentMethods())) {
            $this->payment_method = $payment_method;
        }
        return $this;
    }

    /**
     * @param $status
     * @return $this
     * @throws BException
     */
    public function setStatus($status)
    {
        $this->set('status', $status);
        $this->BEvents->fire(__METHOD__, ['cart' => $this, 'status' => $status]);
        return $this;
    }

    /**
     * @return bool
     */
    public function placeOrder()
    {
        $cart = $this->orm ? $this : $this->sessionCart();
        try {
            /* @var $cart FCom_Sales_Model_Cart */
            $order = $this->FCom_Sales_Model_Order->createFromCart($cart, ['all_components' => true]);
            $order->save();

            //$order->importAllComponentsFromCart($cart);
            //$order->importItemsFromCart($cart);
            //$order->importAddressesFromCart($cart);
            //$order->importPaymentFromCart($cart);
            //$order->save();
            // $payment = $this->FCom_Sales_Model_Order_Payment->createFromCart($cart);
//            $order->pay();
            $cart->setStatus('ordered')->save();
            return $order;
        } catch (Exception $e) {
            // if something failed, like bad payment method
            // set some error message in session and do nothing
            $this->BDebug->logException($e);
        }
        return false;
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->_addresses = null;
        $this->items = null;
        $this->totals = null;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setPaymentDetails($data = [])
    {
        if (!empty($data)) {
            $paymentMethod = $this->getPaymentMethod();
            if ($paymentMethod) {
                $paymentMethod->setDetails($data);
                $this->payment_details = $this->BUtil->toJson($paymentMethod->getPublicData());
            }
        }
        return $this;
    }

    /**
     * @param $post
     */
    public function setPaymentToUser($post)
    {
        if ($this->FCom_Customer_Model_Customer->isLoggedIn() && isset($post['payment'])) {
            $user = $this->FCom_Customer_Model_Customer->sessionUser();
            $user->setPaymentDetails($post['payment']);
        }

    }
}
