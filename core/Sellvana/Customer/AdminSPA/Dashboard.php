<?php

class Sellvana_Customer_AdminSPA_Dashboard extends BClass
{
    public function widgetNewCustomers($filter)
    {
        $customers = $this->Sellvana_Customer_Admin_Dashboard->getCustomerRecent();
        return [
            'customers' => $this->BDb->many_as_array($customers),
        ];
    }
}