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
 * @property int shipping_same flag to know shipping is same as billing
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

    public $addresses;
    public $items;
    public $totals;
    protected $shipping_method;

    public function sessionCartId($id = null)
    {
        if (is_null($id)) {
            return $this->BSession->get('cart_id');
        }
        $this->BSession->set('cart_id', $id);
        return $id;
    }

    /**
     * @param bool $reset
     * @return FCom_Sales_Model_Cart
     */
    public function sessionCart($reset = false)
    {
        if ($reset || !static::$_sessionCart) {
            if ($reset instanceof FCom_Sales_Model_Cart) {
                static::$_sessionCart = $reset;
                $this->sessionCartId($reset->id);
            } else {
                $cartId = $this->sessionCartId();
                if ($cartId) {
                    $cart = $this->load($cartId);
                }
                if (!empty($cart)) {
                    static::$_sessionCart = $cart;
                } else {
                    $sessionId = $this->BSession->sessionId();
                    $cart = $this->orm()
                        ->where('session_id', $sessionId)
                        ->where('status', 'new')
                        ->find_one();
                    if ($cart) {
                        static::$_sessionCart = $cart;
                        $this->sessionCartId($cart->id);
                    } else {
                        static::$_sessionCart = $this->create(['session_id' => $sessionId]);
                        $this->sessionCartId();
                    }
                }
            }
        }
        return static::$_sessionCart;
    }

    public function onUserLogin()
    {
        // load just logged in customer
        $customer = $this->FCom_Customer_Model_Customer->sessionUser();
        // something wrong, abort abort!
        if (!$customer) {
            return;
        }
        // get session cart id
        $sessCartId = $this->sessionCartId();
        // try to load customer cart which is new (not abandoned or converted to order)
        $custCart = $this->FCom_Sales_Model_Cart->loadWhere(['customer_id' => $customer->id(), 'status' => 'new']);

        if ($sessCartId && $custCart && $sessCartId !== $custCart->id()) {

            // if both current session cart and customer cart exist and they're different carts
            $custCart->merge($sessCartId)->save(); // merge them into customer cart
            $this->sessionCartId($custCart->id); // and set it as session cart
            static::$_sessionCart = $custCart;

        } elseif ($sessCartId && !$custCart) { // if only session cart exist

            $this->sessionCart()->set('customer_id', $customer->id())->save(); // assign it to customer

        } elseif (!$sessCartId && $custCart) { // if only customer cart exist

            $this->sessionCartId($custCart->id()); // set it as session cart
            static::$_sessionCart = $custCart;

        }
    }

    public function onUserLogout()
    {
        $this->sessionCartId(false);
        static::$_sessionCart = null;
    }

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
     * @return array
     */
    public function items($assoc = true)
    {
        $this->items = $this->FCom_Sales_Model_Cart_Item->orm()->where('cart_id', $this->id)->find_many_assoc();
        return $assoc ? $this->items : array_values($this->items);
    }

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
                        $arr['shopper'] = $params['shopper'];
                    }
                }
            }
            if ($flag) {
                if (!empty($params['data']['variants'])) {
                    $params['data']['variants']['variant_qty'] = $params['qty'];
                    $variants = (null !== $variants)? $variants : [];
                    $params['data']['variants']['shopper'] = $params['shopper'];
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

        $this->sessionCartId($this->id);
        return $this;
    }

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

    public function removeProduct($productId)
    {
        $this->items();
        $this->removeItem($this->childById('items', $productId, 'product_id'));
        $this->BEvents->fire(__METHOD__, ['model' => $this]);
        return $this;
    }

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

    public function registerTotalRowHandler($name, $class = null)
    {
        if (is_null($class)) $class = $name;
        static::$_totalRowHandlers[$name] = $class;
        return $this;
    }

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

    public function onAfterLoad()
    {
        parent::onAfterLoad();
        $this->data = !empty($this->data_serialized) ? $this->BUtil->fromJson($this->data_serialized) : [];
    }

    public function getAddressByType($atype)
    {
        if (!$this->addresses) {
            $this->addresses = $this->FCom_Sales_Model_Cart_Address->orm()
                ->where("cart_id", $this->id)
                ->find_many_assoc('atype');
        }
        switch ($atype) {
            case 'billing':
                return !empty($this->addresses['billing']) ? $this->addresses['billing'] : null;

            case 'shipping':
                if (!empty($this->addresses['shipping'])) {
                    $this->shipping_same = 0;
                    return $this->addresses['shipping'];
                } elseif ($this->shipping_same) {
                    return $this->getAddressByType('billing');
                } else {
                    return null;
                }
            default:
                throw new BException('Invalid cart address type: ' . $atype);
        }
    }

    public function setAddressByType($atype, $data)
    {
        $address = $this->getAddressByType($atype);
        if (!$address) {
            $address = $this->FCom_Sales_Model_Cart_Address->create(['cart_id' => $this->id, 'atype' => $atype]);
        }
        if ($data instanceof FCom_Customer_Model_Address) {
            $data = $this->BUtil->arrayMask($data->as_array(), 'firstname,lastname,attn,' .
                'street1,street2,street3,city,region,postcode,country,phone,fax,lat,lng');
        }
        $address->set($data)->save();
        $this->addresses[$atype] = $address;
        return $this;
    }

    public function importAddressesFromCustomer($customer)
    {
        $hlp = $this->FCom_Sales_Model_Cart_Address;

        $defBilling = $customer->defaultBilling();
        if (!$defBilling) {
            return false;
        }
        $defShipping = $customer->defaultShipping();

        $this->setAddressByType('billing', $defBilling);

        if ($defBilling->id == $defShipping->id) {
            $this->shipping_same = 1;
        } else {
            $this->shipping_same = 0;
            $this->setAddressByType('shipping', $defShipping);
        }
        return true;
    }

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

    public function setStatus($status)
    {
        $this->set('status', $status);
        $this->BEvents->fire(__METHOD__, ['cart' => $this, 'status' => $status]);
        return $this;
    }

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

    public function __destruct()
    {
        $this->addresses = null;
        $this->items = null;
        $this->totals = null;
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
    public function setPaymentToUser($post)
    {
        if ($this->FCom_Customer_Model_Customer->isLoggedIn() && isset($post['payment'])) {
            $user = $this->FCom_Customer_Model_Customer->sessionUser();
            $user->setPaymentDetails($post['payment']);
        }

    }
}
