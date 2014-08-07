<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property FCom_ProductCompare_Model_SetItem FCom_ProductCompare_Model_SetItem
 * @property FCom_Customer_Model_Customer FCom_Customer_Model_Customer
 * @property FCom_Catalog_Model_Product FCom_Catalog_Model_Product
 */
class FCom_ProductCompare_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_table     = 'fcom_compare_set';
    protected static $_origClass = __CLASS__;

    protected        $items       = null;
    protected static $_sessionSet = null;

    /**
     * Get current user compare products set
     *
     * If user is registered, fetch set for user id,
     * if not use cookie token.
     *
     * If $createAnonymousIfNeeded is true, a compare set for non registered user will be created.
     *
     * @param bool $createAnonymousIfNeeded
     * @return FCom_ProductCompare_Model_Set|false
     * @throws BException
     */
    public function sessionSet($createAnonymousIfNeeded = false)
    {
        if (!static::$_sessionSet) {
            $set = null;
            /** @var FCom_Customer_Model_Customer $customer */
            $customer = $this->FCom_Customer_Model_Customer->sessionUser();
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
                        $ttl = $this->BConfig->get('modules/FCom_ProductCompare/cookie_token_ttl_days') * 86400;
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
     * Fetch and return products compared in current set
     * @return array
     */
    public function getCompareIds()
    {
        $ids = [];
        if ($this->id()) {
            $items = $this->FCom_ProductCompare_Model_SetItem->orm()->select('product_id')->where('set_id', $this->id())
                                                             ->find_many();
            foreach ($items as $item) {
                /** @var FCom_ProductCompare_Model_SetItem $item */
                $ids[] = $item->get('product_id');
            }
        }
        return $ids;
    }

    public function getCompareProductsDetails()
    {
        $details = [];
        $productIds = $this->getCompareIds();
        foreach ($productIds as $id) {
            $pDetails = $this->getProductDetails($id);
            if (!empty($pDetails)) {
                $details[] = $pDetails;
            }

        }
        return $details;
    }

    /**
     * @param $id
     * @return array
     * @throws BException
     */
    public function getProductDetails($id)
    {
        $details = [];
        /** @var FCom_Catalog_Model_Product $product */
        $product = $this->FCom_Catalog_Model_Product->load($id);
        if ($product) {
            $details = [
                'id' => $id,
                'src' => $product->imageUrl(),
                'alt' => $product->get('product_name'),
            ];
        }
        return $details;
    }

    public function getCompareProductsDetailsJson()
    {
        $details = $this->getCompareProductsDetails();
        return $this->BUtil->toJson($details);
    }

    /**
     * @param $id
     * @return FCom_ProductCompare_Model_SetItem
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
            $item = $this->FCom_ProductCompare_Model_SetItem->create($data);
            $item->set('create_at', BDb::now())->save();
        }
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
     * @param $id
     * @return FCom_ProductCompare_Model_SetItem|false
     */
    protected function findSetItem($id)
    {
        $data = [
            'set_id' => $this->id(),
            'product_id' => $id
        ];

        $item = $this->FCom_ProductCompare_Model_SetItem->orm()->where($data)->find_one();
        return $item;
    }

}
