<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Frontend_Controller_Order
 *
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 */
class FCom_Sales_Frontend_Controller_Orders extends FCom_Frontend_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function authenticate($args = [])
    {
        if ($this->FCom_Customer_Model_Customer->isLoggedIn()) {
            return true;
        }
        if ($this->_action === 'view' && $this->BSession->get('allowed_orders')) {
            return true;
        }
        return false;
    }

    public function action_index()
    {
        $customerId = $this->FCom_Customer_Model_Customer->sessionUserId();

        $combinedStates = [
            'open' => [
                FCom_Sales_Model_Order_State_Overall::PLACED,
                FCom_Sales_Model_Order_State_Overall::REVIEW,
                FCom_Sales_Model_Order_State_Overall::FRAUD,
                FCom_Sales_Model_Order_State_Overall::LEGIT,
                FCom_Sales_Model_Order_State_Overall::PROCESSING,
                FCom_Sales_Model_Order_State_Overall::CANCEL_REQUESTED,
            ],
            'closed' => [
                FCom_Sales_Model_Order_State_Overall::COMPLETE,
                FCom_Sales_Model_Order_State_Overall::ARCHIVED,
            ],
            'canceled' => [
                FCom_Sales_Model_Order_State_Overall::CANCELED,
            ],
        ];

        $stateCounts = $this->FCom_Sales_Model_Order->orm()
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
        $orm = $this->FCom_Sales_Model_Order->orm()->where('customer_id', $customerId)->order_by_desc('id');
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

    public function action_view()
    {
        $uniqueId = $this->BRequest->get('id');
        $customerId = $this->FCom_Customer_Model_Customer->sessionUserId();

        $order = null;
        $allowedOrders = $this->BSession->get('allowed_orders');
        if (!empty($allowedOrders[$uniqueId])) {
            $order = $this->FCom_Sales_Model_Order->load($allowedOrders[$uniqueId]);
        }
        if (!$order && $customerId) {
            $order = $this->FCom_Sales_Model_Order->isOrderExists($uniqueId, $customerId);
        }
        if (!$order) {
            $this->BResponse->redirect('orders');
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

}
