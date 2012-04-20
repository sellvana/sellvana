<?php

class FCom_Customer_Frontend_Controller_Account extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_myaccount()
    {
        $this->layout('/myaccount');
    }
}