<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Cancels
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Cancel $Sellvana_Sales_Model_Order_Cancel
 */

class Sellvana_Sales_Admin_Controller_Cancels extends Sellvana_Sales_Admin_Controller_Abstract
{
    public function action_create__POST()
    {
        try {
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
            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_(('Cancel has been created'));
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

            $cancellations = $this->BRequest->post('cancels');
            $delete = $this->BRequest->post('delete');
            if ($cancellations) {
                foreach ($cancellations as $id => $c) {
                    $this->Sellvana_Sales_Main->workflowAction('adminUpdatesCancel', [
                        'order' => $order,
                        'cancel_id' => $id,
                        'data' => $c,
                    ]);
                }
            }
            if ($delete) {
                foreach ($delete as $id => $_) {
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesCancel', [
                        'order' => $order,
                        'cancel_id' => $id,
                    ]);
                }
            }
            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_(('Cancel updates have been applied'));
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['cancellations'] = (string)$this->view('order/orders-form/cancellations')->set('model', $order);
        $this->BResponse->json($result);
    }
}