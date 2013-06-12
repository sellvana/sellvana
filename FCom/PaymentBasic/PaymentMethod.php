<?php

class FCom_PaymentBasic_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    protected $_cart;
    protected $_order;

    public function initCart($cart)
    {
        $this->_cart = $cart;
        return $this;
    }

    public function initOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    public function getName()
    {
        return 'Check / Money Order';
    }

    public function getCheckoutFormView()
    {
        return BLayout::i()->view('check_mo/form');
    }

    public function payOnCheckout()
    {
        $this->authorize();
        return $this;
    }

    public function authorize()
    {
        return true;
    }

    public function capture()
    {
        return true;
    }
}
