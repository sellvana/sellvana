<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_State extends FCom_Core_Model_Abstract_State_Context
{
    const OVERALL = 'overall',
        PAYMENT = 'payment';

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
        self::OVERALL => 'FCom_Sales_Model_Cart_State_Overall',
        self::PAYMENT => 'FCom_Sales_Model_Cart_State_Payment',
    ];

    /**
     * @return FCom_Sales_Model_Cart_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject(self::OVERALL);
    }

    /**
     * @return FCom_Sales_Model_Cart_State_Payment
     * @throws BException
     */
    public function payment()
    {
        return $this->_getStateObject(self::PAYMENT);
    }
}
