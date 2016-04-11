<?php

/**
 * Class Sellvana_ShippingUps_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_ShippingUps_ShippingMethod $Sellvana_ShippingUps_ShippingMethod
 */
class Sellvana_ShippingUps_Main extends BClass
{
    protected $_methodCode = 'ups';

    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addShippingMethod('ups', 'Sellvana_ShippingUps_ShippingMethod');
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_ShippingUps' => 'Shipping UPS Settings',
        ]);
    }

    public function onShipmentStateChange($args)
    {
        /** @var Sellvana_Sales_Model_Order_Shipment_State_Overall $newState */
        $newState = $args['new_state'];

        /** @var Sellvana_Sales_Model_Order_Shipment $shipment */
        $shipment = $newState->getModel();

        $order = $shipment->order();
        if ($order->get('shipping_method') !== $this->_methodCode) {
            return;
        }

        switch ($newState->getValue()) {
            case Sellvana_Sales_Model_Order_Shipment_State_Overall::SHIPPING:
                exit('aaaa');
                $this->Sellvana_ShippingUps_ShippingMethod->buyShipment($shipment);
                break;
//            case Sellvana_Sales_Model_Order_Shipment_State_Overall::CANCELED:
//                $this->Sellvana_ShippingUps_ShippingMethod->cancelShipment($shipment);
//                break;
            default:
                break;
        }
    }
}
