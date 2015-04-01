<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_SalesTax_Model_ProductTax extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_product_tax';
    protected static $_origClass = __CLASS__;

    public function getProductTaxClassIds($product)
    {
        return $this->orm()->where('product_id', $product->id())->find_many_assoc('id', 'product_class_id');
    }
}