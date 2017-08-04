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
    static protected $_recordName = (('Role'));

    public function getGridConfig()
    {
        return [
            static::ID => 'roles',
            static::TITLE => (('Roles & Permissions')),
            static::DATA_URL => 'roles/grid_data',
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT, static::WIDTH => 80],
                [static::NAME => 'id', static::LABEL => (('ID'))],
                [static::NAME => 'role_name', static::LABEL => (('Role Name')), static::WIDTH => 250,
                    static::DATACELL_TEMPLATE => '<td><a :href="\'#/roles/form?id=\'+row.id">{{row.role_name}}</a></td>'],
                [static::NAME => 'create_at', static::LABEL => (('Created At'))],
                [static::NAME => 'update_at', static::LABEL => (('Updated At'))],
            ],
            static::FILTERS => true,
            static::EXPORT => true,
            static::PAGER => true,
            static::BULK_ACTIONS => [
                [static::NAME => 'delete', static::LABEL => (('Delete'))],
            ],
            static::PAGE_ACTIONS => [
                [static::NAME => 'new', static::LABEL => (('Add New Role')), static::BUTTON_CLASS => 'button1', static::LINK => '/roles/form', static::GROUP => 'new'],
            ],
            'state' => [
                'sc' => 'role_name asc'
            ]
        ];
    }

    public function getFormData()
    {
        $roleId = $this->BRequest->get('id');
        $bool = [0 => (('no')), 1 => (('Yes'))];

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
            $permOptions[] = [static::ID => $path, 'text' => $path . ' - ' . $perm[static::TITLE]];
        }

        $result = [];
        $result[static::FORM]['role'] = [
            static::ID => $role->id(),
            'role_name' => $role->get('role_name'),
            'permissions' => explode("\n", $role->get('permissions_data')),
        ];
        $result[static::FORM][static::CONFIG][static::TITLE] = $roleId ? $role->get('role_name') : (('New Role'));
        $result[static::FORM][static::CONFIG][static::TABS] = '/roles/form';
        $result[static::FORM][static::CONFIG][static::FIELDS] = [
            static::DEFAULT_FIELD => [static::MODEL => 'role', static::TAB => 'main'],
            [static::NAME => 'role_name', static::LABEL => (('Role Name')), static::REQUIRED => true],
            [static::NAME => 'permissions', static::LABEL => (('Permissions')), static::OPTIONS => $permOptions, static::TYPE => 'select2', static::MULTIPLE => true],
        ];

        $result[static::FORM][static::CONFIG][static::PAGE_ACTIONS] = $this->getDefaultFormPageActions();

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