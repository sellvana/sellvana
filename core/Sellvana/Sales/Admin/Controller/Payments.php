<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Orders
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 * @property Sellvana_Sales_Model_Order_Payment_State_Overall $Sellvana_Sales_Model_Order_Payment_State_Overall
 * @property Sellvana_Sales_Model_Order_Payment_State_Custom $Sellvana_Sales_Model_Order_Payment_State_Custom
 */

class Sellvana_Sales_Admin_Controller_Payments extends Sellvana_Sales_Admin_Controller_Abstract
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'payments';
    protected $_modelClass = 'Sellvana_Sales_Model_Order_Payment';
    protected $_gridTitle = 'Payments';
    protected $_recordName = 'Payment';
    protected $_mainTableAlias = 'p';
    protected $_permission = 'sales/payments';
    protected $_navPath = 'sales/payments';
    protected $_gridLayoutName = '/payments';

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);

        /** @var FCom_Admin_View_Grid $view */
        $view = $args['page_view'];
        $actions = (array)$view->get('actions');
        unset($actions['new']);
        $view->set('actions', $actions);
    }

    public function gridConfig()
    {
        $methods = $this->Sellvana_Sales_Main->getPaymentMethods();
        $methodOptions = [];
        foreach ($methods as $k => $m) {
            $methodOptions[$k] = $m->getName();
        }
        $stateOverallOptions = $this->Sellvana_Sales_Model_Order_Payment_State_Overall->getAllValueLabels();
        $stateCustomOptions = $this->Sellvana_Sales_Model_Order_Payment_State_Custom->getAllValueLabels();

        $config = parent::gridConfig();
        $config['edit_url'] = $this->BApp->href($this->_gridHref . '/mass_change_state');
        $config['orm'] = $this->Sellvana_Sales_Model_Order_Payment->orm('p')
            ->select('p.*')
            ->join('Sellvana_Sales_Model_Order', ['o.id', '=', 'p.order_id'], 'o')
            ->select('o.unique_id', 'order_unique_id');

        //TODO: add transactions info

        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID'],
            ['name' => 'order_unique_id', 'label' => 'Order #'],
            ['name' => 'payment_method', 'label' => 'Method', 'options' => $methodOptions],
            ['name' => 'amount_authorized', 'label' => 'Authorized', 'cell' => 'currency'],
            ['name' => 'amount_due', 'label' => 'Due', 'cell' => 'currency'],
            ['name' => 'amount_captured', 'label' => 'Captured', 'cell' => 'currency'],
            ['name' => 'amount_refunded', 'label' => 'Refunded', 'cell' => 'currency'],
            ['name' => 'state_overall', 'label' => 'Overall Status', 'options' => $stateOverallOptions],
            ['name' => 'state_custom', 'label' => 'Custom Status', 'options' => $stateCustomOptions],
            ['name' => 'create_at', 'label' => 'Created', 'cell' => 'datetime'],
            ['name' => 'update_at', 'label' => 'Updated', 'cell' => 'datetime'],
            ['name' => 'transactions', 'label' => 'Transactions'],
        ];
        $config['actions'] = [
            //'add' => ['caption' => 'Add payment'],
            'delete' => ['caption' => 'Remove'],
            'mark_paid' => [
                'caption'      => 'Mark as paid',
                'type'         => 'button',
                'class'        => 'btn btn-primary',
                'isMassAction' => true,
                'callback'     => 'markAsPaid',
            ],
        ];
        $config['filters'] = [
            ['field' => 'order_unique_id', 'type' => 'number-range'],
            ['field' => 'payment_method', 'type' => 'multiselect'],
            ['field' => 'amount_due', 'type' => 'number-range'],
            ['field' => 'state_overall', 'type' => 'multiselect'],
            ['field' => 'state_custom', 'type' => 'multiselect'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'update_at', 'type' => 'date-range'],
        ];

        return $config;
    }

    public function action_mass_change_state__POST()
    {
        $request = $this->BRequest;
        $ids = explode(',', $request->post('id'));
        $payments = $this->Sellvana_Sales_Model_Order_Payment->orm('op')->where_in('id', $ids)->find_many();
        $action = 'adminMarksPaymentAs' . ucfirst($request->post('state_overall'));

        foreach ($payments as $payment) {
            $this->Sellvana_Sales_Main->workflowAction($action, [
                'payment' => $payment
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

            $paymentData = $this->BRequest->post('payment');
            $qtys = $this->BRequest->post('qtys');

            $this->Sellvana_Sales_Main->workflowAction('adminCreatesPayment', [
                'order' => $order,
                'data' => $paymentData,
                'qtys' => $qtys,
            ]);
            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Payment has been created');
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['payments'] = (string)$this->view('order/orders-form/payments')->set('model', $order);
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

            $payments = $this->BRequest->post('payments');
            $transactions = $this->BRequest->post('transactions');
            $delete = $this->BRequest->post('delete');
            if ($payments) {
                foreach ($payments as $id => $s) {
                    $this->Sellvana_Sales_Main->workflowAction('adminUpdatesPayment', [
                        'order' => $order,
                        'payment_id' => $id,
                        'data' => $s,
                    ]);
                }
            }
            if ($transactions) {
                foreach ($transactions as $id => $p) {
                    $this->Sellvana_Sales_Main->workflowAction('adminUpdatesTransaction', [
                        'order' => $order,
                        'transaction_id' => $id,
                        'data' => $p,
                    ]);
                }
            }
            if ($delete) {
                foreach ($delete as $id => $_) {
                    $this->Sellvana_Sales_Main->workflowAction('adminDeletesPayment', [
                        'order' => $order,
                        'payment_id' => $id,
                    ]);
                }
            }
            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Payment updates have been applied');
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['payments'] = (string)$this->view('order/orders-form/payments')->set('model', $order);
        $this->BResponse->json($result);
    }

    public function action_processor__POST()
    {
        try {
            $orderId = $this->BRequest->get('id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $payments = $this->BRequest->post('payments');
            if ($payments) {
                $methods = $this->Sellvana_Sales_Main->getPaymentMethods();
                foreach ($payments as $id => $action) {
                    foreach ($order->payments() as $payment) {
                        if ($payment->id() == $id) {
                            break;
                        }
                    }

                    if (!isset($payment)) {
                        throw new BException('Payment doesn\'t belong to this order');
                    }

                    $code = $payment->get('payment_method');
                    if (empty($methods[$code])) {
                        throw new BException('Invalid payment method');
                    }

                    if (!$payment->isActionAvailable($action)) {
                        throw new BException('This action is not available for this payment');
                    }

                    $payment->{$action}();

                    unset($payment);
                }
            }
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }
    }
}