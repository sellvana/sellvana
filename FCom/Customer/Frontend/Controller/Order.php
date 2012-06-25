<?php

class FCom_Customer_Frontend_Controller_Order extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_index()
    {
        $customerId = FCom_Customer_Model_Customer::sessionUserId();
        $orders = FCom_Sales_Model_Order::i()->orm()->where('user_id', $customerId)->find_many();
        $this->view('customer/order/list')->orders = $orders;
        $this->layout('/customer/order/list');
    }

    public function action_view()
    {
        $id = BRequest::get('id');
        $customerId = FCom_Customer_Model_Customer::sessionUserId();
        $order = FCom_Sales_Model_Order::i()->orm()->where('id', $id)
                ->where('user_id', $customerId)->find_one();
        if (!$order) {
            BResponse::i()->redirect(Bapp::href('customer/order'));
        }

        $orderItems = FCom_Sales_Model_OrderItem::i()->orm()->where("order_id", $order->id())->find_many();
        $this->view('customer/order/view')->order = $order;
        $this->view('customer/order/view')->orderItems = $orderItems;
        $this->layout('/customer/order/view');
    }

}