<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property FCom_ProductCompare_Model_SetItem FCom_ProductCompare_Model_SetItem
 * @property FCom_Customer_Model_Customer FCom_Customer_Model_Customer
 */
class FCom_ProductCompare_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_compare_set';
    protected static $_origClass = __CLASS__;

    protected $items = null;
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
     * @return FCom_ProductCompare_Model_Set|null
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
                if(!$set->id()){
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
        if($this->id()){
            $items = $this->FCom_ProductCompare_Model_SetItem->orm()->select('product_id')->where('set_id', $this->id())->find_many();
            foreach ($items as $item) {
                /** @var FCom_ProductCompare_Model_SetItem $item */
                $ids[] = $item->get('product_id');
            }
        }
        return $ids;
    }
}
