<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Model_Product
 *
 * @property int $id
 * @property int $promo_id
 * @property int $group_id
 * @property int $product_id
 * @property int $qty
 */
class FCom_Promo_Model_Product extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_product';
}