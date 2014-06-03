<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Promo_Model_Group extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_group';

    public function products()
    {
        return FCom_Promo_Model_Product::i()->orm()->where('group_id', $this->id)->find_many();
    }
}