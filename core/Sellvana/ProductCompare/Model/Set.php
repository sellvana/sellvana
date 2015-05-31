<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ProductCompare_Model_Set
 *
 * @property int $id
 * @property string $cookie_token
 * @property int $customer_id
 * @property string $create_at
 * @property string $update_at
 *
 * DI
 * @property Sellvana_ProductCompare_Model_SetItem Sellvana_ProductCompare_Model_SetItem
 * @property Sellvana_Customer_Model_Customer Sellvana_Customer_Model_Customer
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_ProductCompare_Model_History $Sellvana_ProductCompare_Model_History
 */
class Sellvana_ProductCompare_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_table     = 'fcom_compare_set';
    protected static $_origClass = __CLASS__;

    protected        $items       = null;
    protected static $_sessionSet = null;

    /**
     * @var array an array of details about products belonging to the set
     */
    protected $_productDetails;

    /**
     * Get current user compare products set
     *
     * If user is registered, fetch set for user id,
     * if not use cookie token.
     *
     * If $createAnonymousIfNeeded is true, a compare set for non registered user will be created.
     *
     * @param bool $createAnonymousIfNeeded
     * @return Sellvana_ProductCompare_Model_Set|false
     * @throws BException
     */
    public function sessionSet($createAnonymousIfNeeded = false)
    {
        if (!static::$_sessionSet) {
            $set = null;
            /** @var Sellvana_Customer_Model_Customer $customer */
            $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
            if ($customer) {
                $set = $this->loadOrCreate(["customer_id" => $customer->id()]);
                if (!$set->id()) {
                    $set->save();
                }
            } else {
                $cookieToken = $this->BRequest->cookie('compare');
                if ($cookieToken) {
                    $set = $this->load($cookieToken, 'cookie_token');
                    if (!$set && !$createAnonymousIfNeeded) {
                        $this->BResponse->cookie('compare', false);
                        return false;
                    }
                }
                if (empty($set)) {
                    if ($createAnonymousIfNeeded) {
                        $cookieToken = $this->BUtil->randomString(32);
                        $set = $this->create(['cookie_token' => (string)$cookieToken])->save();
                        $ttl = $this->BConfig->get('modules/Sellvana_ProductCompare/cookie_token_ttl_days') * 86400;
                        $this->BResponse->cookie('compare', $cookieToken, $ttl);
                    } else {
                        return false;
                    }
                }
            }

            static::$_sessionSet = $set;
        }
        return static::$_sessionSet;
    }

    /**
     * @return Sellvana_ProductCompare_Model_SetItem[]
     */
    public function items()
    {
        #return [];
        if (null === $this->items) {
            $this->items = $this->Sellvana_ProductCompare_Model_SetItem->orm()
                ->where('set_id', $this->id())->find_many();
        }
        return $this->items;
    }

    /**
     * Fetch and return products compared in current set
     * @return array
     */
    public function getCompareProductIds()
    {
        $ids = [];
        if ($this->id()) {
            $items = $this->items();
            foreach ($items as $item) {
                /** @var Sellvana_ProductCompare_Model_SetItem $item */
                $ids[] = $item->get('product_id');
            }
        }
        return $ids;
    }

    /**
     * @param int|Sellvana_Catalog_Model_Product $id
     * @return array
     * @throws BException
     */
    public function getProductDetails($id, $thumbSize = 35)
    {
        $details = [];
        /** @var Sellvana_Catalog_Model_Product $product */
        if (is_numeric($id)) {
            $product = $this->Sellvana_Catalog_Model_Product->load($id);
        } else {
            $product = $id;
        }
        if ($product) {
            $details = [
                'id' => $product->id(),
                'src' => $product->thumbUrl($thumbSize),
                'alt' => $product->getName(),
            ];
        }
        return $details;
    }

    /**
     * @param bool $refresh
     * @return array
     */
    public function getCompareProductsDetails($refresh = false)
    {
        $this->BDebug->debug(__METHOD__ . ':' . $refresh);
        if (empty($this->_productDetails) || $refresh) {
            /** @var Sellvana_Catalog_Model_Product[] $products */
            $products = $this->Sellvana_Catalog_Model_Product->orm('p')->select('p.*')
                ->join('Sellvana_ProductCompare_Model_SetItem', ['si.product_id', '=', 'p.id'], 'si')
                ->where('si.set_id', $this->id())->find_many();
            $details = [];
            foreach ($products as $p) {
                $details[] = $this->getProductDetails($p);
            }
            $this->_productDetails = $details;
        }
        return $this->_productDetails;
    }

    /**
     * @param int $pId
     * @return Sellvana_ProductCompare_Model_SetItem|false
     */
    public function findSetItem($pId)
    {
        foreach ($this->items() as $item) {
            if ($item->get('product_id') === $pId) {
                return $item;
            }
        }
        return false;
    }

    /**
     * @param $id
     * @return Sellvana_ProductCompare_Model_SetItem
     * @throws BException
     */
    public function addItem($id)
    {
        $data = [
            'set_id' => $this->id(),
            'product_id' => $id
        ];
        $item = $this->findSetItem($id);

        if (!$item) {
            $item = $this->Sellvana_ProductCompare_Model_SetItem->create($data);
            $item->set('create_at', BDb::now())->save();
        }

        $this->Sellvana_ProductCompare_Model_History->addItem($item, $this);

        return $item;
    }

    /**
     * @param $id
     * @return bool
     */
    public function rmItem($id)
    {
        $item = $this->findSetItem($id);
        if($item){
            $item->delete();
            return true;
        }
        return false;
    }

    /**
     * Clear all compare items
     */
    public function clearSet()
    {
        try {
            $setItems = $this->items();
            foreach ($setItems as $item) {
                $item->delete();
            }
        } catch(Exception $e) {
            $this->BDebug->logException($e);
            return false;
        }
        return true;
    }
}
