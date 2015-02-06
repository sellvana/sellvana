<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Model_PromoMedia
 *
 * @property int $id
 * @property int $promo_id
 * @property int $file_id
 * @property int $manuf_vendor_id
 * @property string $promo_status
 */
class FCom_Promo_Model_PromoMedia extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_media';
}