<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_Payment_State extends FCom_Core_Model_Abstract_State_Context
{
    const OVERALL = 'overall',
        CUSTOM = 'custom';

    protected $_stateLabels = [
        self::OVERALL => 'Overall',
        self::CUSTOM => 'Custom',
    ];

    /**
     * Order linked
     *
     * @var Sellvana_Sales_Model_Order_Payment
     */
    protected $_model;

    /**
     * Default classes for each type of payment state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        self::OVERALL => 'Sellvana_Sales_Model_Order_Payment_State_Overall',
        self::CUSTOM => 'Sellvana_Sales_Model_Order_Payment_State_Custom',
    ];

    /**
     * @return Sellvana_Sales_Model_Order_Payment_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject(self::OVERALL);
    }

    /**
     * @return Sellvana_Sales_Model_Order_Payment_State_Custom
     * @throws BException
     */
    public function custom()
    {
        return $this->_getStateObject(self::CUSTOM);
    }

}
