<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Cancels
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Cancel $Sellvana_Sales_Model_Order_Cancel
 */

class Sellvana_Sales_Admin_Controller_Cancels extends FCom_Admin_Controller_Abstract_GridForm
{
    public function action_mass_change_state__POST()
    {
        exit('change state');
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

            $cancelData = $this->BRequest->post('cancel');
            $qtys = $this->BRequest->post('qtys');

            $this->Sellvana_Sales_Main->workflowAction('adminCreatesCancel', [
                'order' => $order,
                'data' => $cancelData,
                'qtys' => $qtys,
            ]);
            $result['tabs']['main'] = (string)$this->view('order/orders-form/main')->set('model', $order);
            $result['message'] = $this->_('Cancel has been created');
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['cancellations'] = (string)$this->view('order/orders-form/cancellations')->set('model', $order);
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

            $cancellations = $this->BRequest->post('cancel');
            $delete = $this->BRequest->post('delete');
            if ($cancellations) {
                foreach ($cancellations as $id => $s) {
                    $this->Sellvana_Sales_Main->workflowAction('adminUpdatesCancel', [
                        'order' => $order,
                        'shipment_id' => $id,
                        'data' => $s,
                    ]);
                }
            }
            if ($delete) {
                foreach ($delete as $id => $_) {
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesCancel', [
                        'order' => $order,
                        'shipment_id' => $id,
                    ]);
                }
            }
            $result['message'] = $this->_('Cancel updates have been applied');
            $result['tabs']['main'] = (string)$this->view('order/orders-form/main')->set('model', $order);
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['cancellations'] = (string)$this->view('order/orders-form/cancellations')->set('model', $order);
        $this->BResponse->json($result);
    }
}