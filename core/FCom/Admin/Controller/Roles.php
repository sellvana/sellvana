<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_Controller_Roles
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class FCom_Admin_Controller_Roles extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass      = __CLASS__;
    protected        $_permission     = 'system/roles';
    protected        $_modelClass     = 'FCom_Admin_Model_Role';
    protected        $_gridHref       = 'roles';
    protected        $_gridTitle      = 'Roles and Permissions';
    protected        $_recordName     = 'Role';
    protected        $_formTitleField = 'role_name';
    protected        $_formLayoutName = '/roles/form';


    public function gridConfig()
    {
        $config            = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'width' => 85, 'buttons' => [['name' => 'edit']]],
            ['name' => 'role_name', 'label' => 'Role Name', 'width' => 100],
        ];
        $config['actions'] = [
            'delete' => true,
        ];
        $config['filters'] = [
            ['field' => 'role_name', 'type' => 'text'],
        ];

        return $config;
    }

    public function gridPermissionsConfig()
    {
        $config      = [
            'id'        => static::$_origClass . '_permissions',
            'data_mode' => 'local',
            'columns'   => [
                ['type' => 'row_select'],
                ['name' => 'title', 'label' => 'Permission Name', 'width' => 100],
                ['name' => 'path', 'label' => 'Permission Path', 'width' => 100],
                [
                    'type'               => 'input',
                    'name'               => 'status',
                    'label'              => "Status",
                    'overflow'           => true,
                    'options'            => $this->FCom_Admin_Model_Role->fieldOptions('status'),
                    'width'              => 100,
                    'validation'         => ['required' => true],
                    'editable'           => 'inline',
                    'multirow_edit_show' => true,
                    'multirow_edit'      => true,
                    'editor'             => 'select'
                ]
            ],
            'filters'   => [
                ['field' => 'title', 'type' => 'text'],
                ['field' => 'status', 'type' => 'multiselect'],
            ]
        ];
        $permissions = $this->FCom_Admin_Model_Role->getAllPermissions();
        ksort($permissions);
        $data = [];
        foreach ($permissions as $path => $perm) {
            $data[] = ['id' => $path, 'title' => $perm['title'], 'path' => $path, 'status' => 'all'];
        }

        $config['data'] = $data;

        return ['config' => $config];
    }

    public function formPostBefore($args)
    {
        parent::formPostBefore($args);

        if (empty($args['data']['model']['permissions'])) {
            $args['data']['model']['permissions'] = [];
        }
        if (!empty($args['data']['ie_perm_ids_add'])) {
            $iePerms = $args['data']['ie_perm_ids_add'];
            foreach ((array) $iePerms as $type => $permissions) {
                if (empty($permissions)) {
                    continue;
                }
                foreach (explode(',', $permissions) as $p) {
                    $args['data']['permissions'][$p . '/' . $type] = 1;
                }
            }
        }
    }

    public function formPostAfter($args)
    {
        $data  = $args['data'];
        $model = $args['model'];
        if (!empty($data['user_ids_remove'])) {
            $userIds = explode(",", $data['user_ids_remove']);
            $this->FCom_Admin_Model_User->update_many(['role_id' => null], ['id' => $userIds]);
        }
        if (!empty($data['user_ids_add'])) {
            $userIds = explode(",", $data['user_ids_add']);
            $this->FCom_Admin_Model_User->update_many(['role_id' => $model->id()], ['id' => $userIds]);
        }
    }

}
