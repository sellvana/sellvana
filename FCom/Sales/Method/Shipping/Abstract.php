<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Sales_Method_Shipping_Abstract extends BClass implements
    FCom_Sales_Method_Shipping_Interface
{
    protected $_sortOrder = 50;

    public function getName()
    {
        return $this->_name;
    }

    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    public function getService($serviceKey)
    {
        $services = $this->getServices();
        if (!empty($services[$serviceKey])) {
            return $services[$serviceKey];
        }
        return false;
    }

    public function getServices()
    {
        return [];
    }

    public function getServicesSelected()
    {
        return $this->getServices();
    }
}
