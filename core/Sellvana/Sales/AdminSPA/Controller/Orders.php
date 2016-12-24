<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Sales_Model_Order Sellvana_Sales_Model_Order
 */
class Sellvana_Sales_AdminSPA_Controller_Orders extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function action_grid_config()
    {
        $config = [
            'id' => 'users',
            'data_url' => 'orders/grid_data',
            'columns' => [
                ['type' => 'row-select'],
                ['type' => 'actions', 'actions' => [
                    ['type' => 'edit', 'link' => '/sales/orders/form?id={id}', 'icon_class' => 'fa fa-pencil'],
                    ['type' => 'delete', 'delete_url' => 'orders/grid_delete', 'icon_class' => 'fa fa-trash'],
                ]],
                ['field' => 'id', 'label' => 'Internal ID'],
                ['field' => 'unique_id', 'label' => 'Public ID'],
                ['field' => 'state_overall', 'label' => 'Overall State', 'options' => [
                    'value' => 'pending', 'label' => 'Pending',
                ]],
                ['field' => 'customer_firstname', 'label' => 'Last Name'],
                ['field' => 'customer_lastname', 'label' => 'Last Name'],
                ['field' => 'customer_email', 'label' => 'Email'],
            ],
            'filters' => [
                ['field' => 'id', 'type' => 'number-range'],
                ['field' => 'unique_id', 'type' => 'text'],
            ],
            'export' => [
                'format_options' => [
                    ['value' => 'csv', 'label' => 'CSV'],
                ],
            ],
            'pager' => [
                'pagesize_options' => [5, 10, 20, 50, 100],
            ],
        ];
        $config = $this->normalizeGridConfig($config);
        $this->respond($config);

    }

    public function action_grid_data()
    {
        $data = $this->Sellvana_Sales_Model_Order->orm('u')->paginate();
        $result = [
            'rows' => BDb::many_as_array($data['rows']),
            'state' => $data['state'],
        ];
        $this->respond($result);
    }

    public function action_grid_delete__POST()
    {

    }

    public function action_form_data()
    {
        $config = [
            'tabs' => [

            ],
        ];
        $this->BResponse->json($config);
    }
}