<?php

/**
 * Class Sellvana_Sales_Frontend_Controller_Order
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Sales_Model_Order_Cancel $Sellvana_Sales_Model_Order_Cancel
 * @property Sellvana_Sales_Model_Order_Return $Sellvana_Sales_Model_Order_Return
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_Sales_Frontend_Controller_Orders extends FCom_Frontend_Controller_Abstract
{
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function authenticate($args = [])
    {
        if ($this->Sellvana_Customer_Model_Customer->isLoggedIn()) {
            return true;
        }
        if ($this->_action === 'view' && $this->BSession->get('allowed_orders')) {
            return true;
        }
        return false;
    }

    public function action_index()
    {
        $customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();

        $combinedStates = [
            'open' => [
                Sellvana_Sales_Model_Order_State_Overall::PLACED,
                Sellvana_Sales_Model_Order_State_Overall::REVIEW,
                Sellvana_Sales_Model_Order_State_Overall::FRAUD,
                Sellvana_Sales_Model_Order_State_Overall::LEGIT,
                Sellvana_Sales_Model_Order_State_Overall::PROCESSING,
                Sellvana_Sales_Model_Order_State_Overall::CANCEL_REQUESTED,
            ],
            'closed' => [
                Sellvana_Sales_Model_Order_State_Overall::COMPLETE,
                Sellvana_Sales_Model_Order_State_Overall::ARCHIVED,
            ],
            'canceled' => [
                Sellvana_Sales_Model_Order_State_Overall::CANCELED,
            ],
        ];

        $stateCounts = $this->Sellvana_Sales_Model_Order->orm()
            ->where('customer_id', $customerId)
            ->select('state_overall')->group_by('state_overall')
            ->select('(count(*))', 'cnt')
            ->find_many_assoc('state_overall', 'cnt');

        $counts = ['all' => 0, 'open' => 0, 'closed' => 0, 'canceled' => 0];
        foreach ($combinedStates as $status => $orderStates) {
            foreach ($orderStates as $state) {
                if (!empty($stateCounts[$state])) {
                    $cnt = $stateCounts[$state];
                    $counts['all'] += $cnt;
                    $counts[$status] += $cnt;
                }
            }
        }

        $currentStatus = $this->BRequest->get('status');
        if (!$currentStatus) {
            $currentStatus = 'all';
        }
        $orm = $this->Sellvana_Sales_Model_Order->orm()->where('customer_id', $customerId)->order_by_desc('id');
        if ($currentStatus !== 'all') {
            $orm->where_in('state_overall', $combinedStates[$currentStatus]);
        }
        $orders = $orm->find_many();

        $crumbs[] = ['label' => 'Account', 'href' => $this->BApp->href('customer/myaccount')];
        $crumbs[] = ['label' => 'Orders', 'active' => true];
        $this->layout('/orders/list');
        $this->view('breadcrumbs')->set('crumbs', $crumbs);
        $this->view('orders/list')->set([
            'status' => $currentStatus,
            'counts' => $counts,
            'orders' => $orders,
        ]);
    }

    /**
     * @return Sellvana_Sales_Model_Order
     * @throws BException
     */
    public function getOrder()
    {
        $uniqueId = $this->BRequest->get('id');
        $customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();

        $order = null;
        $allowedOrders = $this->BSession->get('allowed_orders');
        if (!empty($allowedOrders[$uniqueId])) {
            $order = $this->Sellvana_Sales_Model_Order->load($allowedOrders[$uniqueId]);
        }
        if (!$order && $customerId) {
            $order = $this->Sellvana_Sales_Model_Order->isOrderExists($uniqueId, $customerId);
        }
        if (!$order) {
            $this->BResponse->redirect('orders');
            return false;
        }
        return $order;
    }

    public function action_view()
    {
        $order = $this->getOrder();
        if (!$order) {
            $this->forward(false);
            return;
        }

        $crumbs[] = ['label' => 'Account', 'href' => $this->BApp->href('customer/myaccount')];
        $crumbs[] = ['label' => 'Orders', 'href' => $this->BApp->href('orders')];
        $crumbs[] = ['label' => 'View order', 'active' => true];
        $this->layout('/orders/view');
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('orders/view')->order = $order;
        // TODO: convert template to use only $order object
        $this->view('orders/view')->billing = $order->addressAsArray('billing');
        $this->view('orders/view')->shipping = $order->addressAsArray('shipping');
    }

    public function action_repeat__POST()
    {
        $order = $this->getOrder();
        if (!$order) {
            $this->forward(false);
            return;
        }

        $this->Sellvana_Sales_Model_Cart->sessionCart()->importDataFromOrder($order);

        $this->BResponse->redirect('checkout');
    }

    public function action_cancel()
    {
        $order = $this->getOrder();
        if (!$order) {
            $this->forward(false);
            return;
        }
        $crumbs[] = ['label' => 'Account', 'href' => $this->BApp->href('customer/myaccount')];
        $crumbs[] = ['label' => 'Orders', 'href' => $this->BApp->href('orders')];
        $crumbs[] = ['label' => 'Cancel Items', 'active' => true];
        $this->layout('/orders/cancel');
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('orders/cancel')->order = $order;
    }

    public function action_cancel__POST()
    {
        $order = $this->getOrder();
        $cancel = $this->BRequest->post('cancel');
        try {
            $result = $this->Sellvana_Sales_Main->workflowAction('customerRequestsToCancelItems', [
                'order' => $order,
                'qtys' => $cancel,
            ]);
            if ($result['errors'])
            $this->message('Items canceled successfully');
            $this->BResponse->redirect('orders');
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
            $this->BResponse->redirect(true);
        }
    }

    public function action_return()
    {
        $order = $this->getOrder();
        if (!$order) {
            $this->forward(false);
            return;
        }
        $crumbs[] = ['label' => 'Account', 'href' => $this->BApp->href('customer/myaccount')];
        $crumbs[] = ['label' => 'Orders', 'href' => $this->BApp->href('orders')];
        $crumbs[] = ['label' => 'Return Items', 'active' => true];
        $this->layout('/orders/return');
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('orders/return')->order = $order;
    }

    public function action_return__POST()
    {
        $order = $this->getOrder();
        $return = $this->BRequest->post('return');
        try {
            $this->Sellvana_Sales_Main->workflowAction('customerRequestsToReturnItems', [
                'order' => $order,
                'qtys' => $return,
            ]);
            $this->message('RMA has been requested successfully');
            $this->BResponse->redirect('orders');
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
            $this->BResponse->redirect(true);
        }
    }
}
