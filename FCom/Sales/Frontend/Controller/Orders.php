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
        $orders = $this->FCom_Sales_Model_Order->getOrders($customerId);

        $crumbs[] = ['label' => 'Account', 'href' => $this->BApp->href('customer/myaccount')];
        $crumbs[] = ['label' => 'Orders', 'active' => true];
        $this->layout('/orders/list');
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('order/list')->orders = $orders;
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
        $this->view('order/view')->order = $order;
        // TODO: convert template to use only $order object
        $this->view('order/view')->billing = $order->addressAsArray('billing');
        $this->view('order/view')->shipping = $order->addressAsArray('shipping');
    }

}
