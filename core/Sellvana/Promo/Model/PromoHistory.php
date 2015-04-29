<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Model_PromoHistory
 *
 * @property int $id
 * @property int $cart_id
 * @property int $promo_id
 * @property string $update_at
 */
class Sellvana_Promo_Model_PromoHistory extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_history';

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['promo_id', 'cart_id', 'coupon_id'],
        'related'    => [
            'promo_id'      => 'Sellvana_Promo_Model_Promo.id',
            'cart_id'       => 'Sellvana_Sales_Model_Cart.id',
            'coupon_id'     => 'Sellvana_Promo_Model_PromoCoupon.id',
            'admin_user_id' => 'FCom_Admin_Model_User.id',
            'customer_id'   => 'Sellvana_Customer_Model_Customer.id',
            'order_id'      => 'Sellvana_Sales_Model_Order.id'
        ],
    ];
}
