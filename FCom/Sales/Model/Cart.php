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
    use FCom_Sales_Model_Trait_Address;

    protected static $_table = 'fcom_sales_cart';
    protected static $_origClass = __CLASS__;

    protected static $_sessionCart;
    protected static $_totalRowHandlers = [];

    protected static $_fieldOptions = [
        'state_overall' => [
            'active'  => 'Active',
            'ordered' => 'Ordered',
            'abandoned' => 'Abandoned',
            'archived' => 'Archived',
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
                $cart = $this->loadOrCreate(['customer_id' => $customer->id(), 'state_overall' => 'active']);
            } else {
                $cookieToken = $this->BRequest->cookie('cart');
                if ($cookieToken) {
                    $cart = $this->loadWhere(['cookie_token' => (string)$cookieToken, 'state_overall' => 'active']);
                    if (!$cart && !$createAnonymousIfNeeded) {
                        $this->BResponse->cookie('cart', false);
                        return false;
                    }
                }
                if (empty($cart)) {
                    if ($createAnonymousIfNeeded) {
                        $cookieToken = $this->BUtil->randomString(32);
                        $cart = $this->create(['cookie_token' => (string)$cookieToken, 'state_overall' => 'active'])->save();
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
        $custCart = $this->FCom_Sales_Model_Cart->loadWhere(['customer_id' => $customer->id(), 'state_overall' => 'active']);

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
        if (!$this->items) {
            $this->items = $this->FCom_Sales_Model_Cart_Item->orm()->where('cart_id', $this->id())->find_many_assoc();
        }
        return $assoc ? $this->items : array_values($this->items);
    }

    /**
     * Save cart with items and other details
     *
     * @param array $options
     * @return static
     */
    public function saveAllDetails($options = [])
    {
        $this->save();
        foreach ($this->items() as $item) {
            $item->save();
        }
        return $this;
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

    public function findItemToMerge($params)
    {
        if (!empty($params['pack_separate'])) {
            return false;
        }
        $items = $this->items();
        foreach ($items as $item) {
            if ($item->get('pack_separate') || $item->get('product_id') !== $params['product_id']) {
                continue;
            }

        }
        return false;
    }

    public function calcItemSignatureHash($signature)
    {
        $s = $this->BUtil->toJson($signature);
        $hash = crc32($s);
        return $hash;
    }

    /**
     * @todo combine variants and shopper fields into structure grouped differently, i.e. all output in the same array
     * @todo move variants to FCom_CustomField
     *
     * @param FCom_Catalog_Model_Product|int $product
     * @param array $params
     *      - qty
     *      - price
     *      - is_separate
     * @return FCom_Sales_Model_Cart
     */
    public function addProduct($product, $params = [])
    {
        //save cart to DB on add first product
        if (!$this->id()) {
            $this->save();
        }

        if (is_numeric($product)) {
            $productId = $product;
            $product = $this->FCom_Catalog_Model_Product->load($productId);
        } else {
            $productId = $product->id();
        }

        if (empty($params['qty']) || !is_numeric($params['qty'])) {
            $params['qty'] = 1;
        }
        $params['qty'] = intval($params['qty']);

        if (empty($params['price']) || !is_numeric($params['price'])) {
            $params['price'] = 0;
        }

        $hash = !empty($params['signature']) ? $this->calcItemSignatureHash($params['signature']) : null;

        /** @var FCom_Sales_Model_Cart_Item $item */
        $item = null;
        if (empty($params['pack_separate'])) {
            $where = [
                'cart_id' => $this->id(),
                'product_id' => $productId,
                'pack_separate' => 0,
            ];
            if (!empty($params['signature'])) {
                $where['unique_hash'] = $hash;
            }
            $item = $this->FCom_Sales_Model_Cart_Item->loadWhere($where);
            if ($item) {
                $item->add('qty', $params['qty']);
                $item->set('price', $params['price']);
            }
        }
        if (!$item) {
            $item = $this->FCom_Sales_Model_Cart_Item->create([
                'cart_id' => $this->id(),
                'product_id' => $productId,
                'product_name' => $product->get('product_name'),
                'product_sku' => !empty($params['product_sku']) ? $params['product_sku'] : null,
                'inventory_id' => !empty($params['inventory_id']) ? $params['inventory_id'] : null,
                'inventory_sku' => !empty($params['inventory_sku']) ? $params['inventory_sku'] : null,
                'pack_separate' => !empty($params['pack_separate']) ? $params['pack_separate'] : false,
                'qty' => $params['qty'],
                'price' => $params['price'],
                'unique_hash' => $hash,
            ]);
        }
        if (!empty($params['data'])) {
            foreach ($params['data'] as $key => $val) {
                $item->setData($key, $val);
            }
        }

        $item->save();

        $this->BEvents->fire(__METHOD__, ['model' => $this, 'item' => $item]);

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
     * @return null
     */
    public function getBillingAddress()
    {
        return $this->addressAsObject('billing');
    }

    /**
     * @return null
     */
    public function getShippingAddress()
    {
        return $this->addressAsObject('shipping');
    }

    public function importAddressesFromCustomer(FCom_Customer_Model_Customer $customer)
    {
        $defBilling = $customer->getDefaultBillingAddress();
        if ($defBilling) {
            $this->importAddressFromObject($defBilling, 'billing');
        }
        $defShipping = $customer->getDefaultShippingAddress();
        if ($defShipping) {
            $this->importAddressFromObject($defShipping, 'shipping');
        }

        $this->same_address = $defBilling && $defShipping && $defBilling->id() == $defShipping->id();

        return $this;
    }

    public function importPaymentMethodFromCustomer(FCom_Customer_Model_Customer $customer)
    {
        $this->set('payment_method', $customer->getPaymentMethod());
        $this->setData('payment_details', $customer->getPaymentDetails());
        return $this;
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
     * @throws BException
     * @param string $shippingMethod
     * @return $this
     */
    public function setShippingMethod($shippingMethod)
    {
        if (!in_array($shippingMethod, $this->FCom_Sales_Main->getShippingMethods())) {
            throw new BException('Invalid shipping method: '. $shippingMethod);
        }
        $this->set('shipping_method', $shippingMethod);
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
     * @throws BException
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
        if (!array_key_exists($paymentMethod, $this->FCom_Sales_Main->getPaymentMethods())) {
            throw new BException('Invalid payment method: ' . $paymentMethod);
        }
        $this->set('payment_method', $paymentMethod);
        return $this;
    }

    public function setStatus($state)
    {
        if ($this->get('state_overall') !== $state) {
            $this->set('state_overall', $state);
            $this->BEvents->fire(__METHOD__, ['cart' => $this, 'state_overall' => $state]);
        }
        return $this;
    }

    public function setStatusActive()
    {
        $this->setStatus('active');
        return $this;
    }

    public function setStatusOrdered()
    {
        $this->setStatus('ordered');
        return $this;
    }

    public function setStatusAbandoned()
    {
        $this->setStatus('abandoned');
        return $this;
    }

    public function setStatusArchived()
    {
        $this->setStatus('archived');
        return $this;
    }

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
        /** @var FCom_Customer_Model_Customer $user */
        $user = $this->FCom_Customer_Model_Customer->sessionUser();
        if ($user && isset($post['payment'])) {
            $user->setPaymentDetails($post['payment']);
        }
    }

    public function __destruct()
    {
        unset($this->_addresses, $this->items, $this->totals);
    }

}
