<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Model_PromoMedia
 *
 * @property int $id
 * @property int $promo_id
 * @property int $file_id
 * @property int $manuf_vendor_id
 * @property string $promo_status
 */
class Sellvana_Promo_Model_PromoMedia extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_media';
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['promo_id', 'file_id', 'promo_status'],
        'related'    => ['promo_id' => 'Sellvana_Promo_Model_Promo.id', 'file_id' => 'FCom_Core_Model_MediaLibrary.id'],
    ];
}
