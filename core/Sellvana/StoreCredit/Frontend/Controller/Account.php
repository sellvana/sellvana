<?php

class Sellvana_StoreCredit_Frontend_Controller_Account extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        if (!$customer) {
            $this->forward('unauthenticated');
            return;
        }
        $this->BResponse->nocache();
        $layout = $this->BLayout;
        $this->layout('/storecredit/account');
        $layout->getView('breadcrumbs')->crumbs = ['home', ['label' => 'Store Credit', 'active' => true]];
        $balance = $this->Sellvana_StoreCredit_Model_Balance->load($customer->id(), 'customer_id');
        $layout->getView('storecredit/account')->set('balance', $balance);
    }
}