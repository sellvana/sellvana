<?php

/**
 * Class FCom_AdminSPA_AdminSPA_Controller_Users
 *
 * @property FCom_Admin_Model_Role FCom_Admin_Model_Role
 */
class FCom_AdminSPA_AdminSPA_Controller_Roles extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    static protected $_modelClass = 'FCom_Admin_Model_Role';
    static protected $_modelName = 'role';
    static protected $_recordName = 'Role';

    public function getGridConfig()
    {
        return [
            'id' => 'roles',
            'title' => 'Roles & Permissions',
            'data_url' => 'roles/grid_data',
            'columns' => [
                ['type' => 'row-select', 'width' => 80],
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'role_name', 'label' => 'Role Name', 'width' => 250,
                    'datacell_template' => '<td><a :href="\'#/roles/form?id=\'+row.id">{{row.role_name}}</a></td>'],
                ['name' => 'create_at', 'label' => 'Created At'],
                ['name' => 'update_at', 'label' => 'Updated At'],
            ],
            'filters' => true,
            'export' => true,
            'pager' => true,
            'bulk_actions' => [
                ['name' => 'delete', 'label' => 'Delete'],
            ],
            'page_actions' => [
                ['name' => 'new', 'label' => 'Add New Role', 'button_class' => 'button1', 'link' => '/roles/form', 'group' => 'new'],
            ],
            'state' => [
                'sc' => 'role_name asc'
            ]
        ];
    }

    public function getFormData()
    {
        $roleId = $this->BRequest->get('id');
        $bool = [0 => 'no', 1 => 'Yes'];

        if ($roleId) {
            $role = $this->FCom_Admin_Model_Role->load($roleId);
            if (!$role) {
                throw new BException('Role not found');
            }
        } else {
            $role = $this->FCom_Admin_Model_Role->create();
        }

        $allPermissions = $this->FCom_Admin_Model_Role->getAllPermissions();
        $permOptions = [];
        foreach ($allPermissions as $path => $perm) {
            $permOptions[] = ['id' => $path, 'text' => $path . ' - ' . $perm['title']];
        }

        $result = [];
        $result['form']['role'] = [
            'id' => $role->id(),
            'role_name' => $role->get('role_name'),
            'permissions' => explode("\n", $role->get('permissions_data')),
        ];
        $result['form']['config']['title'] = $roleId ? $role->get('role_name') : 'New Role';
        $result['form']['config']['tabs'] = '/roles/form';
        $result['form']['config']['fields'] = [
            'default' => ['model' => 'role', 'tab' => 'main'],
            ['name' => 'role_name', 'label' => 'Role Name', 'required' => true],
            ['name' => 'permissions', 'label' => 'Permissions', 'options' => $permOptions, 'type' => 'select2', 'multiple' => true],
        ];

        $result['form']['config']['page_actions'] = true;

        return $result;
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            $data = $this->BRequest->post('role');
            $roleId = (int)$data['id'];
            if ($roleId) {
                $role = $this->FCom_Admin_Model_Role->load($roleId);
                if (!$role) {
                    throw new BException('Invalid role id');
                }
            } else {
                $role = $this->FCom_Admin_Model_Role->create();
            }
            $role->set([
                'role_name' => $data['role_name'],
                'permissions_data' => join("\n", $data['permissions']),
            ])->save();
            $this->ok()->addMessage('Role has been updated', 'success');
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }
}