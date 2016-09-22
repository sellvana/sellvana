<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Shipments
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Shipment $Sellvana_Sales_Model_Order_Shipment
 * @property Sellvana_Sales_Model_Order_Shipment_Package $Sellvana_Sales_Model_Order_Shipment_Package
 */

class Sellvana_Sales_Admin_Controller_Shipments extends Sellvana_Sales_Admin_Controller_Abstract
{
    public function action_mass_change_state__POST()
    {
        $request = $this->BRequest;
        $ids = explode(',', $request->post('id'));
        $shipments = $this->Sellvana_Sales_Model_Order_Shipment->orm('os')->where_in('id', $ids)->find_many();
        $action = 'adminMarksShipmentAs' . ucfirst($request->post('state_overall'));

        foreach ($shipments as $shipment) {
            $this->Sellvana_Sales_Main->workflowAction($action, [
                'shipment' => $shipment
            ]);
        }

        $result = ['success' => true];
        $this->BResponse->json($result);
    }

    public function action_create__POST()
    {
        try {
            $orderId = $this->BRequest->get('id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $shipmentData = $this->BRequest->post('shipment');
            $qtys = $this->BRequest->post('qtys');

            $this->Sellvana_Sales_Main->workflowAction('adminCreatesShipment', [
                'order' => $order,
                'data' => $shipmentData,
                'qtys' => $qtys,
            ]);
            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Shipment has been created');
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['shipments'] = (string)$this->view('order/orders-form/shipments')->set('model', $order);
        $this->BResponse->json($result);
    }

    public function action_update__POST()
    {
        try {
            $orderId = $this->BRequest->get('id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $shipments = $this->BRequest->post('shipments');
            $packages = $this->BRequest->post('packages');
            $delete = $this->BRequest->post('delete');
            if ($shipments) {
                foreach ($shipments as $id => $s) {
                    $this->Sellvana_Sales_Main->workflowAction('adminUpdatesShipment', [
                        'order' => $order,
                        'shipment_id' => $id,
                        'data' => $s,
                    ]);
                }
            }
            if ($packages) {
                foreach ($packages as $id => $p) {
                    $this->Sellvana_Sales_Main->workflowAction('adminUpdatesPackage', [
                        'order' => $order,
                        'package_id' => $id,
                        'data' => $p,
                    ]);
                }
            }
            if ($delete) {
                foreach ($delete as $id => $_) {
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesShipment', [
                        'order' => $order,
                        'shipment_id' => $id,
                    ]);
                }
            }
            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Shipment updates have been applied');
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['shipments'] = (string)$this->view('order/orders-form/shipments')->set('model', $order);
        $result['otherInfo'] = $order->getStateInfo();
        $this->BResponse->json($result);
    }

    public function action_rates__POST()
    {
        $rates = [];

        try {
            $result = ['tabs' => []];
            $orderId = $this->BRequest->get('id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $qtys = $this->BRequest->post('qtys');

            $shipmentData = $this->BRequest->post('shipment');
            $method = $shipmentData['carrier_code'];
            $methodClass = $this->Sellvana_Sales_Main->getShippingMethodClassName($method);
            if (!$methodClass) {
                throw new BException('Invalid shipping method');
            }

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
            $rates = $this->$methodClass->fetchCartRates($cart);

            $result['message'] = $this->_('Shipping rates have been updated');
            $result['tabs']['main'] = (string)$this->view('order/orders-form/main')->set('model', $order);
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $order->set('shipping_method', $method);

        $result['tabs']['shipments'] = (string)$this->view('order/orders-form/shipments')->set([
            'model' => $order,
            'rates'=> $rates,
        ]);
        $this->BResponse->json($result);
    }

    public function action_updateTracking__POST()
    {
        $result = [];
        try {
            $orderId = (int)$this->BRequest->get('id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $packageData = $this->BRequest->post('packages');
            if (null !== $packageData && !is_array($packageData)) {
                throw new BException('Invalid packages data');
            }

            $packagesIds = array_keys($packageData);

            $orm = $this->Sellvana_Sales_Model_Order_Shipment_Package->orm('p')
                ->inner_join('Sellvana_Sales_Model_Order_Shipment', ['s.id', '=', 'p.shipment_id'], 's')
                ->where_in('p.id', $packagesIds)
                ->select(['p.id', 'p.order_id', 'p.shipment_id', 's.carrier_code', 's.carrier_desc', 's.state_overall', 'p.tracking_number']);

            /** @var Sellvana_Sales_Model_Order_Shipment_Package[] $packageList */
            $packageList = $orm->find_many();
            /** @var Sellvana_Sales_Model_Order_Shipment_Package[][] $packages */
            $packages = [];
            $carrierDescriptions = [];
            foreach ($packageList as $package) {
                $carrierDescriptions[$package->get('carrier_code')] = $package->get('carrier_desc');

                if (!isset($packages[$package->get('carrier_code')])) {
                    $packages[$package->get('carrier_code')] = [];
                }

                $packages[$package->get('carrier_code')][$package->get('id')] = $package;
            }

            $response = [];
            foreach (array_keys($packages) as $methodName) {
                $method = $this->Sellvana_Sales_Main->getShippingMethodClassName($methodName);
                $response[$methodName] = $this->$method->fetchTrackingUpdates($packages[$methodName]);
                foreach ($packages[$methodName] as $package) {
                    $data = ['tracking_number' => $package->get('tracking_number')];
                    if (array_key_exists('states', $response[$methodName])
                        && array_key_exists($package->id(), $response[$methodName]['states'])) {
                        $data['state_overall'] = $response[$methodName]['states'][$package->id()];
                    }

                    $this->Sellvana_Sales_Main->workflowAction('adminUpdatesPackage', [
                        'order' => $order,
                        'package_id' => $package->id(),
                        'data' => $data,
                    ]);

                    $package->save();
                }
            }
            $result['message'] = $this->_('Tracking updates have been received.');

            foreach ($response as $method => $data) {
                if (isset($data['error']) && $data['error']) {
                    $result['error'] = $data['error'];
                    $result['message'] = 'Shipping method: "'
                        . $carrierDescriptions[$method] . '" Response: "' . $data['message'] . '"';
                }
            }
            $result = array_merge($this->_resetOrderTabs($order), $result);
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['shipments'] = (string)$this->view('order/orders-form/shipments')->set('model', $order);

        $this->BResponse->json($result);
    }

    public function action_printLabel()
    {
        $packageId = $this->BRequest->get('id');
        $package = $this->Sellvana_Sales_Model_Order_Shipment_Package->load($packageId);
        $label = $package->label();

        $fileName = 'shipmentLabel.pdf';

        if (is_array($label)){
            $fileName = $label['filename'];
            $label = $label['content'];
        }
        
        $this->BResponse->sendContent($label, $fileName);
    }
}