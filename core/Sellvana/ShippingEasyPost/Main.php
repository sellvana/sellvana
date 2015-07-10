<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ShippingEasyPost_Main
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_ShippingEasyPost_Main extends BClass
{
    protected $_methodCode = 'easypost';
    protected $_configPath = 'modules/Sellvana_ShippingEasyPost';

    public function bootstrap()
    {
        $this->Sellvana_Sales_Main->addShippingMethod($this->_methodCode, 'Sellvana_ShippingEasyPost_ShippingMethod');
    }

    public function serviceCheckNeeded($args)
    {
        if ($args['shipping_method'] === $this->_methodCode) {
            $args['service_check_needed'] = false;
        }
    }

    public function onShipmentStateChange($args)
    {
        /**
         * @var Sellvana_Sales_Model_Order_Shipment_State_Overall $newState
         */
        //var_dump($args);
        $newState = $args['new_state'];
        if ($newState->getValue() === Sellvana_Sales_Model_Order_Shipment_State_Overall::SHIPPED) {
            $orderId = $newState->getModel()->get('order_id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if ($order->get('shipping_method') === $this->_methodCode) {
                $rates = $order->cart()->getData('shipping_rates/' . $this->_methodCode);
                if (isset($rates[$order->get('shipping_service')])) {
                    $config = $this->BConfig->get($this->_configPath);
                    if ($config['mode'] === Sellvana_ShippingEasyPost_ShippingMethod::MODE_TEST) {
                        $config['access_key'] = $config['test_access_key'];
                    }
                    \EasyPost\EasyPost::setApiKey($config['access_key']);

                    $selectedRate = $rates[$order->get('shipping_service')];
                    foreach($selectedRate['packages'] as $package) {
                        /** @var \EasyPost\Shipment $shipment */
                        $shipment = \EasyPost\Shipment::retrieve($package['shipment_id']);
                        $shipment->buy(array('rate' => array('id' => $package['id'])));
                    }
                }
            }

        }
    }
}
