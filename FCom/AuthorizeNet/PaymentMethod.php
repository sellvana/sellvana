<?php

class FCom_AuthorizeNet_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{

    function __construct()
    {
        $this->_name = 'Authorize.net';
    }

    public function getCheckoutFormView()
    {
        return BLayout::i()->view('authorizenet/form');
    }

    public function payOnCheckout()
    {
        die("Authorize.net payment not implemented yet");
    }

    public function getOrder()
    {
        return $this->salesEntity;
    }

    public function getCardNumber()
    {
        // todo return card number
        $cc_number = null;
        return $cc_number;
    }

    /**
     * @return array
     */
    public function cardTypes($config = array())
    {
        return FCom_AuthorizeNet_Model_Settings::cardTypes();
    }

    /**
     * @return array|null
     */
    public function config()
    {
        $config = BConfig::i();
        return $config->get('modules/FCom_AuthorizeNet/aim');
    }
}
