<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_Shipment_State extends FCom_Core_Model_Abstract_State_Context
{
    const OVERALL = 'overall',
        CARRIER = 'carrier',
        CUSTOM = 'custom';

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
        self::OVERALL => 'Sellvana_Sales_Model_Order_Shipment_State_Overall',
        self::CARRIER => 'Sellvana_Sales_Model_Order_Shipment_State_Carrier',
        self::CUSTOM => 'Sellvana_Sales_Model_Order_Shipment_State_Custom',
    ];

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject(self::OVERALL);
    }

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_State_Carrier
     * @throws BException
     */
    public function carrier()
    {
        return $this->_getStateObject(self::CARRIER);
    }

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_State_Custom
     * @throws BException
     */
    public function custom()
    {
        return $this->_getStateObject(self::CUSTOM);
    }

}
