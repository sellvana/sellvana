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
            'shipping_weight' => 'float',
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
        $packages = [];
        if ($methodClass = $this->Sellvana_Sales_Main->getShippingMethodClassName($method)) {
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
        }

        if (count($packages)) {
            $itemIds = []; // cartItem => orderItem
            foreach ($order->items() as $item) {
                $itemIds[$item->get('cart_item_id')] = $item->id();
            }
            foreach ($packages as $package) {
                $packQtys = [];
                foreach ($package['items'] as $cartItemId => $qty) {
                    $packQtys[$itemIds[$cartItemId]] = $qty;
                }
                $shipment = $this->Sellvana_Sales_Model_Order_Shipment->create($data);
                $shipment->importFromOrder($order, $packQtys);
                $shipment->register();
            }
        } else {
            $shipment = $this->Sellvana_Sales_Model_Order_Shipment->create($data);
            $shipment->importFromOrder($order, $qtys);
            $shipment->register();
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
