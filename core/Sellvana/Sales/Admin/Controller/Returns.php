<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Returns
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Return $Sellvana_Sales_Model_Order_Return
 */

class Sellvana_Sales_Admin_Controller_Returns extends Sellvana_Sales_Admin_Controller_Abstract
{
    public function action_create__POST()
    {
        try {
            $orderId = $this->BRequest->get('id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $returnData = $this->BRequest->post('return');
            $qtys = $this->BRequest->post('qtys');

            $this->Sellvana_Sales_Main->workflowAction('adminCreatesReturn', [
                'order' => $order,
                'data' => $returnData,
                'qtys' => $qtys,
            ]);
            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Return has been created');
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['returns'] = (string)$this->view('order/orders-form/returns')->set('model', $order);
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

            $returns = $this->BRequest->post('returns');
            $delete = $this->BRequest->post('delete');
            if ($returns) {
                foreach ($returns as $id => $c) {
                    $this->Sellvana_Sales_Main->workflowAction('adminUpdatesReturn', [
                        'order' => $order,
                        'return_id' => $id,
                        'data' => $c,
                    ]);
                }
            }
            if ($delete) {
                foreach ($delete as $id => $_) {
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesReturn', [
                        'order' => $order,
                        'return_id' => $id,
                    ]);
                }
            }
            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Return updates have been applied');
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['returns'] = (string)$this->view('order/orders-form/returns')->set('model', $order);
        $this->BResponse->json($result);
    }
}