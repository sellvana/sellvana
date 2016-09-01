<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Orders
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 * @property Sellvana_Sales_Model_Order_Payment_State_Overall $Sellvana_Sales_Model_Order_Payment_State_Overall
 * @property Sellvana_Sales_Model_Order_Payment_State_Custom $Sellvana_Sales_Model_Order_Payment_State_Custom
 * @property Sellvana_Sales_Model_Order_Payment_Transaction $Sellvana_Sales_Model_Order_Payment_Transaction
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

    protected static $_typeToPaymentActions = [
        Sellvana_Sales_Model_Order_Payment_Transaction::CAPTURE => 'capture',
        Sellvana_Sales_Model_Order_Payment_Transaction::REFUND => 'refund',
        Sellvana_Sales_Model_Order_Payment_Transaction::REAUTHORIZATION => 'reauthorize',
        Sellvana_Sales_Model_Order_Payment_Transaction::AUTHORIZATION => 'authorize',
        Sellvana_Sales_Model_Order_Payment_Transaction::VOID => 'void',
    ];


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
            $amounts = $this->BRequest->post('amounts');
            $totals = $this->BRequest->post('totals');

            $this->Sellvana_Sales_Main->workflowAction('adminCreatesPayment', [
                'order' => $order,
                'data' => $paymentData,
                'amounts' => $amounts,
                'totals' => $totals,
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
        $result['otherInfo'] = $order->getStateInfo();
        $this->BResponse->json($result);
    }

    public function action_transaction_action__POST()
    {
        try {
            $orderId = $this->BRequest->get('id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $actions = $this->BRequest->post('actions');
            
            foreach ($actions as $paymentId => $transactions) {
                /** @var Sellvana_Sales_Model_Order_Payment $payment */
                $payment = $this->Sellvana_Sales_Model_Order_Payment->load((int)$paymentId);

                foreach ($transactions as $transactionId => $action) {
                    if (empty($action['type']) || !array_key_exists($action['type'], self::$_typeToPaymentActions)) {
                        throw new BException('Unknown transaction type');
                    }

                    $parent = $this->Sellvana_Sales_Model_Order_Payment_Transaction->load($transactionId);

                    $type = $action['type'];
                    $method = self::$_typeToPaymentActions[$type];
                    if ($method != 'void') {
                        $amount = array_key_exists('amount', $action) ? $action['amount'] : null;
                        $payment->$method($amount, $parent);
                    } else {
                        $payment->$method($parent);
                    }
                }
            }

            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Transaction has been created');
        } catch (BException $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['payments'] = (string)$this->view('order/orders-form/payments')->set('model', $order);
        $this->BResponse->json($result);

    }

    public function action_send_root_transaction_url__POST()
    {
        try {
            $orderId = $this->BRequest->get('id');
            $paymentIds = $this->BRequest->post('payments') ?: [];
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            if (!count($paymentIds)) {
                throw new BException('Invalid payment');
            }

            /** @var Sellvana_Sales_Model_Order_Payment[] $payments */
            $payments = $this->Sellvana_Sales_Model_Order_Payment->orm('p')
                ->where_in('id', array_keys($paymentIds))
                ->where('order_id', $orderId)
                ->find_many();

            if (!count($payments)) {
                throw new BException('Invalid payment');
            }

            foreach ($payments as $payment) {
                $url = $payment->getRootTransactionUrl();
                $view = $this->BLayout->getView('email/sales/order-payment-create-root-transaction');
                if (!$view instanceof BViewEmpty) {
                    $view->set(['order' => $order, 'url' => $url, 'payment' => $payment])
                        ->email();
                }

            }

            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Customer has been notified');
        } catch (BException $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['payments'] = (string)$this->view('order/orders-form/payments')->set('model', $order);
        $this->BResponse->json($result);
    }
}