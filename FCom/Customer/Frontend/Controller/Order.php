<?php

class FCom_Customer_Frontend_Controller_Order extends FCom_Frontend_Controller_Abstract
{
    public function authenticate( $args = [] )
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath() == '/login';
    }

    public function action_index()
    {
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $orders = FCom_Sales_Model_Order::i()->getOrders( $customerId );

        $crumbs[] = [ 'label' => 'Account', 'href' => Bapp::href( 'customer/myaccount' ) ];
        $crumbs[] = [ 'label' => 'Orders', 'active' => true ];
        $this->view( 'breadcrumbs' )->crumbs = $crumbs;
        $this->view( 'customer/order/list' )->orders = $orders;
        $this->layout( '/customer/order/list' );
    }

    public function action_view()
    {
        $uniqueId = BRequest::get( 'id' );
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $order = FCom_Sales_Model_Order::i()->isOrderExists( $uniqueId, $customerId );
        if ( !$order ) {
            BResponse::i()->redirect( 'customer/order' );
            return;
        }

        $crumbs[] = [ 'label' => 'Account', 'href' => Bapp::href( 'customer/myaccount' ) ];
        $crumbs[] = [ 'label' => 'Orders', 'href' => Bapp::href( 'customer/order' ) ];
        $crumbs[] = [ 'label' => 'View order', 'active' => true ];
        $this->view( 'breadcrumbs' )->crumbs = $crumbs;
        $this->view( 'customer/order/view' )->order = $order;
        $this->view( 'customer/order/view' )->billing = $order->billing();
        $this->view( 'customer/order/view' )->shipping = $order->shipping();
        $this->layout( '/customer/order/view' );
    }

}
