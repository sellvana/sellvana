<?php

/**
 * Class Sellvana_Sales_Workflow_Shipment
 *
 * @property Sellvana_Sales_Model_Order_Shipment $Sellvana_Sales_Model_Order_Shipment
 * @property Sellvana_Sales_Model_Order_Shipment_Package $Sellvana_Sales_Model_Order_Shipment_Package
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_Sales_Workflow_Shipment extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    static protected $_shipmentOverallStates = [
        'pending' => 'setPending',
        'packing' => 'setPacking',
        'shipping' => 'setShipping',
        'shipped' => 'setShipped',
        'exception' => 'setException',
        'delivered' => 'setDelivered',
        'returned' => 'setReturned',
        'canceled' => 'setCanceled',
    ];

    public function action_adminCreatesShipment($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $data = $this->BRequest->sanitize($args['data'], [
            'carrier_price' => 'float',
            'shipping_weight' => 'float',
            'shipping_size' => 'int',
            'carrier_code' => 'plain',
            'service_code' => 'plain',
        ]);
        $qtys = isset($args['qtys']) ? $args['qtys'] : null;
        foreach ($qtys as $id => $qty) {
            if ($qty < 1) {
                unset($qtys[$id]);
            }
        }
        if (!$qtys) {
            throw new BException('Please add some items to create a shipment');
        }
        $method = $order->get('shipping_method');
        $methodClass = $this->Sellvana_Sales_Main->getShippingMethodClassName($method);
        if (!$methodClass) {
            throw new BException('Invalid shipping method');
        }
        $shippingServices = $this->$methodClass->getServices();
        $data['carrier_desc'] = $this->$methodClass->getDescription();
        $serviceCode = $data['service_code'];
        $data['service_desc'] = !empty($shippingServices[$serviceCode]) ? $shippingServices[$serviceCode] : null;

        $cart = $order->cart();
        foreach ($order->items() as $oItem) {
            foreach ($cart->items() as $cItem) {
                if ($cItem->id() != $oItem->get('cart_item_id')) {
                    continue;
                }
                $qty = (array_key_exists($oItem->id(), $qtys)) ? $qtys[$oItem->id()] : 0;
                $cItem->set('qty', $qty);
            }
        }
        $packages = $this->$methodClass->calcCartPackages($cart);

        /** @var Sellvana_Sales_Model_Order_Shipment $shipment */
        if (count($packages)) {
            $itemsData = []; // cartItem => orderItem
            foreach ($order->items() as $item) {
                /** @var Sellvana_Catalog_Model_Product $product */
                if ($product = $item->product()) { // in case that product was already deleted from DB
                    $itemWeight = $product->getInventoryModel()->get('shipping_weight');
                }

                $itemsData[$item->get('cart_item_id')] = [
                    'id' => $item->id(),
                    'weight' => isset($itemWeight) ? $itemWeight : 0
                ];
            }
            foreach ($packages as $package) {
                $packageData = $data;
                $packageData['shipping_weight'] = 0;
                $packQtys = [];
                foreach ($package['items'] as $cartItemId => $qty) {
                    $packQtys[$itemsData[$cartItemId]['id']] = $qty;
                    $packageData['shipping_weight'] += $qty * $itemsData[$cartItemId]['weight'];
                }

                $shipment = $this->Sellvana_Sales_Model_Order_Shipment->create();
                $shipment->importFromOrder($order, $packQtys);
                $shipment->set($packageData);
                $shipment->register();
                $shipment->save();
            }
        } else {
            $shipment = $this->Sellvana_Sales_Model_Order_Shipment->create();
            $shipment->importFromOrder($order, $qtys);
            $shipment->set($data);
            $shipment->register();
            $shipment->save();
        }
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminUpdatesShipment($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $shipmentId = $args['shipment_id'];
        $data = $args['data'];
        $shipment = $this->Sellvana_Sales_Model_Order_Shipment->load($shipmentId);
        if (!$shipment || $shipment->get('order_id') != $order->id()) {
            throw new BException('Invalid shipment to update');
        }
        if (isset($data['state_custom'])) {
            $shipment->state()->custom()->changeState($data['state_custom']);
        }
        if (isset($data['state_overall'])) {
            foreach ($data['state_overall'] as $state => $_) {
                $method = static::$_shipmentOverallStates[$state];
                $shipment->state()->overall()->$method();
            }
        }
        $shipment->save();
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminUpdatesPackage($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $packageId = $args['package_id'];
        $data = $args['data'];
        $shipment = $this->Sellvana_Sales_Model_Order_Shipment_Package->load($packageId);
        if (!$shipment || $shipment->get('order_id') != $order->id()) {
            throw new BException('Invalid package to update');
        }
        if (isset($data['tracking_number'])) {
            $shipment->set('tracking_number', $data['tracking_number']);
        }
        $shipment->save();
    }

    public function action_adminDeletesShipment($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $shipmentId = $args['shipment_id'];
        $shipment = $this->Sellvana_Sales_Model_Order_Shipment->load($shipmentId);
        if (!$shipment || $shipment->get('order_id') != $order->id()) {
            throw new BException('Invalid shipment to delete');
        }
        $shipment->unregister()->delete();
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminPrintsShippingLabels($args)
    {
    }

    public function action_adminChangesShipmentCustomState($args)
    {
        $newState = $args['shipment']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['shipment']->addHistoryEvent('custom_state', 'Admin user has changed custom shipment state to "' . $label . '"');
        $args['shipment']->save();
    }

    /**
     * @param Sellvana_Sales_Model_Order_Shipment[] $args
     */
    public function action_adminMarksShipmentAsShipped($args)
    {
        $args['shipment']->register()->save();

        $args['shipment']->order()->state()->calcAllStates();
        $args['shipment']->order()->saveAllDetails();
    }
}
