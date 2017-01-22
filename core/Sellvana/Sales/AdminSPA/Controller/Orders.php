<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Sales_Main Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Comment Sellvana_Sales_Model_Order_Comment
 * @property Sellvana_Sales_Model_Order_Item Sellvana_Sales_Model_Order_Item
 * @property Sellvana_Sales_Model_Order_State_Overall Sellvana_Sales_Model_Order_State_Overall
 * @property Sellvana_Sales_Model_Order_Payment Sellvana_Sales_Model_Order_Payment
 * @property Sellvana_Sales_Model_Order_Payment_Transaction Sellvana_Sales_Model_Order_Payment_Transaction
 * @property Sellvana_Sales_Model_Order_History Sellvana_Sales_Model_Order_History
 */
class Sellvana_Sales_AdminSPA_Controller_Orders extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        return [
            'id' => 'orders',
            'data_url' => 'orders/grid_data',
            'columns' => [
                ['type' => 'row-select'],
                ['type' => 'actions', 'actions' => [
                    ['type' => 'edit', 'link' => '/sales/orders/form?id={id}', 'icon_class' => 'fa fa-pencil'],
                    //['type' => 'delete', 'delete_url' => 'orders/grid_delete?id={id}', 'icon_class' => 'fa fa-trash'],
                ]],
                ['name' => 'id', 'label' => 'Internal ID'],
                ['name' => 'unique_id', 'label' => 'Order ID'],
                ['name' => 'state_overall', 'label' => 'Overall State', 'options' => $this->Sellvana_Sales_Model_Order_State_Overall->getAllValueLabels()],
                ['name' => 'customer_firstname', 'label' => 'Last Name'],
                ['name' => 'customer_lastname', 'label' => 'Last Name'],
                ['name' => 'customer_email', 'label' => 'Email'],
                ['name' => 'create_at', 'label' => 'Created', 'type' => 'date']
            ],
            'filters' => [
                ['name' => 'id', 'type' => 'number'],
                ['name' => 'unique_id'],
                ['name' => 'state_overall'],
                ['name' => 'create_at'],
            ],
            'export' => true,
            'pager' => true,
            'bulk_actions' => [
                ['name' => 'custom_state', 'label' => 'Change Custom State'],
            ],
            'state' => [
                's' => 'id',
                'sd' => 'desc',
            ],
        ];
    }

    public function getGridOrm()
    {
        return $this->Sellvana_Sales_Model_Order->orm('o');
    }

    public function action_grid_delete__POST()
    {

    }

    protected function _getFullOrderFormData($orderId)
    {
        $order         = $this->Sellvana_Sales_Model_Order->load($orderId);
        if (!$order) {
            throw new BException('Order not found');
        }
        return [
            'tabs' => $this->getFormTabs('/sales/orders/form'),
            'details_sections' => $this->view('sales/orders/form')->getDetailsSections(),

            'order' => $this->_getOrderData($order),
            'items' => $this->_getOrderItems($order),
            'totals' => $this->_getOrderTotals($order),

            'shipments' => $this->_getShipments($order),
            'returns' => $this->_getReturns($order),
            'payments' => $this->_getPayments($order),
            'refunds' => $this->_getRefunds($order),
            'cancellations' => $this->_getCancellations($order),

            'items_payable' => $this->_getPayableItems($order),
            'items_shippable' => $this->_getShippableItems($order),
            'items_returnable' => $this->_getReturnableItems($order),
            'items_refundable' => $this->_getRefundableItems($order),
            'items_cancellable' => $this->_getCancellableItems($order),

            'comments' => $this->_getComments($order),

            'options' => [
                'order_state_overall' => $order->state()->overall()->getAllValueLabels(),
                'order_state_delivery' => $order->state()->delivery()->getAllValueLabels(),
                'order_state_payment' => $order->state()->payment()->getAllValueLabels(),
                'order_state_custom' => $order->state()->custom()->getAllValueLabels(),
                'item_state_overall' => $this->Sellvana_Sales_Model_Order_Item_State_Overall->getAllValueLabels(),
                'item_state_delivery' => $this->Sellvana_Sales_Model_Order_Item_State_Delivery->getAllValueLabels(),
                'item_state_custom' => $this->Sellvana_Sales_Model_Order_Item_State_Custom->getAllValueLabels(),
                'shipment_state_overall' => $this->Sellvana_Sales_Model_Order_Shipment_State_Overall->getAllValueLabels(),
                'payment_state_overall' => $this->Sellvana_Sales_Model_Order_Payment_State_Overall->getAllValueLabels(),
                'return_state_overall' => $this->Sellvana_Sales_Model_Order_Return_State_Overall->getAllValueLabels(),
                'refund_state_overall' => $this->Sellvana_Sales_Model_Order_Refund_State_Overall->getAllValueLabels(),
                'cancel_state_overall' => $this->Sellvana_Sales_Model_Order_Cancel_State_Overall->getAllValueLabels(),
            ],

            'items_grid_config' => $this->applyGridPersonalization($this->normalizeGridConfig($this->getItemsGridConfig())),

            'payment_methods' => $this->_getPaymentMethods(),
            'shipping_methods' => $this->_getShippingMethods(),

            'updates' => [],
        ];
    }

    public function action_form_data()
    {
        $result = [];
        try {
            $orderId        = $this->BRequest->get('id');
            $result['form'] = $this->_getFullOrderFormData($orderId);
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    protected function _getOrderData(Sellvana_Sales_Model_Order $order)
    {
        return $order->as_array();
    }

    protected function _getOrderItems(Sellvana_Sales_Model_Order $order)
    {
        $order->loadItemsProducts(true);
        $items = $order->items(false);
        /** @var Sellvana_Sales_Model_Order_Item $item */
        foreach ($items as $item) {
            $item->set('thumb_url', $item->thumbUrl(48));
        }

        return $this->BDb->many_as_array($items);
    }

    protected function _getOrderTotals(Sellvana_Sales_Model_Order $order)
    {
        $result = [];
        foreach ($order->getData('totals') as $id => $total) {
            if (in_array($id, ['shipping', 'tax']) || !(float)$total['value']) {
                $total['amount_due'] = $total['value'];
            }
            $total['name'] = $id;
            $result[] = $total;
        }
        return $result;
    }

    protected function _getPayments(Sellvana_Sales_Model_Order $order)
    {
        $payments = $order->getAllPayments(true, true);
        foreach ($payments as $p) {
            $p->set('entity_type', 'payment');
            $p->set('is_manual_state_management_allowed', $p->isManualStateManagementAllowed());
            $method = $p->getMethodObject();
            if ($method && $method->isRootTransactionNeeded() && $method->can('pay_by_url')) {
                $p->set('root_transaction_url', $p->getRootTransactionUrl());
            }
            if (($nextValues = $p->state()->overall()->getNextValueLabels())) {
                $p->set('state_overall_next_values', $nextValues);
            }
            if (($nextValues = $p->state()->custom()->getNextValueLabels())) {
                $p->set('state_custom_next_values', $nextValues);
            }
            if ($p->transactions()) {
                foreach ($p->transactions() as $t) {
                    $availActions = $t->getAvailableActions();
                    if ($availActions) {
                        foreach ($availActions as $i => $a) {
                            if (!empty($a['max_amount'])) {
                                $availActions[$i]['amount'] = $a['max_amount'];
                            }
                        }
                        $t->set('available_actions', $availActions);
                    }
                }
            }
        }
        return $this->BDb->many_as_array($payments);
    }

    protected function _getShipments(Sellvana_Sales_Model_Order $order)
    {
        $shipments = $order->getAllShipments(true, true);
        foreach ($shipments as $s) {
            $s->set('entity_type', 'shipment');
        }
        return $this->BDb->many_as_array($shipments);
    }

    protected function _getReturns(Sellvana_Sales_Model_Order $order)
    {
        $returns = $order->getAllReturns(true);
        foreach ($returns as $r) {
            $r->set('entity_type', 'return');
        }
        return $this->BDb->many_as_array($returns);
    }

    protected function _getRefunds(Sellvana_Sales_Model_Order $order)
    {
        $refunds = $order->getAllRefunds(true);
        foreach ($refunds as $r) {
            $r->set('entity_type', 'refund');
        }
        return $this->BDb->many_as_array($refunds);
    }

    protected function _getCancellations(Sellvana_Sales_Model_Order $order)
    {
        $cancellations = $order->getAllCancellations(true);
        foreach ($cancellations as $c) {
            $c->set('entity_type', 'cancellation');
        }
        return $this->BDb->many_as_array($cancellations);
    }

    protected function _getPayableItems(Sellvana_Sales_Model_Order $order)
    {
        $items = $order->getPayableItems();

        return $this->BDb->many_as_array($items);
    }

    protected function _getShippableItems(Sellvana_Sales_Model_Order $order)
    {
        $items = $order->getShippableItems();

        return $this->BDb->many_as_array($items);
    }

    protected function _getReturnableItems(Sellvana_Sales_Model_Order $order)
    {
        $items = $order->getReturnableItems();

        return $this->BDb->many_as_array($items);
    }

    protected function _getRefundableItems(Sellvana_Sales_Model_Order $order)
    {
        $items = $order->getRefundableItems();

        return $this->BDb->many_as_array($items);
    }

    protected function _getCancellableItems(Sellvana_Sales_Model_Order $order)
    {
        $items = $order->getCancelableItems();

        return $this->BDb->many_as_array($items);
    }

    protected function _getComments($order)
    {
        $comments = $this->Sellvana_Sales_Model_Order_Comment->orm()->where('order_id', $order->id())->find_many();

        return $this->BDb->many_as_array($comments);
    }

    protected function _getPaymentMethods()
    {
        $methods = $this->Sellvana_Sales_Main->getPaymentMethods();
        $result = [];
        foreach ($methods as $methodCode => $method) {
            $m = $method->getAllMetaInfo();
            $m['id'] = $methodCode;
            $m['text'] = $method->getName();
            $result[] = $m;
        }
        return $result;
    }

    protected function _getShippingMethods()
    {
        $methods = $this->Sellvana_Sales_Main->getShippingMethods();
        $result = [];
        foreach ($methods as $methodCode => $method) {
            $result[] = [
                'id' => $methodCode,
                'text' => $method->getName(),
                'services' => $method->getServices()
            ];
        }
        return $result;
    }

    public function getItemsGridConfig()
    {
        $itemStateOverallOptions = $this->Sellvana_Sales_Model_Order_Item_State_Overall->getAllValueLabels();
        $itemStatePaymentOptions = $this->Sellvana_Sales_Model_Order_Item_State_Payment->getAllValueLabels();
        $itemStateDeliveryOptions = $this->Sellvana_Sales_Model_Order_Item_State_Delivery->getAllValueLabels();
        $itemStateCustomOptions = $this->Sellvana_Sales_Model_Order_Item_State_Custom->getAllValueLabels();

        return [
            'id' => 'order_items',
            'columns' =>  [
                ['type' => 'row-select'],
                //['type' => 'actions'],
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'thumb_path', 'label' => 'Thumbnail', 'width' => 48, 'sortable' => false,
                    'datacell_template' => '<td><a :href="\'#/catalog/products/form?id=\'+row.id"><img :src="row.thumb_url" :alt="row.product_name"></a></td>'],
                ['name' => 'product_name', 'label' => 'Product Name'],
                ['name' => 'product_sku', 'label' => 'Product SKU'],
                ['name' => 'price', 'label' => 'Price'],
                ['name' => 'qty_ordered', 'label' => 'Qty'],
                ['name' => 'row_total', 'label' => 'Total'],
                ['name' => 'state_overall', 'label' => 'Overall', 'options' => $itemStateOverallOptions],
                ['name' => 'state_payment', 'label' => 'Payment', 'options' => $itemStatePaymentOptions],
                ['name' => 'state_delivery', 'label' => 'Delivery', 'options' => $itemStateDeliveryOptions],
                ['name' => 'state_custom', 'label' => 'Custom', 'options' => $itemStateCustomOptions],
            ],
            'bulk_actions' => [
                ['name' => 'delete', 'label' => 'Delete Items'],
            ],
        ];
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            //$orderId =
            $this->ok()->addMessage('Order changes has been saved successfully', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function action_ship_all_items__POST()
    {
        $result = [];
        $orderId = $this->BRequest->post('order_id');
        try {
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if (!$order) {
                throw new BException('Invalid order');
            }
            $this->Sellvana_Sales_Main->workflowAction('adminMarksOrderAsShipped', [
                'order' => $order
            ]);
            $this->ok()->addMessage('Order has been marked as shipped', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $result['form'] = $this->_getFullOrderFormData($orderId);
        $this->respond($result);
    }

    public function action_mark_as_paid__POST()
    {
        $result = [];
        $orderId = $this->BRequest->post('order_id');
        try {
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if (!$order) {
                throw new BException('Invalid order');
            }
            $this->Sellvana_Sales_Main->workflowAction('adminMarksOrderAsPaid', [
                'order' => $order
            ]);
            $this->ok()->addMessage('Order has been marked as paid', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $result['form'] = $this->_getFullOrderFormData($orderId);
        $this->respond($result);
    }


    public function action_form_history_grid_data()
    {
        $orderId = $this->BRequest->get('id');
        $data = $this->Sellvana_Sales_Model_Order_History->orm('h')->where('order_id', $orderId)->paginate();
        $result = [
            'rows' => BDb::many_as_array($data['rows']),
            'state' => $data['state'],
        ];
        $this->respond($result);
    }

    public function action_payment_add__POST()
    {
        $result = [];
        try {
            $orderId = $this->BRequest->post('order_id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if (!$order) {
                throw new BException('Invalid order');
            }

            $paymentData = $this->BRequest->post('payment');
            $amounts = $this->BRequest->post('amounts');
            $totals = $this->BRequest->post('totals');

            $wfaResult = $this->Sellvana_Sales_Main->workflowAction('adminCreatesPayment', [
                'order' => $order,
                'data' => $paymentData,
                'amounts' => $amounts,
                'totals' => $totals,
            ]);
            foreach ($wfaResult as $r) {
                if (!empty($r['new_payment'])) {
                    $result['new_entity_id'] = $r['new_payment']->id();
                }
            }
            $result['form'] = $this->_getFullOrderFormData($orderId);
            $this->ok()->addMessage('Payment has been created', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }

        $this->respond($result);
    }

    public function action_payment_state__POST()
    {
        $result = [];
        $orderId = $this->BRequest->post('order_id');
        try {
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if (!$order) {
                throw new BException('Invalid order');
            }

            $paymentId = $this->BRequest->post('payment_id');
            $payment = $this->Sellvana_Sales_Model_Order_Payment->load($paymentId);
            if (!$payment || $payment->get('order_id') !== $orderId) {
                throw new BException('Invalid payment ID');
            }

            $type =  $this->BRequest->post('type');
            $value = $this->BRequest->post('value');

            $this->Sellvana_Sales_Main->workflowAction('adminUpdatesPayment', [
                'order' => $order,
                'payment_id' => $paymentId,
                'data' => ["state_{$type}" => [$value => true]],
            ]);
            $this->ok()->addMessage('Payment state has been changed', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $result['form'] = $this->_getFullOrderFormData($orderId);

        $this->respond($result);
    }

    public function action_transaction_action__POST()
    {
        $result = [];
        $typeToPaymentMethods = [
            Sellvana_Sales_Model_Order_Payment_Transaction::CAPTURE => 'capture',
            Sellvana_Sales_Model_Order_Payment_Transaction::REFUND => 'refund',
            Sellvana_Sales_Model_Order_Payment_Transaction::REAUTHORIZATION => 'reauthorize',
            Sellvana_Sales_Model_Order_Payment_Transaction::AUTHORIZATION => 'authorize',
            Sellvana_Sales_Model_Order_Payment_Transaction::VOID => 'void',
        ];
        $orderId = $this->BRequest->post('order_id');
        try {
            $type = $this->BRequest->post('action_type');
            if (!$type || !array_key_exists($type, $typeToPaymentMethods)) {
                throw new BException('Invalid action type');
            }

            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if (!$order) {
                throw new BException('Invalid order ID');
            }

            $paymentId = $this->BRequest->post('payment_id');
            $payment = $this->Sellvana_Sales_Model_Order_Payment->load($paymentId);
            if (!$payment || $payment->get('order_id') !== $orderId) {
                throw new BException('Invalid payment ID');
            }

            $transId = $this->BRequest->post('transaction_id');
            if ($transId) {
                $parent = $this->Sellvana_Sales_Model_Order_Payment_Transaction->load($transId);
                if (!$parent || $parent->get('payment_id') !== $paymentId) {
                    throw new BException('Invalid transaction ID');
                }
            } else {
                $parent = null;
            }

            $method = $typeToPaymentMethods[$type];

            if ($method === 'void') {
                $payment->$method($parent);
            } else {
                $amount = $this->BRequest->post('amount');
                $payment->$method($amount, $parent);
            }

            $this->ok()->addMessage('Transaction has been added successfully.', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $result['form'] = $this->_getFullOrderFormData($orderId);
        $this->respond($result);
    }

    public function action_send_root_transaction_url__POST()
    {
        $result = [];
        $orderId = $this->BRequest->post('order_id');
        try {
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if (!$order) {
                throw new BException('Invalid order ID');
            }

            $paymentId = $this->BRequest->post('payment_id');
            $payment = $this->Sellvana_Sales_Model_Order_Payment->load($paymentId);
            if (!$payment || $payment->get('order_id') !== $orderId) {
                throw new BException('Invalid payment ID');
            }

            $view = $this->BLayout->getView('email/sales/order-payment-create-root-transaction');
            if (!$view instanceof BViewEmpty) {
                $url = $payment->getRootTransactionUrl();
                $view->set(['order' => $order, 'url' => $url, 'payment' => $payment])->email();
            }

            $this->ok()->addMessage('Root transaction URL has been sent successfully.', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $result['form'] = $this->_getFullOrderFormData($orderId);
        $this->respond($result);
    }

    public function action_shipment_add__POST()
    {
        $result = [];
        try {
            $orderId = $this->BRequest->post('order_id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $shipmentData = $this->BRequest->post('shipment');
            $qtys = $this->BRequest->post('qtys');

            $wfaResult = $this->Sellvana_Sales_Main->workflowAction('adminCreatesShipment', [
                'order' => $order,
                'data' => $shipmentData,
                'qtys' => $qtys,
            ]);
            foreach ($wfaResult as $r) {
                if (!empty($r['new_shipment'])) {
                    $result['new_entity_id'] = $r['new_shipment']->id();
                }
            }
            $this->ok()->addMessage('Shipment has been created', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $result['form'] = $this->_getFullOrderFormData($orderId);
        $this->respond($result);
    }

    public function action_shipment_state__POST()
    {

    }

    public function action_shipment_edit__POST()
    {
        $result = [];
        try {
            $orderId = $this->BRequest->post('order_id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if (!$order) {
                throw new BException('Invalid order ID');
            }

            $packages = $this->BRequest->post('packages');
            if (!is_array($packages)) {
                throw new BException('Invalid packages data');
            }
            foreach ((array)$packages as $id => $p) {
                $this->Sellvana_Sales_Main->workflowAction('adminUpdatesPackage', [
                    'order' => $order,
                    'package_id' => $id,
                    'data' => $p,
                ]);
            }
            $this->ok()->addMessage('Shipment has been updated', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $result['form'] = $this->_getFullOrderFormData($orderId);
        $this->respond($result);
    }

    public function action_refund_add__POST()
    {
        $result = [];
        try {
            $orderId = $this->BRequest->post('order_id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if (!$order) {
                throw new BException('Invalid order');
            }

            $amounts = $this->BRequest->post('amounts');

            $wfaResult = $this->Sellvana_Sales_Main->workflowAction('adminCreatesRefund', [
                'order' => $order,
                'amounts' => $amounts,
            ]);
            foreach ($wfaResult as $r) {
                if (!empty($r['new_refund'])) {
                    $result['new_entity_id'] = $r['new_refund']->id();
                }
            }
            $result['form'] = $this->_getFullOrderFormData($orderId);
            $this->ok()->addMessage('Refund has been created', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function action_refund_edit__POST()
    {

    }

    public function action_return_add__POST()
    {
        $result = [];
        try {
            $orderId = $this->BRequest->post('order_id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $returnData = $this->BRequest->post('return');
            $qtys = $this->BRequest->post('qtys');

            $wfaResult = $this->Sellvana_Sales_Main->workflowAction('adminCreatesReturn', [
                'order' => $order,
                'data' => $returnData,
                'qtys' => $qtys,
            ]);
            foreach ($wfaResult as $r) {
                if (!empty($r['new_return'])) {
                    $result['new_entity_id'] = $r['new_return']->id();
                }
            }
            $result['form'] = $this->_getFullOrderFormData($orderId);
            $this->ok()->addMessage('Return has been created', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function action_return_edit__POST()
    {

    }

    public function action_cancellation_add__POST()
    {
        $result = [];
        try {
            $orderId = $this->BRequest->post('order_id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $cancelData = $this->BRequest->post('cancel');
            $qtys = $this->BRequest->post('qtys');

            $wfaResult = $this->Sellvana_Sales_Main->workflowAction('adminCreatesCancel', [
                'order' => $order,
                'data' => $cancelData,
                'qtys' => $qtys,
            ]);
            foreach ($wfaResult as $r) {
                if (!empty($r['new_cancel'])) {
                    $result['new_entity_id'] = $r['new_cancel']->id();
                }
            }
            $result['form'] = $this->_getFullOrderFormData($orderId);
            $this->ok()->addMessage('Cancellation has been created', 'success');
        } catch (Exception $e) {
            $this->addMessage($e, 'error');
        }
        $this->respond($result);
    }

    public function action_cancellation_edit__POST()
    {

    }
    
    public function action_entity_delete__POST()
    {
        $result = []; 
        try {
            $orderId = $this->BRequest->post('order_id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);
            if (!$order) {
                throw new BException('Invalid order');
            }
            $type = $this->BRequest->post('entity_type');
            $entityId = $this->BRequest->post('entity_id');
            switch ($type) {
                case 'payment':
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesPayment', [
                        'order' => $order,
                        'payment_id' => $entityId,
                    ]);
                    break;

                case 'shipment':
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesShipment', [
                        'order' => $order,
                        'shipment_id' => $entityId,
                    ]);
                    break;

                case 'return':
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesReturn', [
                        'order' => $order,
                        'return_id' => $entityId,
                    ]);
                    break;

                case 'refund':
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesRefund', [
                        'order' => $order,
                        'refund_id' => $entityId,
                    ]);
                    break;

                case 'cancellation':
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesCancel', [
                        'order' => $order,
                        'cancel_id' => $entityId,
                    ]);
                    break;
            }
            $result['form'] = $this->_getFullOrderFormData($orderId);
            $this->ok()->addMessage($type . ' has been deleted successfully.', 'success');
        } catch (Exception $e) {
            $this->addMessage($e, 'error');
        }
        $this->respond($result);
    }




    public function onHeaderSearch($args)
    {
        $r = $this->BRequest->get();
        if (isset($r['q']) && $r['q'] != '') {
            $value = '%' . (string)$r['q'] . '%';
            $result = $this->Sellvana_Sales_Model_Order->orm()
                ->where(['OR' => [
                    ['id like ?', $value],
                    ['customer_email like ?', $value],
                    ['unique_id like ?', $value],
                    ['coupon_code like ?', $value],
                ]])->find_one();
            $args['result']['order'] = null;
            if ($result) {
                $args['result']['order'] = [
                    'priority' => 20,
                    'link' => '/sales/orders/form?id=' . $result->id(),
                ];
            }
        }
    }
}