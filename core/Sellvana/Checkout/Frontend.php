<?php

/**
 * Class Sellvana_Checkout_Frontend
 */
class Sellvana_Checkout_Frontend extends BClass
{
    /**
     * Clear selected addresses from the session
     *
     * @param $args
     */
    public function onCustomerLogout($args)
    {
        $this->BSession->set('shipping_address_id', null);
        $this->BSession->set('billing_address_id', null);
    }
}