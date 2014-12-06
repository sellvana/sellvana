<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Refund_State extends FCom_Core_Model_Abstract_State_Context
{
    const OVERALL = 'overall',
        CUSTOM = 'custom';
    
    /**
     * Order linked
     *
     * @var FCom_Sales_Model_Order_Refund
     */
    protected $_model;

    /**
     * Default classes for each type of payment state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        self::OVERALL => 'FCom_Sales_Model_Order_Payment_State_Overall',
        self::CUSTOM => 'FCom_Sales_Model_Order_Payment_State_Custom',
    ];

    /**
     * @return FCom_Sales_Model_Order_Payment_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject(self::OVERALL);
    }

    /**
     * @return FCom_Sales_Model_Order_Payment_State_Custom
     * @throws BException
     */
    public function custom()
    {
        return $this->_getStateObject(self::CUSTOM);
    }

}
