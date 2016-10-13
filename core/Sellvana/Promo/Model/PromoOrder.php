<?php

/**
 * Class Sellvana_Promo_Model_PromoOrder
 *
 * @property int $id
 * @property int $order_id
 * @property int $promo_id
 * @property string $update_at
 */
class Sellvana_Promo_Model_PromoOrder extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_order';

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['promo_id', 'order_id', 'coupon_id'],
        'related'    => [
            'promo_id'           => 'Sellvana_Promo_Model_Promo.id',
            'order_id'           => 'Sellvana_Sales_Model_Order.id',
            'coupon_id'          => 'Sellvana_Promo_Model_PromoCoupon.id',
            'free_order_item_id' => 'Sellvana_Sales_Model_Order_Item.id',
            'customer_id'        => 'Sellvana_Customer_Model_Customer.id'
        ],
    ];
}
