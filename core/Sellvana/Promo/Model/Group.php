<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Model_Group
 *
 * @property int $id
 * @property int $promo_id
 * @property string $group_type enum('buy','get')
 * @property string $group_name
 *
 * @deprecated
 *
 * DI
 * @property Sellvana_Promo_Model_Product $Sellvana_Promo_Model_Product
 */
class Sellvana_Promo_Model_Group extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_group';

    /**
     * @return Sellvana_Promo_Model_Product[]|null
     */
    public function products()
    {
        return $this->Sellvana_Promo_Model_PromoProduct->orm()->where('group_id', $this->id())->find_many();
    }
}
