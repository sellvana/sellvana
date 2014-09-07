<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Customer_Frontend_Controller_Order extends FCom_Frontend_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function authenticate($args = [])
    {
        return $this->FCom_Customer_Model_Customer->isLoggedIn() || $this->BRequest->rawPath() == '/login';
    }

    public function action_index()
    {
        $customerId = $this->FCom_Customer_Model_Customer->sessionUserId();
        $orders = $this->FCom_Sales_Model_Order->getOrders($customerId);

        $crumbs[] = ['label' => 'Account', 'href' => $this->BApp->href('customer/myaccount')];
        $crumbs[] = ['label' => 'Orders', 'active' => true];
        $this->layout('/customer/order/list');
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/order/list')->orders = $orders;
    }

    public function action_view()
    {
        $uniqueId = $this->BRequest->get('id');
        $customerId = $this->FCom_Customer_Model_Customer->sessionUserId();
        $order = $this->FCom_Sales_Model_Order->isOrderExists($uniqueId, $customerId);
        if (!$order) {
            $this->BResponse->redirect('customer/order');
            return;
        }

        $crumbs[] = ['label' => 'Account', 'href' => $this->BApp->href('customer/myaccount')];
        $crumbs[] = ['label' => 'Orders', 'href' => $this->BApp->href('customer/order')];
        $crumbs[] = ['label' => 'View order', 'active' => true];
        $this->layout('/customer/order/view');
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/order/view')->order = $order;
        $this->view('customer/order/view')->billing = $order->billing();
        $this->view('customer/order/view')->shipping = $order->shipping();
    }

}
