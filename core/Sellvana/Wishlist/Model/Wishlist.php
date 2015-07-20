<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Wishlist_Model_Wishlist
 *
 * @property int $id
 * @property int $customer_id
 * @property string $cookie_token
 * @property string $remote_ip
 * @property datetime $create_at
 * @property datetime $update_at
 *
 * DI
 * @property Sellvana_Wishlist_Model_WishlistItem $Sellvana_Wishlist_Model_WishlistItem
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */
class Sellvana_Wishlist_Model_Wishlist extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_wishlist';
    protected static $_origClass = __CLASS__;

    protected $items = null;
    protected static $_sessionWishlist = null;

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['cookie_token'],
        'related'    => ['customer_id' => 'Sellvana_Customer_Model_Customer.id'],
    ];

    /**
     * @param bool $createAnonymousIfNeeded
     * @return bool|Sellvana_Wishlist_Model_Wishlist
     * @throws BException
     */
    public function sessionWishlist($createAnonymousIfNeeded = false)
    {
        if (!static::$_sessionWishlist) {
            $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
            if ($customer) {
                $wishlist = $this->loadOrCreate(["customer_id" => $customer->id(), 'is_default' => 1]);
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
                        $ttl = $this->BConfig->get('modules/Sellvana_Wishlist/cookie_token_ttl_days') * 86400;
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
     * @param bool $clean
     * @return null
     */
    public function items($refresh = false, $clean = false)
    {
        if (null === $this->items || $refresh) {
            $orm = $this->Sellvana_Wishlist_Model_WishlistItem->orm('wi')->where('wishlist_id', $this->id());
            if ($clean) {
                $orm->join('Sellvana_Catalog_Model_Product', ['p.id', '=', 'wi.product_id'], 'p')
                    ->select('wi.*')->select('p.id', 'product_id');
            }
            $items = $orm->find_many_assoc();
            if ($clean) {
                foreach ($items as $ik => $item) {
                    if (!$item->get('product_id')) {
                        $this->removeItem($item);
                        unset($items[$ik]);
                    }
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
        $item = $this->Sellvana_Wishlist_Model_WishlistItem->loadWhere(['wishlist_id' => $this->id(), 'product_id' => $productId]);
        if (!$item) {
            $item = $this->Sellvana_Wishlist_Model_WishlistItem->create([
                'wishlist_id' => $this->id(),
                'product_id' => $productId,
            ])->save();
        }

        return $this;
    }

    public function moveItemToCart($item)
    {
        if (is_numeric($item)) {
            $item = $this->Sellvana_Wishlist_Model_WishlistItem->loadWhere(['wishlist_id' => $this->id(), 'id' => $item]);
        }
        //$this->Sellvana_Sales_Main->workflowAction('customerAddsProductToCart', [[$item->get('product_id')]]);
        //TODO: implement variants in wishlist
        $this->Sellvana_Sales_Model_Cart->sessionCart()->addProduct($item->get('product_id'));
        $this->removeItem($item);
        return $this;;
    }

    /**
     * @param Sellvana_Wishlist_Model_WishlistItem|int $item
     * @return $this
     */
    public function removeItem($item)
    {
        if (is_numeric($item)) {
            $item = $this->Sellvana_Wishlist_Model_WishlistItem->loadWhere(['wishlist_id' => $this->id(), 'id' => $item]);
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
        $item = $this->Sellvana_Wishlist_Model_WishlistItem->loadWhere(['wishlist_id' => $this->id(), 'product_id' => $productId]);
        $this->removeItem($item);
        return $this;
    }

    /**
     * @param Sellvana_Wishlist_Model_Wishlist $sourceWishlist
     * @return $this
     */
    public function merge($sourceWishlist)
    {
        foreach ($sourceWishlist->items() as $item) {
            $this->addItem($item->product_id);
        }
        $this->Sellvana_Wishlist_Model_WishlistItem->delete_many(['wishlist_id' => $sourceWishlist->id()]);
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
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
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
        $custWishlist = $this->loadWhere(['customer_id' => $customer->id(), 'is_default' => 1]);
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
