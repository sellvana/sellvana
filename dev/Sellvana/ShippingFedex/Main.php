<?php

/**
 * Class Sellvana_ShippingEasyPost_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_ShippingFedex_ShippingMethod $Sellvana_ShippingFedex_ShippingMethod
 */
class Sellvana_ShippingFedex_Main extends BClass
{
    protected $_methodCode = 'fedex';

    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addShippingMethod($this->_methodCode, 'Sellvana_ShippingFedex_ShippingMethod');
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
                $this->Sellvana_ShippingFedex_ShippingMethod->buyShipment($shipment);
                break;
            case Sellvana_Sales_Model_Order_Shipment_State_Overall::CANCELED:
                $this->Sellvana_ShippingFedex_ShippingMethod->cancelShipment($shipment);
                break;
            default:
                break;
        }
    }
}