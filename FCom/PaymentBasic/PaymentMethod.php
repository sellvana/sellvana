<?php

class FCom_PaymentBasic_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    protected $_cart;
    protected $_order;

    function __construct()
    {
        $this->_name = 'Check / Money Order';
    }

    public function initCart( $cart )
    {
        $this->_cart = $cart;
        return $this;
    }

    public function getCheckoutFormView()
    {
        return BLayout::i()->view( 'check_mo/form' );
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
