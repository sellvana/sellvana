<?php

class FCom_AdminSPA_AdminSPA_Controller_Users extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        return [
            'id' => 'users',
            'data_url' => 'users/grid_data',
            'columns' => [
                ['type' => 'row-select', 'width' => 80],
                ['type' => 'actions', 'width' => 80, 'actions' => [
                    ['type' => 'edit', 'link' => '/users/form?id='],
                    ['type' => 'delete', 'delete_url' => 'users/grid_delete'],
                ]],
                ['field' => 'id', 'label' => 'ID'],
                ['field' => 'username', 'label' => 'Username'],
                ['field' => 'firstname', 'label' => 'First Name'],
                ['field' => 'lastname', 'label' => 'Last Name'],
                ['field' => 'email', 'label' => 'Email'],
            ],
            'filters' => [
                ['field' => 'id', 'type' => 'number-range'],
                ['field' => 'username'],
            ],
            'export' => [
                ['type' => 'csv', 'label' => 'CSV'],
            ],
            'bulk_actions' => [
                ['name' => 'delete', 'label' => 'Delete'],
            ],
        ];
    }

    public function getGridOrm()
    {
        return $data = $this->FCom_Admin_Model_User->orm('u');
    }

    public function action_grid_delete__POST()
    {

    }

    public function action_form_data()
    {

    }
}