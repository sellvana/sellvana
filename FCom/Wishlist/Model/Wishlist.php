<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Wishlist_Model_Wishlist
 *
 * @property int $id
 * @property int $customer_id
 * @property string $cookie_token
 * @property string $remote_ip
 * @property datetime $create_at
 * @property datetime $update_at
 *
 * DI
 * @property FCom_Wishlist_Model_WishlistItem $FCom_Wishlist_Model_WishlistItem
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 */
class FCom_Wishlist_Model_Wishlist extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_wishlist';
    protected static $_origClass = __CLASS__;

    protected $items = null;
    protected static $_sessionWishlist = null;

    /**
     * @param bool $createAnonymousIfNeeded
     * @return bool|FCom_Wishlist_Model_Wishlist
     * @throws BException
     */
    public function sessionWishlist($createAnonymousIfNeeded = false)
    {
        if (!static::$_sessionWishlist) {
            $customer = $this->FCom_Customer_Model_Customer->sessionUser();
            if ($customer) {
                $wishlist = $this->loadOrCreate(["customer_id" => $customer->id()]);
                $id = $wishlist->id();
                if(empty($id)){
                    $wishlist->save(); // make sure wishlist has an ID
                }
            } else {
                $cookieToken = $this->BRequest->cookie('wishlist');
                if ($cookieToken) {
                    $wishlist = $this->load($cookieToken, 'cookie_token');
                    if (!$wishlist && !$createAnonymousIfNeeded) {
                        $this->BResponse->cookie('wishlist', false);
                        return false;
                    }
                }
                if (empty($wishlist)) {
                    if ($createAnonymousIfNeeded) {
                        $cookieToken = $this->BUtil->randomString(32);
                        $wishlist = $this->create(['cookie_token' => (string)$cookieToken])->save();
                        $ttl = $this->BConfig->get('modules/FCom_Wishlist/cookie_token_ttl_days') * 86400;
                        $this->BResponse->cookie('wishlist', $cookieToken, $ttl);
                    } else {
                        return false;
                    }
                }
            }

            static::$_sessionWishlist = $wishlist;
        }
        return static::$_sessionWishlist;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('remote_ip', $this->BRequest->ip());

        return true;
    }

    /**
     * @param bool $refresh
     * @return null
     */
    public function items($refresh = false)
    {
        if (!$this->items || $refresh) {
            $items = $this->FCom_Wishlist_Model_WishlistItem->orm()->where('wishlist_id', $this->id())->find_many_assoc();
            foreach ($items as $ik => $item) {
                if (!$item->product()) {
                    $this->removeItem($item);
                    unset($items[$ik]);
                }
            }
            $this->items = $items;
        }
        return $this->items;
    }

    /**
     * @param $pId
     * @return bool
     */
    public function hasItem($pId)
    {
        $items = $this->items();
        foreach ($items as $item) {
            if ($item->get('product_id') == $pId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $productId
     * @return $this
     */
    public function addItem($productId)
    {
        $item = $this->FCom_Wishlist_Model_WishlistItem->loadWhere(['wishlist_id' => $this->id(), 'product_id' => $productId]);
        if (!$item) {
            $item = $this->FCom_Wishlist_Model_WishlistItem->create([
                'wishlist_id' => $this->id(),
                'product_id' => $productId,
            ])->save();
        }

        return $this;
    }

    /**
     * @param FCom_Wishlist_Model_WishlistItem|int $item
     * @return $this
     */
    public function removeItem($item)
    {
        if (is_numeric($item)) {
            $item = $this->FCom_Wishlist_Model_WishlistItem->loadWhere(['wishlist_id' => $this->id(), 'id' => $item]);
        }
        if ($item) {
            unset($this->items[$item->id()]);
            $item->delete();
        }
        return $this;
    }

    /**
     * @param $productId
     * @return $this
     */
    public function removeProduct($productId)
    {
        $item = $this->FCom_Wishlist_Model_WishlistItem->loadWhere(['wishlist_id' => $this->id(), 'product_id' => $productId]);
        $this->removeItem($item);
        return $this;
    }

    /**
     * @param FCom_Wishlist_Model_Wishlist $sourceWishlist
     * @return $this
     */
    public function merge($sourceWishlist)
    {
        foreach ($sourceWishlist->items() as $item) {
            $this->addItem($item->product_id);
        }
        $this->FCom_Wishlist_Model_WishlistItem->delete_many(['wishlist_id' => $sourceWishlist->id()]);
        $sourceWishlist->set([
            'cookie_token' => null,
        ])->save();
        return $this;
    }

    /**
     * @throws BException
     */
    public function onUserLogin()
    {
        // get cookie wishlist token
        $cookieToken = $this->BRequest->cookie('wishlist');
        // if no local wishlist, skip
        if (!$cookieToken) {
            return;
        }
        // load just logged in customer
        $customer = $this->FCom_Customer_Model_Customer->sessionUser();
        // something wrong, abort abort!
        if (!$customer) {
            return;
        }
        // try to load customer cart which is new (not abandoned or converted to order)
        $cookieWishlist = $this->load($cookieToken, 'cookie_token');
        // cookie wishlist doesn't exist or has customer id which doesn't match logged in customer
        if (!$cookieWishlist || ($cookieWishlist->customer_id && $cookieWishlist->customer_id !== $customer->id())) {
            $this->BResponse->cookie('wishlist', false);
            return;
        }
        // load customer's wishlist
        $custWishlist = $this->load($customer->id(), 'customer_id');
        if (!$custWishlist) {
            // if no customer wishlist, make cookie wishlist customer's
            $cookieWishlist->set([
                'customer_id' => $customer->id(),
                'cookie_token' => null,
            ])->save();
        } else {
            // otherwise merge cookie wishlist into customer wishlist
            $custWishlist->merge($cookieWishlist);
        }
        // clear cookie token
        $this->BResponse->cookie('wishlist', false);
    }
}
