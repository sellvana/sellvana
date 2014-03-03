<?php

class FCom_Customer_Frontend_Controller_Order extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_index()
    {
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $orders = FCom_Sales_Model_Order::i()->orm()->where('customer_id', $customerId)->find_many();

        $crumbs[] = array('label'=>'Account', 'href'=>Bapp::href('customer/myaccount'));
        $crumbs[] = array('label'=>'Orders', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/order/list')->orders = $orders;
        $this->layout('/customer/order/list');
    }

    public function action_view()
    {
        $id = BRequest::get('id');
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $order = FCom_Sales_Model_Order::i()->orm()->where('id', $id)
                ->where('customer_id', $customerId)->find_one();
        if (!$order) {
            BResponse::i()->redirect('customer/order');
            return;
        }

        $orderItems = FCom_Sales_Model_Order_Item::i()->orm()->where("order_id", $order->id())->find_many();

        $crumbs[] = array('label'=>'Account', 'href'=>Bapp::href('customer/myaccount'));
        $crumbs[] = array('label'=>'Orders', 'href'=>Bapp::href('customer/order'));
        $crumbs[] = array('label'=>'View order', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/order/view')->order = $order;
        $this->view('customer/order/view')->orderItems = $orderItems;
        $this->layout('/customer/order/view');
    }

}
