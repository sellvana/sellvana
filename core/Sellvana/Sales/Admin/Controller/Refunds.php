<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Refunds
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Refund $Sellvana_Sales_Model_Order_Refund
 */

class Sellvana_Sales_Admin_Controller_Refunds extends Sellvana_Sales_Admin_Controller_Abstract
{
    public function action_create__POST()
    {
        try {
            $orderId = $this->BRequest->get('id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $refundData = $this->BRequest->post('refund');
            $qtys = $this->BRequest->post('qtys');

            $this->Sellvana_Sales_Main->workflowAction('adminCreatesRefund', [
                'order' => $order,
                'data' => $refundData,
                'qtys' => $qtys,
            ]);
            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Refund has been created');
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['refunds'] = (string)$this->view('order/orders-form/refunds')->set('model', $order);
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

            $refunds = $this->BRequest->post('refunds');
            $delete = $this->BRequest->post('delete');
            if ($refunds) {
                foreach ($refunds as $id => $c) {
                    $this->Sellvana_Sales_Main->workflowAction('adminUpdatesRefund', [
                        'order' => $order,
                        'refund_id' => $id,
                        'data' => $c,
                    ]);
                }
            }
            if ($delete) {
                foreach ($delete as $id => $_) {
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesRefund', [
                        'order' => $order,
                        'refund_id' => $id,
                    ]);
                }
            }
            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Refund updates have been applied');
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['refunds'] = (string)$this->view('order/orders-form/refunds')->set('model', $order);
        $this->BResponse->json($result);
    }
}