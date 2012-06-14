<?php

class FCom_Checkout_Model_Cart extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cart';
    protected static $_origClass = __CLASS__;

    protected static $_sessionCart;
    protected $shippingMethods = array();
    protected $shippingClasses = array();

    public $items;


    static public function sessionCartId($id=BNULL)
    {
        if (BNULL===$id) {
            return BSession::i()->data('cart_id');
        }
        BSession::i()->data('cart_id', $id);
        return $id;
    }

    static public function sessionCart($reset = true)
    {
        if ($reset || !static::$_sessionCart) {
            if ($reset instanceof FCom_Checkout_Model_Cart) {
                static::$_sessionCart = $reset;
                static::sessionCartId($reset->id);
            } else {
                if (($cartId = static::sessionCartId()) && ($cart = static::load($cartId))) {
                    static::$_sessionCart = $cart;
                } elseif (($cart = static::i()->load(BSession::i()->sessionId(), 'session_id'))) {
                    static::$_sessionCart = $cart;
                    static::sessionCartId($cart->id);
                } else {
                    static::$_sessionCart = static::i()->create();
                    static::sessionCartId();
                }
            }
        }
        return static::$_sessionCart;
    }

    static public function userLogin()
    {
        $user = FCom_Customer_Model_Customer::sessionUser();
        if(!$user){
            return;
        }
        $sessCartId = static::sessionCartId();
        if ($user->session_cart_id) {
            $cart = static::i()->load($user->session_cart_id);
            if(!$cart){
                $user->session_cart_id = $sessCartId;
                $user->save();
            } elseif ($user->session_cart_id != $sessCartId) {
                if ($sessCartId) {
                    $cart->merge($sessCartId)->save();
                }
            }
        } elseif ($sessCartId) {
            $user->set('session_cart_id', $sessCartId)->save();
        }

        static::sessionCartId($user->session_cart_id);
    }

    static public function userLogout()
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
        $this->calcTotals();
        return $this;
    }

    public static function carts($flags=array())
    {
        $sessCartId = 1*static::sessionCartId();
        $carts = array();
        if (!empty($flags['default'])) {
            $carts[] = array('id'=>$sessCartId, 'description'=>$sessCartId ? static::sessionCart()->description : 'Unsaved Cart');
        }
        $orm = static::factory();
        if (!empty($flags['user'])) {
            $orm->filter('by_user', $flags['user']);
        }
        if ($sessCartId) {
            $orm->where_not_equal('id', $sessCartId);
        }
        $orm->order_by_asc('sort_order');
        foreach ($orm->find_many() as $cart) $carts[] = $cart->as_array();
        if (!empty($flags['full'])) {
            static::loadCartsData($carts);
        }
        return $carts;
    }

    public static function loadCartsData(&$carts)
    {
        $cIds = array();
        foreach ($carts as $i=>$c) {
            $cIds[$c['id']] = $i;
        }
        $cartUsers = FCom_Checkout_Model_CartUser::factory()->where_in('cart_id', array_keys($cIds))->find_many();
        foreach ($cartUsers as $u) {
            $carts[$cIds[$u->cart_id]]['users'][] = $u->as_array();
        }
    }

    public static function newDescription()
    {
        return 'New Cart';
    }

    public static function addFromList()
    {
        $carts = static::carts(array('user'=>true, 'default'=>true));
        return $carts;
    }

    public static function sendToList()
    {
        $carts = static::carts(array('user'=>true, 'default'=>true));
        $carts[] = array('id'=>-1, 'description'=>'['.self::newDescription().']');
        return $carts;
    }

    public function items($assoc=true)
    {
        if (is_null($this->items)) {
            $this->items = FCom_Checkout_Model_CartItem::factory()->where('cart_id', $this->id)->find_many_assoc();
        }
        return $assoc ? $this->items : array_values($this->items);
    }

    public function loadProducts()
    {
        $productIds = array();
        foreach ($this->items() as $item) {
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
        foreach ($this->items() as $item) {
            $item->product = FCom_Catalog_Model_Product::i()->load($item->product_id);
        }
        return $this;
    }

    public static function cartItems($cartId)
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        $tCartItem = FCom_Checkout_Model_CartItem::table();
        return BDb::many_as_array(FCom_Catalog_Model_Product::factory()
            ->join($tCartItem, array($tCartItem.'.product_id','=',$tProduct.'.id'))
            ->select($tProduct.'.*')
            ->select($tCartItem.'.*')
            ->where($tCartItem.'.cart_id', $cartId)
            ->find_many());
    }

    public function itemQty()
    {
        return $this->item_qty*1;
    }

    public static function by_user($orm, $userId)
    {
        if (is_null($userId)) {
            $userId = FCom_Customer_Model_User::sessionUserId();
        }
        return $orm->where('user_id', $userId);
    }

    public static function sendProducts($request)
    {
        if (true===$request->multirow_ids) {
            $request->multirow_ids = array();
            $items = FCom_Checkout_Model_CartItem::factory()->select('product_id')->select('qty')->select('price')
                ->where('cart_id', $request->source_id)
                ->find_many();
            foreach ($items as $item) {
                $request->multirow_ids[] = $item->product_id;
            }
        }
        if ($request->target!=='catalog') {
            $productIds = !empty($request->multirow_ids) ? $request->multirow_ids : (array)$request->row_id;
            $request->qtys = array();
            if (empty($items)) {
                $items = FCom_Checkout_Model_CartItem::factory()->select('product_id')->select('qty')->select('price')
                    ->where('cart_id', $request->source_id)->where_in('product_id', $productIds)
                    ->find_many();
            }
            foreach ($items as $item) {
                $request->qtys[$item->product_id] = $item->qty;
            }
        }
    }

    public function addProduct($productId, $options=array())
    {
        $this->save();
        if (empty($options['qty']) || !is_numeric($options['qty'])) {
            $options['qty'] = 1;
        }
        if (empty($options['price']) || !is_numeric($options['price'])) {
            $options['price'] = 0;
        }
        $item = FCom_Checkout_Model_CartItem::load(array('cart_id'=>$this->id, 'product_id'=>$productId));
        if ($item) {
            $item->add('qty', $options['qty']);
            $item->add('price', $options['price']);
        } else {
            $item = FCom_Checkout_Model_CartItem::create(array('cart_id'=>$this->id, 'product_id'=>$productId,
                'qty'=>$options['qty'], 'price' => $options['price']));
        }
        $item->save();
        if (empty($options['no_calc_totals'])) {
            $this->calcTotals()->save();
        }
        $user = FCom_Customer_Model_Customer::sessionUser();
        if($user){
            $user->session_cart_id = $this->id;
            $user->save();
        }
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
        }
        return $this;
    }

    public function removeProduct($productId)
    {
        $this->items();
        $this->removeItem($this->childById('items', $productId, 'product_id'));
        return $this;
    }

    public static function updateCarts($request)
    {
        try {
            static::writeDb()->beginTransaction();

            $oldCarts = static::factory()->filter('by_user')->find_many_assoc();
            $newCarts = array();
            if (!empty($request->carts)) {
                foreach ($request->carts as $c) {
                    $newCarts[$c->id] = (array)$c;
                }
            }
            if (!empty($request->deleted)) {
                foreach ($request->deleted as $cId) {
                    if (!empty($oldCarts[$cId])) {
                        $oldCarts[$cId]->delete();
                    }
                }
            }
            foreach ($newCarts as $cId=>$c) {
                unset($c->id);
                if ($cId<0) {
                    unset($data['id']);
                    $cart = static::create($c)->save();
                } else {
                    if (empty($oldCarts[$cId])) {
throw new Exception("Invalid cart_id: ".$cId);
                        continue;
                    }
                    $cart = $oldCarts[$cId]->set($c)->save();
                }
                $cart->updateUsers(!empty($c['users']) ? $c['users'] : array(), !empty($c['deleted_users']) ? $c['deleted_users'] : null);
            }
            static::writeDb()->commit();
        } catch (Exception $e) {
            static::writeDb()->rollback();
            throw $e;
        }
    }

    public function updateItemsQty($request)
    {
        $items = $this->items();
        foreach ($request as $data) {
            if (!empty($items[$data->id])) {
                $items[$data->id]->set('qty', $data->qty)->save();
            }
        }
        $this->calcTotals()->save();
        return $this;
    }

    public function calcTotals()
    {
        $this->loadProducts();
        $this->item_num = 0;
        $this->item_qty = 0;
        $this->subtotal = 0;
        foreach ($this->items() as $item) {
            $this->item_num++;
            $this->item_qty += $item->qty;
            $this->subtotal += $item->price;
        }
        return $this;
    }
/*
    public function totalAsHtml()
    {
        $subtotal = $this->subtotal;
        $shipping = 0;
        if ($this->shipping_method) {
            $shipping = $this->shipping_price;
        }
        $discount = 0;
        if ($this->discount_code) {
            $discount = 10;
        }
        //if tax
        $beforeTax = $subtotal + $shipping - $discount;
        $estimatedTax = 0;
        if (1) {
            $estimatedTax = $beforeTax*0.2;
        }
        $total = $beforeTax + $estimatedTax;
        $html = '
Items: $'.$subtotal.'<br>
Shipping and handling: $'.$shipping.'<br>
Discount: -$'.$discount.'<br/>
Total before tax: $'.$beforeTax.'<br>
Estimated tax: $'.$estimatedTax.'<br>
<b>Order total: $'.$total.'</b>';
        return $html;
    }
*/
    public function addShippingMethod($method, $class)
    {
        $this->shippingMethods[$method] = $class;
    }

    /**
     *
     * @return Array of Shipping Method objects
     */
    public function getShippingMethods()
    {
        if (!$this->shippingMethods) {
            return false;
        }
        if (empty($this->shippingClasses)) {
            foreach($this->shippingMethods as $method => $class) {
                $this->shippingClasses[$method] = $class::i();
            }
        }
        return $this->shippingClasses;
    }

    public function getShippingClassName($method)
    {
        return $this->shippingMethods[$method];
    }

    public function getShippingMethod($method)
    {
        $this->getShippingMethods();
        if (!empty($this->shippingClasses[$method])){
            return $this->shippingClasses[$method];
        } else {
            return false;
        }
    }

    public function addTotalRow($name, $options)
    {
        $cart = self::sessionCart();
        $totals = BUtil::fromJson($cart->totals_json);
        $totals[$name] = array('name' => $name, 'options' => $options);
        $cart->totals_json = BUtil::toJson($totals);
        $cart->save();
    }

    public function calculateTotals()
    {
        $this->calc_balance = 0;
        $totals = BUtil::fromJson($this->totals_json);
        $sorted = $this->sortTotals($totals);
        if (!$sorted) {
            return;
        }

        foreach($sorted as $key => $totalMethod) {
            if (empty($totalMethod['options']['callback'])) {
                continue;
            }
            list($class, $func) = explode(".", $totalMethod['options']['callback']);
            if (!is_callable("$class::i()->{$func}()")) {
                continue;
            }
            $res = $class::i()->{$func}($this);
            if (false === $res) {
                $totals[$key]['total'] = 0;
                if (is_callable("$class::i()->getError()")) {
                    $totals[$key]['error'] = $class::i()->getError();
                }

            } else {
                $totals[$key]['total'] = $res;
            }

            $this->calc_balance += $totals[$key]['total'];
        }
        $this->totals_json = BUtil::toJson($totals);
        $this->save();
    }

    public function getTotals()
    {
        return BUtil::fromJson($this->totals_json);
    }


    public function sortTotals($totals)
    {
        $totalObjects = $totals;
        // take care of 'load_after' option
        foreach ($totalObjects as $index => $data) {
            if (!empty($data['options']['after'])) {
                $totalObjects[$index]['parents'][] = $data['options']['after'];
                $totalObjects[$data['options']['after']]['children'][] = $data['name'];
            }
        }

        // get modules without dependencies
        $rootModules = array();
        foreach ($totalObjects as $data) {
            if (empty($data['parents'])) {
                $rootModules[] = $data;
            }
        }

        $sorted = array();
        while($totalObjects) {
            // check for circular reference
            if (!$rootModules) return false;
            // remove this node from root modules and add it to the output
            $n = array_pop($rootModules);

            $sorted[$n['name']] = $n;

            if (empty($n['children'])) {
                unset($totalObjects[$n['name']]);
                continue;
            }
            // for each of its children: queue the new node, finally remove the original
            for ($i = count($n['children'])-1; $i>=0; $i--) {
                // get child module
                $childModule = $totalObjects[$n['children'][$i]];
                // remove child modules from parent
                unset($n['children'][$i]);
                // remove parent from child module
                unset($childModule['parents'][array_search($n['name'], $childModule['parents'])]);
                // check if this child has other parents. if not, add it to the root modules list
                if (empty($childModule['parents'])) array_push($rootModules, $childModule);
            }
            // remove processed module from list
            unset($totalObjects[$n['name']]);
        }

        $sortedTotals = array();
        foreach($sorted as $key => $data){
            $sortedTotals[$key] = $totals[$key];
        }
        return $sortedTotals;
    }

    //todo: rename to subtotalCallback
    public function subtotalCallback()
    {
        $cart = self::sessionCart();
        return $cart->subtotal;
    }

    //this is example
    //todo: move this to discout module when it will be ready
    public function discountCallback()
    {
        $cart = self::sessionCart();
        if ($cart->discount_code) {
            return -10;
        }
        return 0;
    }

    public function urlHash($id)
    {
        return '/carts/items/'.$id;
    }

    public static function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned DEFAULT NULL,
  `location_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `item_qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `item_num` smallint(6) unsigned NOT NULL DEFAULT '0',
  `subtotal` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `session_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `NewIndex1` (`session_id`),
  UNIQUE KEY `user_id` (`user_id`,`description`,`session_id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public static function upgrade_0_1_3()
    {
        if (BDb::ddlFieldInfo(static::table(), "shipping_method")){
            return;
        }
        BDb::run("
            ALTER TABLE ".static::table()." ADD `shipping_method` VARCHAR( 50 ) NOT NULL ,
ADD `shipping_price` DECIMAL( 10, 2 ) NOT NULL ,
ADD `payment_method` VARCHAR( 50 ) NOT NULL ,
ADD `payment_details` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
ADD `discount_code` VARCHAR( 50 ) NOT NULL,
ADD `calc_balance` DECIMAL( 10, 2 ) NOT NULL ,
            ADD `totals_json` TEXT NOT NULL "
        );
    }

    public static function upgrade_0_1_4()
    {
        if (BDb::ddlFieldInfo(static::table(), "shipping_service")){
            return;
        }
        BDb::run("
            ALTER TABLE ".static::table()." ADD `shipping_service` CHAR( 2 ) NOT NULL AFTER `shipping_price`"
        );

    }
}
