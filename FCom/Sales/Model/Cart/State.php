<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_State extends FCom_Core_Model_Abstract_State_Context
{
    /**
     * Order linked
     *
     * @var FCom_Sales_Model_Cart
     */
    protected $_model;

    /**
     * Default classes for each type of order state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        'overall' => 'FCom_Sales_Model_Cart_State_Overall',
        'payment' => 'FCom_Sales_Model_Cart_State_Payment',
    ];

    /**
     * @return FCom_Sales_Model_Cart_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject('overall');
    }

    /**
     * @return FCom_Sales_Model_Cart_State_Payment
     * @throws BException
     */
    public function payment()
    {
        return $this->_getStateObject('payment');
    }
}
