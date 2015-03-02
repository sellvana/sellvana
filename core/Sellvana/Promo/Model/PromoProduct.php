<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Model_PromoProduct
 *
 * @property int $id
 * @property int $promo_id
 * @property int $product_id
 * @property int $qty
 * @property boolean $calc_status
 */
class Sellvana_Promo_Model_PromoProduct extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_product';
}