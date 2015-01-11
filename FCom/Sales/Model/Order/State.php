<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State extends FCom_Core_Model_Abstract_State_Context
{
    const OVERALL = 'overall',
        DELIVERY = 'delivery',
        PAYMENT = 'payment',
        COMMENT = 'comment',
        CUSTOM = 'custom';

    /**
     * Order linked
     *
     * @var FCom_Sales_Model_Order
     */
    protected $_model;

    /**
     * Default classes for each type of order state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        self::OVERALL => 'FCom_Sales_Model_Order_State_Overall',
        self::DELIVERY => 'FCom_Sales_Model_Order_State_Delivery',
        self::PAYMENT => 'FCom_Sales_Model_Order_State_Payment',
        self::COMMENT => 'FCom_Sales_Model_Order_State_Comment',
        self::CUSTOM => 'FCom_Sales_Model_Order_State_Custom',
    ];

    /**
     * @return FCom_Sales_Model_Order_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject(self::OVERALL);
    }

    /**
     * @return FCom_Sales_Model_Order_State_Delivery
     * @throws BException
     */
    public function delivery()
    {
        return $this->_getStateObject(self::DELIVERY);
    }

    /**
     * @return FCom_Sales_Model_Order_State_Payment
     * @throws BException
     */
    public function payment()
    {
        return $this->_getStateObject(self::PAYMENT);
    }

    /**
     * @return FCom_Sales_Model_Order_State_Comment
     * @throws BException
     */
    public function comment()
    {
        return $this->_getStateObject(self::COMMENT);
    }

    /**
     * @return FCom_Sales_Model_Order_State_Custom
     * @throws BException
     */
    public function custom()
    {
        return $this->_getStateObject(self::CUSTOM);
    }

}
