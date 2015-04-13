<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_SalesTax_Model_ProductClass extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_product_class';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['title'],
    ];

    public function getAllTaxClasses()
    {
        return $this->orm()->find_many_assoc('id', 'title');
    }
}
