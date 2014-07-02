<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ProductCompare_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_compare_set';
    protected static $_origClass = __CLASS__;

    protected $items = null;
    protected static $_sessionSet = null;

    public function sessionSet($createAnonymousIfNeeded = false)
    {
        if (!static::$_sessionSet) {
            $customer = $this->FCom_Customer_Model_Customer->sessionUser();
            if ($customer) {
                $set = $this->loadOrCreate(["customer_id" => $customer->id()]);
            } else {
                $cookieToken = $this->BRequest->cookie('compare');
                if ($cookieToken) {
                    $set = $this->load($cookieToken, 'cookie_token');
                    if (!$set && !$createAnonymousIfNeeded) {
                        $this->BResponse->cookie('compare', false);
                        return false;
                    }
                }
                if (empty($wishlist)) {
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
}