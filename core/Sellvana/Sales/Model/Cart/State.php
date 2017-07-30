<?php

class Sellvana_Sales_Model_Cart_State extends FCom_Core_Model_Abstract_State_Context
{
    const OVERALL = 'overall',
        PAYMENT = 'payment';

    protected $_stateLabels = [
        self::OVERALL => (('Overall')),
        self::PAYMENT => (('Payment')),
    ];

    /**
     * Order linked
     *
     * @var Sellvana_Sales_Model_Cart
     */
    protected $_model;

    /**
     * Default classes for each type of order state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        self::OVERALL => 'Sellvana_Sales_Model_Cart_State_Overall',
        self::PAYMENT => 'Sellvana_Sales_Model_Cart_State_Payment',
    ];

    /**
     * @return Sellvana_Sales_Model_Cart_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject(self::OVERALL);
    }

    /**
     * @return Sellvana_Sales_Model_Cart_State_Payment
     * @throws BException
     */
    public function payment()
    {
        return $this->_getStateObject(self::PAYMENT);
    }
}
