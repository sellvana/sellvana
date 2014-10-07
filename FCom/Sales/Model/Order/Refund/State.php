<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State extends FCom_Core_Model_Abstract_State_Context
{
    /**
     * Order linked
     *
     * @var FCom_Sales_Model_Order_Payment
     */
    protected $_model;

    /**
     * Default classes for each type of payment state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        'overall' => 'FCom_Sales_Model_Order_Payment_State_Overall',
        'custom' => 'FCom_Sales_Model_Order_Payment_State_Custom',
    ];

    public function overall()
    {
        return $this->_getStateObject('overall');
    }

    public function custom()
    {
        return $this->_getStateObject('custom');
    }

}
