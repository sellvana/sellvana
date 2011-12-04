<?php

class FCom_Checkout_Model_Cart extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cart';

    protected static $_sessionCart;

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
                    static::sessionCartId(null);
                }
            }
        }
        return static::$_sessionCart;
    }

    static public function userLogin()
    {
        $user = FCom_Checkout_Model_User::sessionUser();
        $sessCartId = static::sessionCartId();
        if ($user->session_cart_id) {
            if ($sessCartId) {
                $user->sessionCart()->merge($sessCartId)->save();
            }
            static::sessionCartId($user->session_cart_id);
        } elseif ($sessCartId) {
            $user->set('session_cart_id', $sessCartId)->save();
        }
    }

    public function merge($cartId)
    {
        $cart = static::i()->load($cartId);
        foreach ($cart->items() as $item) {
            $this->addProduct($item->product_id, array('qty'=>$item->qty));
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
        FCom_Catalog_Model_Product::i()->cachePreloadFrom(array_keys($productIds));
        foreach ($this->items() as $item) {
            $item->product = FCom_Catalog_Model_Product::i()->load($item->product_id);
        }
        return $this;
    }

    public static function cartItems($cartId)
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        $tCartItem = FCom_Checkout_Model_CartItem::table();
        return BDb::many_as_array(FCom_Catalog_Model_Product::factory()->filter('current_company', true, $tProduct)
            ->join($tCartItem, array($tCartItem.'.product_id','=',$tProduct.'.id'))
            ->select($tProduct.'.*')
            ->select($tCartItem.'.qty')
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
            $items = FCom_Checkout_Model_CartItem::factory()->select('product_id')->select('qty')
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
                $items = FCom_Checkout_Model_CartItem::factory()->select('product_id')->select('qty')
                    ->where('cart_id', $request->source_id)->where_in('product_id', $productIds)
                    ->find_many();
            }
            foreach ($items as $item) {
                $request->qtys[$item->product_id] = $item->qty;
            }
        }
    }

    public static function receiveProducts($request)
    {
        if ($request->target_id==0 || $request->target_id==-1) {
            $description = $request->target_id==0 ? 'Unsaved Cart' : $request->target_description;
            $request->target_description = Denteva_Model_Cart::i()->findNextAvailableValue('description', $description);
            /** @var Denteva_Model_Cart */
            $cart = FCom_Checkout_Model_Cart::i()->create(array(
                'company_id' => Denteva_Model_Company::sessionCompanyId(),
                'user_id' => Denteva_Model_User::sessionUserId(),
                'description' => $request->target_description,
            ))->save();
            $request->target_id = $cart->id;
            Denteva_Model_CartUser::i()->create(array(
                'cart_id' => $cart->id, 'user_id' => Denteva_Model_User::sessionUserId(),
                'can_admin'=>1, 'can_edit'=>1, 'can_share'=>1, 'can_order'=>1,
            ))->save();
            static::sessionCart($cart);
            Denteva_Model_User::sessionUser()->set('session_cart_id', $cart->id)->save();
        } elseif ($request->target_id==static::sessionCartId()) {
            $cart = static::sessionCart();
        } else {
            $cart = static::load($request->target_id);
        }
        $options = array('no_calc_totals'=>true);
        if (!empty($request->multirow_ids)) {
            foreach ($request->multirow_ids as $id) {
                $options['qty'] = !empty($request->qtys[$id]) ? $request->qtys[$id] : 1;
                $cart->addProduct($id, $options);
            }
        } elseif (!empty($request->row_id)) {
            $id = $request->row_id;
            $options['qty'] = !empty($request->qtys[$id]) ? $request->qtys[$id] : 1;
            $cart->addProduct($id, $options);
        }
        $cart->calcTotals()->save();
    }

    public static function afterSendProducts($request)
    {
        if (!empty($request->move)) {
            $ids = !empty($request->multirow_ids) ? $request->multirow_ids : (array)$request->row_id;
            Denteva_Model_CartItem::delete_many(array('cart_id'=>$request->source_id, 'product_id'=>$ids));
        }
    }

    public function addProduct($productId, $options=array())
    {
        $this->save();
        if (empty($options['qty']) || !is_numeric($options['qty'])) {
            $options['qty'] = 1;
        }
        $item = Denteva_Model_CartItem::load(array('cart_id'=>$this->id, 'product_id'=>$productId));
        if ($item) {
            $item->add('qty', $options['qty']);
        } else {
            $item = Denteva_Model_CartItem::create(array('cart_id'=>$this->id, 'product_id'=>$productId, 'qty'=>$options['qty']));
        }
        $item->save();
        if (empty($options['no_calc_totals'])) {
            $this->calcTotals()->save();
        }
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

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (Denteva_Model_User::i()->isLoggedIn()) {
            $this->set('company_id', Denteva_Model_Company::sessionCompanyId(), null);
            $this->set('location_id', Denteva_Model_Location::sessionLocationId(), null);
            $this->set('user_id', Denteva_Model_User::sessionUserId(), null);
        } else {
            $this->set('session_id', BSession::i()->sessionId(), null);
        }
        $this->set('description', 'Unsaved Cart', null);
        if (!$this->sort_order) {
            $this->set('sort_order', Denteva_Model_Cart::i()->factory()->select('(max(sort_order))', 'sort_order')->find_one()->sort_order+1);
        }
        return true;
    }

    public function afterSave()
    {
        parent::afterSave();
        Denteva_Model_Cart::sessionCartId($this->id);
        if (Denteva_Model_User::i()->isLoggedIn()) {
            Denteva_Model_User::i()->sessionUser()->set('session_cart_id', $this->id, null);
        }
        return $this;
    }

    public function updateUsers($users, $deleted=null)
    {
        if ($deleted) {
            $deleted = (array)$deleted;
            if (empty($deleted[0])) {
                $deleted = array_keys($deleted);
            }
            Denteva_Model_CartUser::delete_many(array('cart_id'=>$this->id, 'user_id'=>$deleted));
        }
        if ($users) {
            $userIds = array();
            foreach ($users as $i=>$u) {
                $userIds[] = $u->user_id;
            }
            $oldUsers = Denteva_Model_CartUser::factory()
                ->where_complex(array('cart_id'=>$this->id, 'user_id'=>$userIds))
                ->find_many_assoc('user_id');
            foreach ($users as $u) {
                if (empty($u->user_id)) {
                    continue;
                }
                $u->cart_id = $this->id;
                unset($u->id);
                if (empty($oldUsers[$u->user_id])) {
                    $user = Denteva_Model_CartUser::create($u)->save();
                } elseif ($oldUsers[$u->user_id]->as_array()!=$data) {
                    $user = $oldUsers[$u->user_id]->set($u)->save();
                }
            }
        }
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
            $this->subtotal += $item->product()->price*$item->qty;
        }
        return $this;
    }

    public function urlHash($id)
    {
        return '/carts/items/'.$id;
    }
}
