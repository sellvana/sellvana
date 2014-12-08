<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State extends FCom_Core_Model_Abstract_State_Context
{
    const OVERALL = 'overall',
        PROCESSOR = 'processor',
        CHILDREN = 'children',
        CUSTOM = 'custom';
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
        self::OVERALL => 'FCom_Sales_Model_Order_Payment_State_Overall',
        self::PROCESSOR => 'FCom_Sales_Model_Order_Payment_State_Processor',
        self::CHILDREN => 'FCom_Sales_Model_Order_Payment_State_Children',
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
     * @return FCom_Sales_Model_Order_Payment_State_Processor
     * @throws BException
     */
    public function processor()
    {
        return $this->_getStateObject(self::PROCESSOR);
    }

    /**
     * @return FCom_Sales_Model_Order_Payment_State_Children
     * @throws BException
     */
    public function children()
    {
        return $this->_getStateObject(self::CHILDREN);
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
