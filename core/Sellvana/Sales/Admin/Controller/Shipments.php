<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Shipments
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Shipment $Sellvana_Sales_Model_Order_Shipment
 */

class Sellvana_Sales_Admin_Controller_Shipments extends FCom_Admin_Controller_Abstract_GridForm
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
            $result = ['tabs' => []];
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
            $result['tabs']['main'] = (string)$this->view('order/orders-form/main')->set('model', $order);
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
            $result['message'] = $this->_('Shipment updates have been applied');
            $result['tabs']['main'] = (string)$this->view('order/orders-form/main')->set('model', $order);
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['shipments'] = (string)$this->view('order/orders-form/shipments')->set('model', $order);
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

        $result['tabs']['shipments'] = (string)$this->view('order/orders-form/shipments')->set([
            'model' => $order,
            'rates'=> $rates,
        ]);
        $this->BResponse->json($result);
    }

    public function action_printLabel()
    {
        $shipmentId = $this->BRequest->get('id');
        $shipment = $this->Sellvana_Sales_Model_Order_Shipment->load($shipmentId);
        $label = $shipment->label();
        $fileName = 'shipmentLabel.pdf';

        $this->BResponse->header([
            'Pragma: public',
            'Cache-Control: must-revalidate, post-check=0, pre-check=0',
            'Content-Length: ' . strlen($label),
            'Last-Modified: ' . date('r'),
            'Content-Type: application/pdf',
            'Content-Disposition: inline; filename=' . $fileName,
        ]);

        echo $label;

        $this->BResponse->shutdown(__METHOD__);
    }
}