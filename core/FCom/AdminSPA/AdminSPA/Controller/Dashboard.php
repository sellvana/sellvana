<?php

class FCom_AdminSPA_AdminSPA_Controller_Dashboard extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function action_index()
    {
        $dashboard = [
            'config' => [
                'widgets' => [

                ],
            ],
            'data' => [
                'at_glance' => [
                    'total_sales' => 12345,
                    'total_customers' => 1234,
                    'new_customers' => 123,
                    'total_revenue' => 2384,
                ],
            ],
        ];
        $this->respond($dashboard);
    }
}