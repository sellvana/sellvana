<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Model_PromoProduct
 *
 * @property int $id
 * @property int $promo_id
 * @property int $group_id
 * @property int $product_id
 * @property int $qty
 *
 * @deprecated
 */
class Sellvana_Promo_Model_PromoProduct extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_product';
}