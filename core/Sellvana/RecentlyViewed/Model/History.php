<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_RecentlyViewed_Model_History
 *
 * DI
 * @property Sellvana_Customer_Model_Customer Sellvana_Customer_Model_Customer
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia $Sellvana_Catalog_Model_ProductMedia
 */
class Sellvana_RecentlyViewed_Model_History extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_recentlyviewed_history';
    protected static $_origClass = __CLASS__;

    public function addItem($product)
    {
        $pId = $product->id();
        $custId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
        $token = $this->BRequest->cookie('recently_viewed');
        if (!$token) {
            $token = $this->BUtil->randomString(40);
            $ttl = $this->BConfig->get('modules/Sellvana_RecentlyViewed/cookie_token_ttl_days') * 86400;
            $this->BResponse->cookie('recently_viewed', $token, $ttl);
        }

        /** @var Sellvana_RecentlyViewed_Model_History $exists */
        $exists = $this->orm()
            ->where_raw('product_id=? and (customer_id=? or cookie_token=?)', [$pId, $custId, $token])->find_one();
        if ($exists) {
            $exists->set('update_at', $this->BDb->now())->save();
        } else {
            $this->create([
                'product_id' => $pId,
                'customer_id' => $custId,
                'cookie_token' => $token,
                'update_at' => $this->BDb->now(),
            ])->save();
        }
        return $this;
    }

    public function getVisitorProducts($cnt = 6)
    {
        $token = $this->BRequest->cookie('recently_viewed');
        $custId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
        if (!$token && !$custId) {
            return [];
        }

        $orm = $this->Sellvana_Catalog_Model_Product->orm('p')
            ->select('p.*')
            ->join('Sellvana_RecentlyViewed_Model_History', ['h.product_id', '=', 'p.id'], 'h')
            ->order_by_desc('h.update_at')
            ->limit($cnt);

        if ($token) {
            $orm->where('h.cookie_token', $token);
        }
        if ($custId) {
            $orm->where('h.customer_id', $custId);
        }

        $products = $orm->find_many();
        $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages($products);
        return $products;
    }
}