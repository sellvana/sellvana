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
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Sales_Model_Cart_Item $Sellvana_Sales_Model_Cart_Item
 * @property Sellvana_Sales_Model_Cart_State $Sellvana_Sales_Model_Cart_State
 */
class Sellvana_Sales_Model_Cart extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_Address;

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

    /**
     * @var Sellvana_Sales_Model_Cart_State
     */
    protected $_state;

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
     * @return Sellvana_Sales_Model_Cart
     */
    public function sessionCart($createAnonymousIfNeeded = false)
    {
        // if there's already session cart, return existing session cart
        if (static::$_sessionCart) {
            return static::$_sessionCart;
        }

        // get unique cart token from cookie
        $cookieToken = $this->BRequest->cookie('cart');

        // get session user
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();

        $cart = null;
        // if cookie cart token is set, try to load it
        if ($cookieToken) {
            $cart = $this->loadWhere([
                'cookie_token' => (string)$cookieToken,
                'state_overall' => Sellvana_Sales_Model_Cart_State_Overall::ACTIVE
            ]);
            $this->resetSessionCart($cart);
        }
        if (!$cart && $customer) { // if no cookie cart token and customer is logged in, try to find customer cart
            $cart = $this->loadWhere([
                'customer_id' => $customer->id(),
                'state_overall' => Sellvana_Sales_Model_Cart_State_Overall::ACTIVE
            ]);
            if ($cart) {
                $this->resetSessionCart($cart);
            }
        }
        if (!$cart && ($customer || $createAnonymousIfNeeded)) {
            $this->Sellvana_Sales_Main->workflowAction('customerCreatesNewCart');
        }

        return static::$_sessionCart;
    }

    /**
     * @param Sellvana_Sales_Model_Cart
     * @return Sellvana_Sales_Model_Cart
     */
    public function resetSessionCart($cart = null)
    {
        static::$_sessionCart = $cart;

        if ($cart) {
            // get cookie token ttl from config
            $ttl = $this->BConfig->get('modules/Sellvana_Sales/cart_cookie_token_ttl_days') * 86400;
            // set cookie cart token for found cart
            $this->BResponse->cookie('cart', $cart->get('cookie_token'), $ttl);
        } else {
            $this->BResponse->cookie('cart', false);
        }

        return $cart;
    }

    /**
     * @param Sellvana_Sales_Model_Cart $cart
     * @return Sellvana_Sales_Model_Cart
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
        $this->calculateTotals()->saveAllDetails();
        return $this;
    }

    /**
     * Return total UNIQUE number of items in the cart
     * @param boolean $assoc
     * @return Sellvana_Sales_Model_Cart_Item[]
     */
    public function items($assoc = true)
    {
        if (!$this->items) {
            $this->items = $this->Sellvana_Sales_Model_Cart_Item->orm()->where('cart_id', $this->id())->find_many_assoc();
            foreach ($this->items as $item) {
                $item->setCart($this);
            }
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
        $orm = $this->Sellvana_Sales_Model_Cart_Item->orm('ci')->where('ci.cart_id', $this->id())
            ->order_by_desc('ci.update_at')->limit($limit);
        $this->BEvents->fire(__METHOD__ . ':orm', ['orm' => $orm]);
        $items = $orm->find_many();
        $this->BEvents->fire(__METHOD__ . ':data', ['items' => &$items]);
        return $items;
    }

    /**
     * @param null $items
     * @return Sellvana_Sales_Model_Cart
     */
    public function loadProducts($items = null)
    {
        if (is_null($items)) {
            $items = $this->items();
        }
        $productIds = [];
        /** @var Sellvana_Sales_Model_Cart_Item[] $itemsToUpdate */
        $itemsToUpdate = [];
        foreach ($items as $item) {
            if ($item->getProduct(false)) {
                continue;
            }
            $pId = $item->get('product_id');
            /** @var Sellvana_Catalog_Model_Product $cached */
            $cached = $this->Sellvana_Catalog_Model_Product->cacheFetch('id', $pId);
            if ($cached) {
                $item->setProduct($cached);
            } else {
                $productIds[$pId] = $pId;
                $itemsToUpdate[] = $item;
            }
        }
        if ($productIds) {
            $products = $this->Sellvana_Catalog_Model_Product->orm('p')->where_in('p.id', $productIds)->find_many_assoc('id');
            $this->Sellvana_Catalog_Model_ProductPrice->collectProductsPrices($products);
            foreach ($itemsToUpdate as $item) {
                $item->setProduct($products[$item->get('product_id')]);
            }
        }
        return $this;
    }

    /**
     * @param $cartId
     * @return array
     */
    public function cartItems($cartId)
    {
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();
        $tCartItem = $this->Sellvana_Sales_Model_Cart_Item->table();
        return $this->BDb->many_as_array($this->Sellvana_Catalog_Model_Product->orm()
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
        if (!empty($params['show_separate'])) {
            return false;
        }
        $items = $this->items();
        foreach ($items as $item) {
            if ($item->get('show_separate') || $item->get('product_id') !== $params['product_id']) {
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
     * @todo move variants to Sellvana_CustomField
     *
     * @param Sellvana_Catalog_Model_Product|int $product
     * @param array $params
     *      - qty
     *      - price
     *      - is_separate
     * @return Sellvana_Sales_Model_Cart_Item
     */
    public function addProduct($product, $params = [])
    {
        //save cart to DB on add first product
        if (!$this->id()) {
            $this->save();
        }

        if (is_numeric($product)) {
            $productId = $product;
            $product = $this->Sellvana_Catalog_Model_Product->load($productId);
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

        /** @var Sellvana_Sales_Model_Cart_Item $item */
        $item = null;
        if (empty($params['show_separate'])) {
            $where = [
                'cart_id' => $this->id(),
                'product_id' => $productId,
                'show_separate' => 0,
            ];
            if (!empty($params['signature'])) {
                $where['unique_hash'] = $hash;
            }
            $item = $this->Sellvana_Sales_Model_Cart_Item->loadWhere($where);
            if ($item) {
                $item->add('qty', $params['qty']);
                $item->set('price', $params['price']);
            }
        }
        if (!empty($params['inventory_id'])) {
            $skuModel = $this->Sellvana_Catalog_Model_InventorySku->load($params['inventory_id']);
        } else {
            $skuModel = $this->Sellvana_Catalog_Model_InventorySku->load($product->get('inventory_sku'), 'inventory_sku');
        }

        if (!$item) {
            $itemData = [
                'cart_id' => $this->id(),
                'product_id' => $productId,
                'product_name' => $product->get('product_name'),
                'product_sku' => !empty($params['product_sku']) ? $params['product_sku'] : $product->get('product_sku'),
                'inventory_sku' => $product->get('inventory_sku'),
                'show_separate' => !empty($params['show_separate']) ? $params['show_separate'] : 0,
                'qty' => $params['qty'],
                'price' => $params['price'],
                'unique_hash' => $hash,
                'auto_added' => !empty($params['auto_added']) ? $params['auto_added'] : 0,
                'parent_item_id' => !empty($params['parent_item_id']) ? $params['parent_item_id'] : null,
            ];
            if ($skuModel) {
                $itemData = array_merge($itemData, [
                    'inventory_id' => $skuModel->id(),
                    'pack_separate' => $skuModel->get('pack_separate'),
                    'shipping_weight' => $skuModel->get('shipping_weight'),
                    'shipping_size' => $skuModel->get('shipping_size'),
                    'cost' => $skuModel->get('unit_cost'),
                ]);
            }
            $item = $this->Sellvana_Sales_Model_Cart_Item->create($itemData);
        }
        if (!empty($params['data'])) {
            foreach ($params['data'] as $key => $val) {
                $item->setData($key, $val);
            }
        }

        $item->save();

        $this->BEvents->fire(__METHOD__, ['model' => $this, 'item' => $item]);

        return $item;
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
     * @return Sellvana_Sales_Model_Cart_Total_Abstract[]
     */
    public function getTotalRowInstances()
    {
        if (!$this->totals) {
            $this->totals = [];
            foreach (static::$_totalRowHandlers as $name => $class) {
                /** @var Sellvana_Sales_Model_Cart_Total_Abstract $inst */
                $inst = $this->BClassRegistry->instance($class)->init($this);
                $this->totals[$inst->getCode()] = $inst;
            }
            uasort($this->totals, function($a, $b) { return $a->getSortOrder() - $b->getSortOrder(); });
        }
        return $this->totals;
    }

    /**
     * @param string $type
     * @return Sellvana_Sales_Model_Cart_Total_Abstract
     * @throws BException
     */
    public function getTotalByType($type)
    {
        $totals = $this->getTotalRowInstances();
        if (empty($totals[$type])) {
            throw new BException('Invalid total type: ' . $type);
        }
        return $totals[$type];
    }

    /**
     * @return $this
     */
    public function calculateTotals()
    {
        $this->loadProducts();
        $totals = [];
        foreach ($this->getTotalRowInstances() as $total) {
            $total->init($this)->calculate();
            $totals[$total->getCode()] = $total->asArray();
        }
        $this->set('last_calc_at', time())->setData('totals', $totals);
        return $this;
    }

    /**
     * @return Sellvana_Sales_Model_Cart_Total_Abstract[]
     */
    public function getTotals()
    {
        //TODO: price invalidate
        if (!$this->getData('totals') || !$this->get('last_calc_at') || $this->get('last_calc_at') < time() - 86400) {
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

        $customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();

        if (!$this->get('customer_id') && $customerId) {
            $this->set('customer_id', $customerId);
        }

        return true;
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();

        $this->set('same_address', 1);
        $defCountry = $this->BConfig->get('modules/FCom_Core/default_country');
        $this->set('shipping_country', $defCountry)->set('billing_country', $defCountry);
        $this->setShippingMethod(true, null, true);
        $this->setPaymentMethod(true);
        $this->state()->overall()->setActive();

        return $this;
    }

    /**
     * @return BData
     */
    public function getBillingAddress()
    {
        return $this->addressAsObject('billing');
    }

    /**
     * @return BData
     */
    public function getShippingAddress()
    {
        return $this->addressAsObject('shipping');
    }

    public function importAddressesFromCustomer(Sellvana_Customer_Model_Customer $customer)
    {
        $defBilling = $customer->getDefaultBillingAddress();
        if ($defBilling) {
            $this->importAddressFromObject($defBilling, 'billing');
        }
        $defShipping = $customer->getDefaultShippingAddress();
        if ($defShipping) {
            $this->importAddressFromObject($defShipping, 'shipping');
        }

        $this->set([
            'same_address' => $defBilling && $defShipping && $defBilling->id() == $defShipping->id(),
            'recalc_shipping_rates' => 1,
        ]);

        return $this;
    }

    public function importPaymentMethodFromCustomer(Sellvana_Customer_Model_Customer $customer)
    {
        $this->set('payment_method', $customer->getPaymentMethod());
        $this->setData('payment_details', $customer->getPaymentDetails());
        return $this;
    }

    public function isShippable()
    {
        foreach ($this->items() as $item) {
            if ($item->isShippable()) {
                return true;
            }
        }
        return false;
    }

    public function hasShippingMethod()
    {
        return $this->get('shipping_method') ? true : false;
    }

    /**
     * @return null
     */
    public function getShippingMethod()
    {
        if (!$this->shipping_method) {
            $shippingMethod = $this->BConfig->get('modules/Sellvana_Sales/default_shipping_method');
            if (!$shippingMethod) {
                return null;
            }
            $this->shipping_method = $shippingMethod;
        }
        $methods = $this->Sellvana_Sales_Main->getShippingMethods();
        return $methods[$this->shipping_method];
    }

    /**
     * Set shipping method
     *
     * Check if provided code is valid shipping method and apply it
     * @throws BException
     * @param string $method
     * @param string $service
     * @return $this
     */
    public function setShippingMethod($method, $service = null, $ignoreInvalid = false)
    {
        $methods = $this->Sellvana_Sales_Main->getShippingMethods();
        if (true === $method) {
            $method = $this->BConfig->get('modules/Sellvana_Sales/default_shipping_method');
        }
        if (!$ignoreInvalid && empty($methods[$method])) {
            throw new BException('Invalid shipping method: '. $method);
        }
        if (!empty($methods[$method])) {
            $services = $methods[$method]->getServices();
            if (null !== $service && empty($services[$service])) {
                throw new BException('Invalid shipping service: ' . $service . '(' . $method . ')');
            }
        } else {
            $method = null;
            $service = null;
        }
        $this->set('shipping_method', $method)->set('shipping_service', $service);
        return $this;
    }

    /**
     * @return null|Sellvana_Sales_Method_Payment_Interface
     */
    public function getPaymentMethod()
    {
        if (!$this->payment_method) {
            return null;
        }
        $methods = $this->Sellvana_Sales_Main->getPaymentMethods();
        return $methods[$this->payment_method];
    }

    /**
     * Set payment method
     *
     * Check if provided code is valid payment method and apply it
     * @throws BException
     * @param string $method
     * @return $this
     */
    public function setPaymentMethod($method)
    {
        $methods = $this->Sellvana_Sales_Main->getPaymentMethods();
        if (true === $method) {
            $method = $this->BConfig->get('modules/Sellvana_Sales/default_payment_method');
        } elseif (empty($methods[$method])) {
            throw new BException('Invalid payment method: ' . $method);
        }
        $this->set('payment_method', $method);
        return $this;
    }

    public function setPaymentDetails($data = [])
    {
        if (empty($data)) {
            return $this;
        }
        $paymentMethod = $this->getPaymentMethod();
        if (!$paymentMethod) {
            return $this;
        }
        $prefix = $paymentMethod->getCheckoutFormPrefix();
        if (!empty($data[$prefix])) {
            $paymentMethod->setPaymentFormData($data[$prefix]);
        }
        $data = $paymentMethod->getDataToSave();
        if ($data && is_array($data)) {
            $this->setData('payment_details', [$prefix => $data]);
        }
        return $this;
    }

    /**
     * @param $post
     */
    public function setPaymentToUser($post)
    {
        /** @var Sellvana_Customer_Model_Customer $user */
        $user = $this->Sellvana_Customer_Model_Customer->sessionUser();
        if ($user && isset($post['payment'])) {
            $user->setPaymentDetails($post['payment']);
        }
    }

    /**
     * Verify if the cart has a complete billing or shipping address
     *
     * @throws BException
     * @param string $type 'billing' or 'shipping'
     * @return boolean
     */
    public function hasCompleteAddress($type)
    {
        if ('billing' !== $type && 'shipping' !== $type) {
            throw new BException('Invalid address type: ' . $type);
        }
        $country = $this->get($type . '_country');
        if (!$country) {
            return false;
        }
        $fields = ['firstname', 'lastname', 'street1', 'city'];
        if ($this->BLocale->postcodeRequired($country)) {
            $fields[] = 'postcode';
        }
        if ($this->BLocale->regionRequired($country)) {
            $fields[] = 'region';
        }
        foreach ($fields as $field) {
            $val = $this->get($type . '_' . $field);
            if (null === $val || '' === $val) {
                return false;
            }
        }
        return true;
    }

    public function getShippingRates()
    {
        $ratesArr = $this->getData('shipping_rates');
        if (!$ratesArr) {
            return false;
        }
        $result = [];
        $selMethod = $this->get('shipping_method');
        $selService = $this->get('shipping_service');

        $allMethods = $this->Sellvana_Sales_Main->getShippingMethods();
        foreach ($allMethods as $methodCode => $method) {
            if (empty($ratesArr[$methodCode])) {
                continue;
            }
            $servicesArr = $ratesArr[$methodCode];
            if (!empty($servicesArr['error'])) {
                continue;
            }
            $allServices = $method->getServices();
            $services = [];
            foreach ($servicesArr as $serviceCode => $serviceRate) {
                $serviceTitle = $allServices[$serviceCode];
                $services[$serviceCode] = [
                    'value' => $methodCode . ':' . $serviceCode,
                    'title' => $serviceTitle,
                    'price' => $serviceRate['price'],
                    'max_days' => $serviceRate['max_days'],
                    'selected' => $selMethod == $methodCode && $selService == $serviceCode,
                ];
            }
            if ($services) {
                $result[$methodCode] = [
                    'title' => $method->getDescription(),
                    'services' => $services,
                ];
            }
        }

        return $result;
    }

    public function getCouponCodes()
    {
        $codes = $this->get('coupon_code');
        return $codes ? explode(',', $codes) : [];
    }

    /**
     * @return Sellvana_Sales_Model_Cart_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Cart_State->factory($this);
        }
        return $this->_state;
    }


    public function __destruct()
    {
        parent::__destruct();
        unset($this->_addresses, $this->items, $this->totals);
    }

}
