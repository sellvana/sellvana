<?php

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
    protected static $_totalRowHandlers = array();

    protected static $_fieldOptions = array(
        'status' => array(
            'new'     => 'New',
            'ordered' => 'Ordered',
        ),
    );

    public $addresses;
    public $items;
    public $totals;
    protected $shipping_method;

    static public function sessionCartId($id = null)
    {
        if (is_null($id)) {
            return BSession::i()->get('cart_id');
        }
        BSession::i()->set('cart_id', $id);
        return $id;
    }

    static public function sessionCart($reset = false)
    {
        if ($reset || !static::$_sessionCart) {
            if ($reset instanceof FCom_Sales_Model_Cart) {
                static::$_sessionCart = $reset;
                static::sessionCartId($reset->id);
            } else {
                $cartId = static::sessionCartId();
                if ($cartId) {
                    $cart = static::load($cartId);
                }
                if (!empty($cart)) {
                    static::$_sessionCart = $cart;
                } else {
                    $sessionId = BSession::i()->sessionId();
                    $cart = static::i()->orm()
                        ->where('session_id', $sessionId)
                        ->where('status', 'new')
                        ->find_one();
                    if ($cart) {
                        static::$_sessionCart = $cart;
                        static::sessionCartId($cart->id);
                    } else {
                        static::$_sessionCart = static::i()->create(array('session_id' => $sessionId));
                        static::sessionCartId();
                    }
                }
            }
        }
        return static::$_sessionCart;
    }

    static public function onUserLogin()
    {
        // load just logged in customer
        $customer = FCom_Customer_Model_Customer::i()->sessionUser();
        // something wrong, abort abort!
        if (!$customer) {
            return;
        }
        // get session cart id
        $sessCartId = static::sessionCartId();
        // try to load customer cart which is new (not abandoned or converted to order)
        $custCart = FCom_Sales_Model_Cart::i()->load(array($customer->id => 'customer_id', 'status'=>'new'));

        if ($sessCartId && $custCart && $sessCartId !== $custCart->id) {

            // if both current session cart and customer cart exist and they're different carts
            $custCart->merge($sessCartId)->save(); // merge them into customer cart
            static::sessionCartId($custCart->id); // and set it as session cart
            static::$_sessionCart = $custCart;

        } elseif ($sessCartId && !$custCart) { // if only session cart exist

            static::sessionCart()->set('customer_id', $customer->id)->save(); // assign it to customer

        } elseif (!$sessCartId && $custCart) { // if only customer cart exist

            static::sessionCartId($custCart->id); // set it as session cart
            static::$_sessionCart = $custCart;

        }
    }

    static public function onUserLogout()
    {
        static::sessionCartId(false);
        static::$_sessionCart = null;
    }

    public function merge($cartId)
    {
        $cart = static::i()->load($cartId);
        foreach ($cart->items() as $item) {
            $this->addProduct($item->product_id, array('qty'=>$item->qty, 'price'=>$item->price));
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
    public function items($assoc=true)
    {
        $this->items = FCom_Sales_Model_Cart_Item::i()->orm()->where('cart_id', $this->id)->find_many_assoc();
        return $assoc ? $this->items : array_values($this->items);
    }

    public function recentItems($limit=3)
    {
        if (!$this->id()) {
            return array();
        }
        $orm = FCom_Sales_Model_Cart_Item::i()->orm('ci')->where('ci.cart_id', $this->id())
            ->order_by_desc('ci.update_at')->limit($limit);
        BEvents::i()->fire(__METHOD__.':orm', array('orm'=>$orm));
        $items = $orm->find_many();
        BEvents::i()->fire(__METHOD__.':data', array('items'=>&$items));
        return $items;
    }

    public function loadProducts($items = null)
    {
        if (is_null($items)) {
            $items = $this->items();
        }
        $productIds = array();
        foreach ($items as $item) {
            if ($item->product) continue;
            if (($cached = FCom_Catalog_Model_Product::i()->cacheFetch('id', $item->product_id))) {
                $item->product = $cached;
            } else {
                $productIds[$item->product_id] = $item->id;
            }
        }
        if($productIds){
            //todo: fix bug for ambigious field ID
            //FCom_Catalog_Model_Product::i()->cachePreloadFrom(array_keys($productIds));
        }
        foreach ($items as $item) {
            $item->product = FCom_Catalog_Model_Product::i()->load($item->product_id);
        }
        return $this;
    }

    public static function cartItems($cartId)
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        $tCartItem = FCom_Sales_Model_Cart_Item::table();
        return BDb::many_as_array(FCom_Catalog_Model_Product::i()->orm()
            ->join($tCartItem, array($tCartItem.'.product_id','=',$tProduct.'.id'))
            ->select($tProduct.'.*')
            ->select($tCartItem.'.*')
            ->where($tCartItem.'.cart_id', $cartId)
            ->find_many());
    }

    /**
     * Return total number of items in the cart
     * @return integer
     */
    public function itemQty()
    {
        return $this->get('item_qty')*1;
    }

    public function addProduct($productId, $params=array())
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
        } else {
            $params['price'] = $params['price']; //$params['price'] * $params['qty']; // ??
        }
        $item = FCom_Sales_Model_Cart_Item::i()->load(array('cart_id'=>$this->id, 'product_id'=>$productId));
        if ($item && $item->promo_id_get == 0) {
            $item->add('qty', $params['qty']);
            $item->set('price', $params['price']);
        } else {
            $item = FCom_Sales_Model_Cart_Item::i()->create(array('cart_id'=>$this->id, 'product_id'=>$productId,
                'qty'=>$params['qty'], 'price' => $params['price']));
        }
        $item->save();
        if (empty($params['no_calc_totals'])) {
            $this->calculateTotals()->save();
        }

        BEvents::i()->fire(__METHOD__, array('model'=>$this, 'item' => $item));

        static::sessionCartId($this->id);
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
        BEvents::i()->fire(__METHOD__, array('model'=>$this));
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

    public function registerTotalRowHandler($name, $class=null)
    {
        if (is_null($class)) $class = $name;
        static::$_totalRowHandlers[$name] = $class;
        return $this;
    }

    public function getTotalRowInstances()
    {
        if (!$this->totals) {
            $this->totals = array();
            foreach (static::$_totalRowHandlers as $name=>$class) {
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
        $data['totals'] = array();
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
            $this->create_at = BDb::now();
        }
        if (!$this->customer_id && FCom_Customer_Model_Customer::i()->isLoggedIn()) {
            $this->customer_id = FCom_Customer_Model_Customer::i()->sessionUserId();
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
            $this->payment_method = BConfig::i()->get('modules/FCom_Sales/default_payment_method');
        }

        $this->update_at = BDb::now();
        $this->data_serialized = BUtil::toJson($this->data);
        return true;
    }

    public function onAfterLoad()
    {
        parent::onAfterLoad();
        $this->data = !empty($this->data_serialized) ? BUtil::fromJson($this->data_serialized) : array();
    }

    public function getAddressByType($atype)
    {
        if (!$this->addresses) {
            $this->addresses = FCom_Sales_Model_Cart_Address::i()->orm()
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
            throw new BException('Invalid cart address type: '.$atype);
        }
    }

    public function setAddressByType($atype, $data)
    {
        $address = $this->getAddressByType($atype);
        if (!$address) {
            $address = FCom_Sales_Model_Cart_Address::i()->create(array('cart_id' => $this->id, 'atype' => $atype));
        }
        if ($data instanceof FCom_Customer_Model_Address) {
            $data = BUtil::arrayMask($data->as_array(), 'firstname,lastname,attn,' .
                'street1,street2,street3,city,region,postcode,country,phone,fax,lat,lng');
        }
        $address->set($data)->save();
        $this->addresses[$atype] = $address;
        return $this;
    }

    public function importAddressesFromCustomer($customer)
    {
        $hlp = FCom_Sales_Model_Cart_Address::i();

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
            $shippingMethod = BConfig::i()->get('modules/FCom_Sales/default_shipping_method');
            if(!$shippingMethod){
                return null;
            }
            $this->shipping_method = $shippingMethod;
        }
        $methods = FCom_Sales_Main::i()->getShippingMethods();
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
            in_array($shipping_method, FCom_Sales_Main::i()->getShippingMethods())) {
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
        $methods = FCom_Sales_Main::i()->getPaymentMethods();
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
        if ( $this->payment_method != $payment_method &&
            array_key_exists($payment_method, FCom_Sales_Main::i()->getPaymentMethods())) {
            $this->payment_method = $payment_method;
        }
        return $this;
    }

    public function setStatus($status)
    {
        $this->set('status', $status);
        BEvents::i()->fire(__METHOD__, array('cart'=>$this, 'status'=>$status));
        return $this;
    }

    public function placeOrder()
    {
        $cart = $this->orm ? $this : static::sessionCart();
        try {
            /* @var $cart FCom_Sales_Model_Cart */
            $order = FCom_Sales_Model_Order::i()->createFromCart($cart, array('all_components' => true));
            $order->save();

            //$order->importAllComponentsFromCart($cart);
            //$order->importItemsFromCart($cart);
            //$order->importAddressesFromCart($cart);
            //$order->importPaymentFromCart($cart);
            //$order->save();
            // $payment = FCom_Sales_Model_Order_Payment::i()->createFromCart($cart);
//            $order->pay();
            $cart->setStatus('ordered')->save();
            return $order;
        } catch (Exception $e) {
            // if something failed, like bad payment method
            // set some error message in session and do nothing
            BDebug::logException($e);
        }
        return false;
    }

    public function __destruct()
    {
        $this->addresses = null;
        $this->items = null;
        $this->totals = null;
    }

    public function setPaymentDetails($data = array())
    {
        if(!empty($data)){
            $paymentMethod = $this->getPaymentMethod();
            if($paymentMethod){
                $paymentMethod->setDetails($data);
                $this->payment_details = BUtil::toJson($paymentMethod->getPublicData());
            }
        }
        return $this;
    }
    public function setPaymentToUser($post)
    {
        if (FCom_Customer_Model_Customer::isLoggedIn() && isset($post['payment'])) {
            $user = FCom_Customer_Model_Customer::i()->sessionUser();
            $user->setPaymentDetails($post['payment']);
        }

    }
}
