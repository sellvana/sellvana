<?php

/**
 * Class Sellvana_Sales_Model_Order_Shipment_Package
 *
 * @property Sellvana_Sales_Model_Order_Shipment $Sellvana_Sales_Model_Order_Shipment
 * @property Sellvana_Sales_Model_Order_Shipment_Item $Sellvana_Sales_Model_Order_Shipment_Item
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_Sales_Model_Order_Shipment_Package extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_shipment_package';
    protected static $_origClass = __CLASS__;

    /**
     * @var Sellvana_Sales_Model_Order_Shipment_Item[]
     */
    protected $_items;

    protected $_shipment;

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_Item[]
     */
    public function items()
    {
        if (null === $this->_items) {
            $this->_items = $this->Sellvana_Sales_Model_Order_Shipment_Item->orm('osi')
                ->where('package_id', $this->id())
                ->join('Sellvana_Sales_Model_Order_Item', ['oi.id', '=', 'osi.order_item_id'], 'oi')
                ->select('osi.*')->select(['oi.product_id', 'product_sku', 'inventory_id', 'inventory_sku',
                    'product_name', 'shipping_size', 'shipping_weight'])
                ->find_many();
        }
        return $this->_items;
    }


    public function label()
    {
        if (!$this->_shipment) {
            $this->_shipment = $this->Sellvana_Sales_Model_Order_Shipment->load($this->get('shipment_id'));
        }

        $method = $this->_shipment->get('carrier_code');
        $methodClass = $this->Sellvana_Sales_Main->getShippingMethodClassName($method);
        if (!$methodClass) {
            return false;
        }

        return $this->$methodClass->getPackageLabel($this);
    }
}