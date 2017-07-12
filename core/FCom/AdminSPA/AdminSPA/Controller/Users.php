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
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'username', 'label' => 'Username'],
                ['name' => 'firstname', 'label' => 'First Name'],
                ['name' => 'lastname', 'label' => 'Last Name'],
                ['name' => 'email', 'label' => 'Email'],
            ],
            'filters' => true,
            'export' => true,
            'pager' => true,
            'bulk_actions' => [
                ['name' => 'delete', 'label' => 'Delete'],
            ],
            'state' => [
                'sc' => 'username asc'
            ]
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
        $userId = $this->BRequest->get('id');
        /** @var FCom_Admin_Model_User $user */
        $user = $this->FCom_Admin_Model_User->load($userId);

        $result = [];
        $result['form']['config']['tabs'] = $this->getFormTabs('/users/form');
        $result['form']['config']['fields'] = [

        ];
        $result['form']['user'] = $user->as_array();
        $result['form']['avatar'] = ['thumb_url' => $user->thumb(100)];

        $this->respond($result);
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {

            $userId = $this->BRequest->request('id');
            if (!$userId) {
                throw new BException('Invalid user id');
            }
            $user = $this->FCom_Admin_Model_User->load($userId);
            if (!$user) {
                throw new BException('Invalid user id');
            }
            $this->ok()->addMessage('User has been updated');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function action_form_delete__POST()
    {
        $result = [];
        try {
            $userId = $this->BRequest->request('id');
            if (!$userId) {
                throw new BException('Invalid user id');
            }
            $user = $this->FCom_Admin_Model_User->load($userId);
            if (!$user) {
                throw new BException('Invalid user id');
            }
            $user->delete();
            $this->ok()->addMessage('User has been deleted');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }
}