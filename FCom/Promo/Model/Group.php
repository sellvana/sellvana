<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Model_Group
 *
 * @property int $id
 * @property int $promo_id
 * @property string $group_type enum('buy','get')
 * @property string $group_name
 *
 * DI
 * @property FCom_Promo_Model_Product $FCom_Promo_Model_Product
 */
class FCom_Promo_Model_Group extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_group';

    /**
     * @return FCom_Promo_Model_Product[]|null
     */
    public function products()
    {
        return $this->FCom_Promo_Model_Product->orm()->where('group_id', $this->id())->find_many();
    }
}
