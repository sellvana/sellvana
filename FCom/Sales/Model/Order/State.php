<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State extends FCom_Core_Model_Abstract_State_Context
{
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
        'overall' => 'FCom_Sales_Model_Order_State_Overall',
        'delivery' => 'FCom_Sales_Model_Order_State_Delivery',
        'payment' => 'FCom_Sales_Model_Order_State_Payment',
        'comment' => 'FCom_Sales_Model_Order_State_Comment',
        'custom' => 'FCom_Sales_Model_Order_State_Custom',
    ];

    /**
     * @return FCom_Sales_Model_Order_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject('overall');
    }

    /**
     * @return FCom_Sales_Model_Order_State_Delivery
     * @throws BException
     */
    public function delivery()
    {
        return $this->_getStateObject('delivery');
    }

    /**
     * @return FCom_Sales_Model_Order_State_Payment
     * @throws BException
     */
    public function payment()
    {
        return $this->_getStateObject('payment');
    }

    /**
     * @return FCom_Sales_Model_Order_State_Comment
     * @throws BException
     */
    public function comment()
    {
        return $this->_getStateObject('comment');
    }

    /**
     * @return FCom_Sales_Model_Order_State_Custom
     * @throws BException
     */
    public function custom()
    {
        return $this->_getStateObject('custom');
    }

}
