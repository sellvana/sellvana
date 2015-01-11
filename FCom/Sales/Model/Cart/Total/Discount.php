<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_Total_Discount extends FCom_Sales_Model_Cart_Total_Abstract
{
    protected $_label = 'Discount';
    protected $_cartField = 'discount_amount';
    protected $_sortOrder = 70;
}