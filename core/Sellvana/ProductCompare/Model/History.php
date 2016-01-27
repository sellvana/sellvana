<?php

/**
 * Class Sellvana_ProductCompare_Model_History
 *
 * DI
 * @property Sellvana_ProductCompare_Model_SetItem Sellvana_ProductCompare_Model_SetItem
 * @property Sellvana_Customer_Model_Customer Sellvana_Customer_Model_Customer
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 */
class Sellvana_ProductCompare_Model_History extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_compare_history';
    protected static $_origClass = __CLASS__;

    public function addItem(Sellvana_ProductCompare_Model_SetItem $item, Sellvana_ProductCompare_Model_Set $set)
    {
        $pId = $item->get('product_id');
        $custId = $set->get('customer_id');
        $token = $set->get('cookie_token');

        /** @var Sellvana_ProductCompare_Model_History $exists */
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
        $token = $this->BRequest->cookie('compare');
        $custId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
        if (!$token && !$custId) {
            return [];
        }

        $orm = $this->Sellvana_Catalog_Model_Product->orm('p')
            ->join('Sellvana_ProductCompare_Model_History', ['h.product_id', '=', 'p.id'], 'h')
            ->order_by_desc('h.update_at')
            ->limit($cnt);

        if ($token) {
            $orm->where('h.cookie_token', $token);
        }
        if ($custId) {
            $orm->where('h.customer_id', $custId);
        }

        $result = $orm->find_many();
        return $result;
    }
}