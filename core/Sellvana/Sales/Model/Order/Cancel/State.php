<?php

class Sellvana_Sales_Model_Order_Cancel_State extends FCom_Core_Model_Abstract_State_Context
{
    const OVERALL = 'overall',
        CUSTOM = 'custom';

    protected $_stateLabels = [
        self::OVERALL => (('Overall')),
        self::CUSTOM => (('Custom')),
    ];

    /**
     * Order linked
     *
     * @var Sellvana_Sales_Model_Order_Cancel
     */
    protected $_model;

    /**
     * Default classes for each type of cancel state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        self::OVERALL => 'Sellvana_Sales_Model_Order_Cancel_State_Overall',
        self::CUSTOM => 'Sellvana_Sales_Model_Order_Cancel_State_Custom',
    ];

    public function overall()
    {
        return $this->_getStateObject(self::OVERALL);
    }

    public function custom()
    {
        return $this->_getStateObject(self::CUSTOM);
    }

}
