<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Model_PromoProductPrice
 *
 * Required mostly to associate product price rows with promotions, to remove when not valid anymore
 * @deprecated by ProductPrice - delete after testing
 */
class Sellvana_Promo_Model_PromoProductPrice extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_product_price';

}
