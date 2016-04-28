<?php

class Sellvana_Sales_Model_Order_Shipment_Package_State extends FCom_Core_Model_Abstract_State_Context
{
    const OVERALL = 'overall';

    protected $_stateLabels = [
        self::OVERALL => 'Overall',
    ];

    /**
     * Order linked
     *
     * @var Sellvana_Sales_Model_Order_Shipment
     */
    protected $_model;

    /**
     * Default classes for each type of shipment state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        self::OVERALL => 'Sellvana_Sales_Model_Order_Shipment_Package_State_Overall',
    ];

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_Package_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject(self::OVERALL);
    }
}
