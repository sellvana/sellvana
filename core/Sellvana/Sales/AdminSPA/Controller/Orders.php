<?php

class Sellvana_Sales_AdminSPA_Controller_Orders extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function authenticate($args = [])
    {
         return true;
    }

    public function action_grid_config()
    {
        $json = <<<EOT
{
                id: 'sales/orders',
                data_url: 'https://127.0.0.1/sellvana/admin-spa/sales/orders/data',
                columns: [
                    {type: 'select-checkbox'},
                    {type: 'actions'},
                    {field: 'id', label: 'ID'},
                    {
                        field: 'state_overall', label: 'Overall State', options: [
                        {value: 'pending', label: 'Pending'},
                        {value: 'processing', label: 'Processing'},
                        {value: 'shipped', label: 'Shipped'}
                    ]
                    }
                ],
                filters: [
                    {field: 'id'},
                    {field: 'state_overall', type: 'multiselect'}
                ],
                export: {
                    format_options: [
                        {value: 'csv', label: 'CSV'}
                    ]
                },
                pager: {
                    pagesize_options: [5, 10, 20, 50, 100]
                }
            }
EOT;
        $config = $this->BUtil->fromJson($json);
        $this->BResponse->json($config);

    }

    public function action_grid_data()
    {

    }

    public function action_form_config()
    {
        $config = [
            'tabs' => [

            ],
        ];
        $this->BResponse->json($config);
    }
}