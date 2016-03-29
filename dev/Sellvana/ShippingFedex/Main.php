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
        /**
         * @var Sellvana_Sales_Model_Order_Shipment_State_Overall $newState
         */
        $newState = $args['new_state'];
        if ($newState->getValue() === Sellvana_Sales_Model_Order_Shipment_State_Overall::SHIPPING) {
            $orderId = $newState->getModel()->get('order_id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if ($order->get('shipping_method') === $this->_methodCode) {
                $this->Sellvana_ShippingFedex_ShippingMethod->buyShipment();
            }
        }
    }
}