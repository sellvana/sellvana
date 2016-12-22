<?php

class FCom_AdminSPA_AdminSPA_Controller_Users extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function action_grid_config()
    {
        $config = [
            'id' => 'users',
            'data_url' => $this->BApp->href('users/grid_data'),
            'columns' => [
                ['type' => 'row-select'],
                ['type' => 'actions'],
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
        $config = $this->normalizeGridConfig($config);
        $this->respond($config);
    }

    public function action_grid_data()
    {
        $data = $this->FCom_Admin_Model_User->orm('u')->paginate();
        $result = [
            'rows' => BDb::many_as_array($data['rows']),
            'state' => $data['state'],
        ];
        $this->respond($result);
    }
}