<?php

abstract class FCom_Sales_Model_Abstract_ShippingMethod extends BClass
{
    public function getService($serviceKey)
    {
        $services = $this->getServices();
        if (!empty($services[$serviceKey])) {
            return $services[$serviceKey];
        }
        return false;
    }
}